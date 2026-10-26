<?php

namespace Tests\Feature\Accounting;

use App\CrmInventoryItem;
use App\CrmInventoryMovement;
use App\Services\FinancialReportService;
use App\Services\InventoryAccounting;
use App\Support\CrmWorkspaceContext;
use App\Vendor;
use Carbon\Carbon;
use ReflectionProperty;

/** The item View page's stock history: search, and "Revert" (a reversing entry that also undoes the entry in the books). */
class StockHistoryRevertTest extends AccountingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetStockConfigs();
    }

    protected function tearDown(): void
    {
        $this->resetStockConfigs();
        parent::tearDown();
    }

    public function test_reverting_a_wastage_puts_the_stock_back_and_takes_the_loss_out_of_the_books(): void
    {
        $this->startBooks();
        $paper = $this->newItem('Revert Paper', 100, 2);                 // opening stock 200
        $this->post(route('crm.inventory.adjust', $paper->id), ['reason' => 'wastage', 'quantity_lost' => 10])->assertSessionHasNoErrors();
        $this->assertMoney(20, $this->pl()['opex'][InventoryAccounting::LOSS] ?? 0);
        $wastage = $this->lastMovement($paper);

        $this->revert($paper, $wastage)->assertSessionHas('success', fn ($m) => str_contains($m, '+10 sheets') && str_contains($m, 'now 100 sheets'));

        $this->assertSame(100.0, (float) $paper->fresh()->quantity);
        $reversal = $this->lastMovement($paper);
        $this->assertSame([$wastage->id, 'wastage', 'adjust'], [(int) $reversal->reversal_of_id, $reversal->reason, $reversal->type]);
        $this->assertMoney(0, $this->pl()['opex'][InventoryAccounting::LOSS] ?? 0, 'the loss is taken back out');
        $books = $this->books();
        $this->assertMoney(200, $books['inventory']);
        $this->assertMoney(0, $books['balancing']);

        // The page marks both rows, and neither can be reverted again.
        $page = $this->get(route('crm.inventory.movements', $paper->id))->assertOk()->assertSee('Reverted')->assertSee('Reversal');
        $this->assertTrue($page->viewData('reverted')->has($wastage->id));
        $this->revert($paper, $wastage)->assertSessionHas('error', 'Cannot revert: This entry was already reverted.');
        $this->revert($paper, $reversal)->assertSessionHas('error', 'Cannot revert: This entry is itself a reversal.');
        $this->assertSame(100.0, (float) $paper->fresh()->quantity);
    }

    public function test_reverting_a_job_use_returns_the_stock_and_the_cost_from_that_job(): void
    {
        $this->startBooks();
        $board = $this->newItem('Revert Board', 50, 3);
        $this->post(route('crm.inventory.consume', $board->id), ['quantity' => 20, 'job_number' => 'AMS-7001'])->assertSessionHasNoErrors();
        $this->assertMoney(60, $this->pl()['cos'][InventoryAccounting::USED]);

        $this->revert($board, $this->lastMovement($board))->assertSessionHasNoErrors();

        $this->assertSame(50.0, (float) $board->fresh()->quantity);
        $reversal = $this->lastMovement($board);
        $this->assertSame(['in', 'job', 'AMS-7001'], [$reversal->type, $reversal->reason, $reversal->job_number]);
        $this->assertMoney(0, $this->pl()['cos'][InventoryAccounting::USED] ?? 0);
        $page = $this->get(route('crm.inventory.movements', $board->id))->assertOk();
        $this->assertTrue($page->viewData('jobs')->isEmpty(), 'the job no longer shows stock used');
        $this->assertMoney(0, $this->books()['balancing']);
    }

    public function test_reverting_opening_stock_takes_it_off_the_books_at_its_own_price(): void
    {
        $this->startBooks();
        $card = $this->newItem('Revert Card', 40, 5);                    // opening 200
        $this->post(route('crm.inventory.stock_in', $card->id), ['quantity' => 60, 'unit_cost' => 10, 'reason' => 'expensed'])->assertSessionHasNoErrors();
        $opening = CrmInventoryMovement::where('inventory_item_id', $card->id)->orderBy('id')->firstOrFail();

        $this->revert($card, $opening)->assertSessionHasNoErrors();

        $card = $card->fresh();
        $this->assertSame(60.0, (float) $card->quantity);
        $this->assertMoney(10, $card->unit_cost, 'what is left keeps its own price');
        $books = $this->books();
        $this->assertMoney(600, $books['inventory']);
        $this->assertMoney(0, $books['opening_stock']);
        $this->assertMoney(0, $books['balancing']);
    }

    public function test_entries_owned_by_another_page_or_that_would_go_below_zero_cannot_be_reverted(): void
    {
        $this->startBooks();
        $ink = $this->newItem('Revert Ink', 0, 0);
        $vendor = Vendor::create(['workspace_id' => $this->workspace->id, 'name' => 'Revert Vendor '.uniqid()]);
        $this->post(route('crm.purchase_invoices.store'), ['vendor_id' => $vendor->id, 'issue_date' => '2026-10-26', 'currency' => 'AED',
            'items' => [['inventory_item_id' => $ink->id, 'name' => 'ink', 'category' => 'stock', 'quantity' => 30, 'unit_price' => 4]]])->assertSessionHasNoErrors();
        $fromBill = $this->lastMovement($ink);

        $this->revert($ink, $fromBill)->assertSessionHas('error', fn ($m) => str_contains($m, 'purchase invoice'));
        $this->assertSame(30.0, (float) $ink->fresh()->quantity);

        // Stock in 20, use 45 of the 50: taking the 20 back out would leave −15.
        $this->post(route('crm.inventory.stock_in', $ink->id), ['quantity' => 20, 'unit_cost' => 4, 'reason' => 'expensed'])->assertSessionHasNoErrors();
        $stockIn = $this->lastMovement($ink);
        $this->post(route('crm.inventory.consume', $ink->id), ['quantity' => 45, 'job_number' => 'AMS-7002'])->assertSessionHasNoErrors();
        $this->revert($ink, $stockIn)->assertSessionHas('error', 'Cannot revert: only 5 sheets left, and this would take out 20.');
        $this->assertSame(5.0, (float) $ink->fresh()->quantity);

        // Job-card and demand-request entries are changed on those pages.
        foreach (['Used on job AMS-7003' => 'job card', 'Demand #55 received' => 'demand request'] as $reference => $owner) {
            $m = CrmInventoryMovement::create(['workspace_id' => $this->workspace->id, 'inventory_item_id' => $ink->id, 'type' => 'out', 'reason' => 'job',
                'quantity' => 1, 'balance_after' => 4, 'unit_cost' => 4, 'reference' => $reference]);
            $this->assertStringContainsString($owner, InventoryAccounting::revertProblem($m, false));
        }
    }

    public function test_entries_from_before_stock_was_in_the_books_cannot_be_reverted(): void
    {
        $old = $this->newItem('Revert Old', 25, 1);       // made before the cut-off starts (nothing has read it yet)
        $this->startBooks();
        $this->revert($old, $this->lastMovement($old))->assertSessionHas('error', fn ($m) => str_contains($m, 'before stock was in the books'));
        $this->assertSame(25.0, (float) $old->fresh()->quantity);
    }

    public function test_only_admins_and_accounts_can_revert(): void
    {
        $this->startBooks();
        $paper = $this->newItem('Revert Rights', 10, 1);
        $this->post(route('crm.inventory.adjust', $paper->id), ['reason' => 'damage', 'quantity_lost' => 2])->assertSessionHasNoErrors();
        $damage = $this->lastMovement($paper);

        $this->signIn($this->makeUser('sales_manager'));
        $page = $this->get(route('crm.inventory.movements', $paper->id))->assertOk()->assertDontSee('fa-undo');
        $this->assertFalse($page->viewData('canRevert'));
        $this->revert($paper, $damage)->assertForbidden();

        $this->signIn($this->makeUser('accounts'));
        $this->get(route('crm.inventory.movements', $paper->id))->assertOk()->assertSee('fa-undo', false);
        $this->revert($paper, $damage)->assertSessionHasNoErrors();
        $this->assertSame(10.0, (float) $paper->fresh()->quantity);
    }

    public function test_the_stock_history_can_be_searched(): void
    {
        $this->startBooks();
        $paper = $this->newItem('Search Paper', 100, 1);
        $this->post(route('crm.inventory.consume', $paper->id), ['quantity' => 5, 'job_number' => 'AMS-8101'])->assertSessionHasNoErrors();
        $this->post(route('crm.inventory.consume', $paper->id), ['quantity' => 7, 'job_number' => 'AMS-8102'])->assertSessionHasNoErrors();
        $this->post(route('crm.inventory.adjust', $paper->id), ['reason' => 'wastage', 'quantity_lost' => 3, 'note' => 'torn sheets'])->assertSessionHasNoErrors();
        $this->post(route('crm.inventory.stock_in', $paper->id), ['quantity' => 50, 'unit_cost' => 1, 'reason' => 'expensed', 'reference' => 'Silver Star delivery'])->assertSessionHasNoErrors();

        $found = fn (string $q) => collect($this->get(route('crm.inventory.movements', [$paper->id, 'q' => $q]))->assertOk()->viewData('movements')->items());

        $this->assertSame(['Used on AMS-8102'], $found('8102')->pluck('reference')->all(), 'by job number');
        $this->assertCount(2, $found('used'), 'by the type shown on the page');
        $this->assertSame(['Wastage (production) (−)'], $found('torn')->pluck('reference')->all(), 'by note');
        $this->assertSame(['Wastage (production) (−)'], $found('wastage')->pluck('reference')->all(), 'by reason');
        $this->assertSame(['Silver Star delivery'], $found('silver star')->pluck('reference')->all());
        $this->assertCount(5, $found('26 Oct'), 'by date');
        $this->assertCount(5, $found($this->admin->name), 'by who made it');
        $this->assertCount(0, $found('nothing like this'));

        // The quantity shown on a found row is still that entry's own change.
        $page = $this->get(route('crm.inventory.movements', [$paper->id, 'q' => 'AMS-8102']));
        $row = collect($page->viewData('movements')->items())->first();
        $this->assertSame(-7.0, $page->viewData('deltas')[$row->id]);
        $page->assertSee('1 entry found');
    }

    // ───────────── helpers ─────────────

    private function startBooks(): void
    {
        CrmWorkspaceContext::set($this->workspace->id);
        InventoryAccounting::config();
    }

    private function newItem(string $name, float $qty, float $cost): CrmInventoryItem
    {
        $name .= ' '.uniqid();
        $this->post(route('crm.inventory.store'), ['name' => $name, 'unit' => 'sheets', 'quantity' => $qty, 'unit_cost' => $cost])->assertSessionHasNoErrors();

        return CrmInventoryItem::where('name', $name)->firstOrFail();
    }

    private function lastMovement(CrmInventoryItem $item): CrmInventoryMovement
    {
        return CrmInventoryMovement::where('inventory_item_id', $item->id)->orderByDesc('id')->firstOrFail();
    }

    private function revert(CrmInventoryItem $item, CrmInventoryMovement $m)
    {
        return $this->from(route('crm.inventory.movements', $item->id))->post(route('crm.inventory.revert', [$item->id, $m->id]));
    }

    private function pl(): array
    {
        CrmWorkspaceContext::set($this->workspace->id);

        return (new FinancialReportService(Carbon::parse('2026-01-01'), Carbon::parse('2026-10-26')))
            ->plTotalsFor(Carbon::parse('2026-01-01')->startOfDay(), Carbon::parse('2026-10-26')->endOfDay());
    }

    /** Balance sheet lines by kind today. */
    private function books(): array
    {
        CrmWorkspaceContext::set($this->workspace->id);
        $sheet = (new FinancialReportService(Carbon::parse('2026-01-01'), Carbon::parse('2026-10-26')))->balanceSheetAsOf(Carbon::parse('2026-10-26'));
        $lines = [];
        foreach (array_merge($sheet['assets'], $sheet['liabilities'], $sheet['equity']) as [$label, $amount, $url, $kind]) {
            $lines[$kind] = ($lines[$kind] ?? 0) + (float) $amount;
        }

        return $lines;
    }

    /** The cut-off config is cached per workspace id for the whole PHP process. */
    private function resetStockConfigs(): void
    {
        $property = new ReflectionProperty(InventoryAccounting::class, 'configs');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }
}
