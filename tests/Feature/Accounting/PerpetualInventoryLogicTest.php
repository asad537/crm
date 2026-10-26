<?php

namespace Tests\Feature\Accounting;

use App\CrmInventoryItem;
use App\CrmInventoryMovement;
use App\CrmPurchaseInvoice;
use App\Services\InventoryAccounting;
use App\Services\InventoryService;
use App\Support\CrmWorkspaceContext;
use App\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** The pure rules of perpetual inventory (InventoryAccounting): labels, movement reasons, the moving-average replay, bill stock. */
class PerpetualInventoryLogicTest extends AccountingTestCase
{
    private string $tag;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forgetStockConfig();
        $this->tag = substr(uniqid(), -6);
    }

    protected function tearDown(): void
    {
        $this->forgetStockConfig();
        parent::tearDown();
    }

    public function test_item_label_adds_size_and_gsm_only_when_the_name_lacks_them(): void
    {
        $label = fn (array $a) => InventoryAccounting::itemLabel(new CrmInventoryItem($a));

        $this->assertSame('Art Card 20x30 300gsm', $label(['name' => 'Art Card', 'paper_size' => '20x30', 'gsm' => '300']));
        $this->assertSame('Art Card 20 x 30 300gsm', $label(['name' => 'Art Card 20 x 30 300gsm', 'paper_size' => '20x30', 'gsm' => '300']), 'spaces are ignored');
        $this->assertSame('Art Card 300 GSM', $label(['name' => 'Art Card 300 GSM', 'gsm' => '300']), 'case is ignored');
        $this->assertSame('Art Card 300gsm', $label(['name' => 'Art Card', 'gsm' => '300gsm']), 'a GSM that already says gsm is kept as it is');
        $this->assertSame('Kraft heavy', $label(['name' => 'Kraft', 'gsm' => 'heavy']));
        $this->assertSame('Ink', $label(['name' => '  Ink ', 'paper_size' => ' ', 'gsm' => null]));
    }

    public function test_a_movement_without_a_saved_reason_gets_one_from_how_it_was_made(): void
    {
        $reason = fn (array $a) => InventoryAccounting::reasonOf(new CrmInventoryMovement($a));

        $this->assertSame('damage', $reason(['type' => 'adjust', 'reason' => 'damage']), 'a saved reason wins');
        $this->assertSame('bill', $reason(['type' => 'out', 'purchase_invoice_id' => 5]));
        $this->assertSame('opening', $reason(['type' => 'in', 'reference' => 'Opening stock']));
        $this->assertSame('job', $reason(['type' => 'out', 'job_number' => ' AMS-0001 ']));
        $this->assertSame('job', $reason(['type' => 'in', 'job_id' => 9]), 'stock returned from a job');
        $this->assertSame('count', $reason(['type' => 'adjust']));
        $this->assertSame('expensed', $reason(['type' => 'in', 'reference' => 'Demand #12 received']));
        $this->assertSame('job', $reason(['type' => 'out', 'job_number' => '  ']), 'a plain stock-out is use');
    }

    public function test_books_labels_say_where_each_movement_went(): void
    {
        $afterId = 1000;
        $this->startStockBooks('2026-10-26', [], $afterId);
        $label = fn (array $a, int $id = 5000) => InventoryAccounting::booksLabel((new CrmInventoryMovement($a))->forceFill(['id' => $id]));

        $this->assertSame('Before 26 Oct 2026 (not in books)', $label(['type' => 'in', 'reason' => 'bill'], $afterId));
        $this->assertSame('Inventory (bought on bill)', $label(['type' => 'in', 'reason' => 'bill']));
        $this->assertSame('Bill changed / removed', $label(['type' => 'out', 'reason' => 'bill']));
        $this->assertSame('Opening stock (equity)', $label(['type' => 'in', 'reason' => 'opening']));
        $this->assertSame('Moved from expenses into stock', $label(['type' => 'in', 'reason' => 'expensed']));
        $this->assertSame('Back to expenses', $label(['type' => 'out', 'reason' => 'expensed']));
        $this->assertSame('Cost of Sales (materials used)', $label(['type' => 'out', 'reason' => 'job']));
        $this->assertSame('Returned from job', $label(['type' => 'in', 'reason' => 'job']));
        foreach (InventoryAccounting::LOSS_REASONS as $key => $text) {
            $this->assertSame('Stock loss & wastage — '.$text, $label(['type' => 'adjust', 'reason' => $key]));
        }
        $this->assertSame('Stock loss & wastage', $label(['type' => 'adjust', 'reason' => 'correction']));
        $this->assertTrue(InventoryAccounting::isBooked((new CrmInventoryMovement())->forceFill(['id' => $afterId + 1])));
        $this->assertFalse(InventoryAccounting::isBooked((new CrmInventoryMovement())->forceFill(['id' => $afterId])));
    }

    public function test_a_fresh_workspace_starts_its_stock_books_today_with_nothing_on_hand(): void
    {
        $this->assertSame(0, DB::table('crm_settings')->where('workspace_id', $this->workspace->id)->where('key', InventoryAccounting::KEY)->count());
        $config = InventoryAccounting::config();
        $this->assertSame('2026-10-26', $config['from']);
        $this->assertSame([], $config['opening']);
        $this->assertSame((int) DB::table('crm_inventory_movements')->max('id'), $config['after_id']);
        $saved = json_decode(DB::table('crm_settings')->where('workspace_id', $this->workspace->id)->where('key', InventoryAccounting::KEY)->value('value'), true);
        $this->assertSame($config, $saved, 'the cut-off is saved once');
        $this->assertSame($config, InventoryAccounting::start($this->workspace->id), 'starting again keeps the saved cut-off');
    }

    public function test_stock_is_replayed_at_moving_average_cost(): void
    {
        InventoryAccounting::config();
        $item = $this->item('Board');
        $service = app(InventoryService::class);
        $service->move($item, 'in', 1000, ['unit_cost' => 20, 'reason' => 'expensed'], null);
        $service->move($item, 'in', 1000, ['unit_cost' => 18, 'reason' => 'expensed'], null);
        $this->assertMoney(19, $item->fresh()->unit_cost, '1000 @ 20 + 1000 @ 18 → 19.00');
        $service->move($item, 'out', 500, ['reason' => 'job', 'job_number' => 'AMS-0200'], null);
        $service->move($item, 'adjust', 100, ['reason' => 'wastage', '_signed' => -100], null);
        $service->move($item, 'in', 50, ['reason' => 'job', 'job_number' => 'AMS-0200'], null);   // returned from the job at the average

        $stock = new InventoryAccounting();
        $this->assertSame([['expensed', 1000.0, 20000.0], ['expensed', 1000.0, 18000.0], ['job', -500.0, -9500.0], ['loss', -100.0, -1900.0], ['job', 50.0, 950.0]],
            $stock->events()->map(fn ($e) => [$e->kind, $e->qty, round($e->value, 2)])->values()->all());
        $this->assertMoney(27550, $stock->valueAsOf(Carbon::parse('2026-10-26')->endOfDay()));
        $this->assertMoney(1450 * 19, $stock->valueAsOf(Carbon::parse('2026-10-26')->endOfDay()), 'still 19.00 a sheet');
        $this->assertMoney(0, $stock->valueAsOf(Carbon::parse('2026-10-25')->endOfDay()), 'nothing before the movements');

        $costs = $stock->costEvents()->groupBy('category')->map(fn ($g) => round($g->sum('amount'), 2))->all();
        $this->assertSame([InventoryAccounting::MOVED_IN => -38000.0, InventoryAccounting::USED => 8550.0, InventoryAccounting::LOSS => 1900.0], $costs);
    }

    public function test_stock_used_before_its_bill_came_in_is_corrected_into_cost_of_sales(): void
    {
        InventoryAccounting::config();
        $item = $this->item('Ink');
        $service = app(InventoryService::class);
        $service->move($item, 'out', 5, ['reason' => 'job', 'job_number' => 'AMS-0201'], null);   // nothing on hand yet: no value to take
        $this->assertMoney(-5, $item->fresh()->quantity);
        $service->move($item, 'in', 10, ['reason' => 'bill', 'unit_cost' => 2], null);

        $stock = new InventoryAccounting();
        $this->assertSame([['job', -5.0, 0.0], ['bill', 10.0, 20.0], ['correction', 0.0, -10.0]],
            $stock->events()->map(fn ($e) => [$e->kind, $e->qty, round($e->value, 2) + 0.0])->values()->all());
        $this->assertMoney(10, $stock->valueAsOf(now()->endOfDay()), '5 left @ 2');
        $this->assertMoney(10, $stock->costEvents()->where('category', InventoryAccounting::USED)->sum('amount'), 'the 5 used @ 2 are Cost of Sales');
    }

    public function test_stock_on_hand_at_the_cut_off_is_the_start_value(): void
    {
        $item = $this->item('Duplex', ['quantity' => 100, 'unit_cost' => 2]);
        $this->startStockBooks('2026-10-01', [$item->id => [100, 2]]);
        app(InventoryService::class)->move($item, 'out', 40, ['reason' => 'job', 'job_number' => 'AMS-0202'], null);

        $stock = new InventoryAccounting();
        $this->assertSame(['start', 'job'], $stock->events()->pluck('kind')->all());
        $this->assertSame('2026-10-01 00:00:00', $stock->events()->first()->date->toDateTimeString());
        $this->assertMoney(0, $stock->valueAsOf(Carbon::parse('2026-09-30')->endOfDay()));
        $this->assertMoney(200, $stock->valueAsOf(Carbon::parse('2026-10-01')->endOfDay()));
        $this->assertMoney(120, $stock->valueAsOf(Carbon::parse('2026-10-26')->endOfDay()));
        $this->assertMoney(200, $stock->openingStockAsOf(Carbon::parse('2026-10-26')->endOfDay()));
        $this->assertMoney(0, $stock->openingStockAsOf(Carbon::parse('2026-09-30')->endOfDay()));
        $row = $stock->itemsAsOf(Carbon::parse('2026-10-26')->endOfDay())->get($item->id);
        $this->assertSame([60.0, 120.0], [$row['qty'], round($row['value'], 2)]);
    }

    public function test_a_bills_stock_and_what_blocks_changing_it(): void
    {
        InventoryAccounting::config();
        $item = $this->item('Art Card');
        $vendor = Vendor::create(['workspace_id' => $this->workspace->id, 'name' => 'Logic Vendor '.$this->tag]);
        $invoice = CrmPurchaseInvoice::create(['vendor_id' => $vendor->id, 'vendor_name' => $vendor->name, 'issue_date' => '2026-10-26', 'currency' => 'AED', 'total' => 50]);
        $stock = new InventoryAccounting();

        $stock->syncBill($invoice, [$item->id => [100, 50]], $this->admin->id);
        $this->assertMoney(100, $item->fresh()->quantity);
        $this->assertSame([$item->id => [100.0, 50.0]], InventoryAccounting::billStock($invoice->id));
        $move = CrmInventoryMovement::where('purchase_invoice_id', $invoice->id)->firstOrFail();
        $this->assertSame(['in', 'bill', '2026-10-26', 'Bought on '.$invoice->number, $vendor->name], [$move->type, $move->reason, $move->entry_date->toDateString(), $move->reference, $move->note]);

        $count = CrmInventoryMovement::count();
        $stock->syncBill($invoice, [$item->id => [100, 50]], $this->admin->id);
        $this->assertSame($count, CrmInventoryMovement::count(), 'nothing to do when the bill did not change');

        $this->assertSame([], InventoryAccounting::billProblems($invoice->id, [$item->id => [40, 20]]), '60 can come back out of 100');
        app(InventoryService::class)->move($item, 'out', 70, ['reason' => 'job', 'job_number' => 'AMS-0203'], null);
        $problems = InventoryAccounting::billProblems($invoice->id, [$item->id => [40, 20]]);
        $this->assertCount(1, $problems);
        $this->assertStringContainsString('this bill brought in 100 sheets but only 30 sheets are left', $problems[0]);
        $this->assertSame([], InventoryAccounting::billProblems($invoice->id, [$item->id => [100, 60]]), 'a price change takes nothing out');
        $this->assertSame([], InventoryAccounting::billProblems($invoice->id, [$item->id => [130, 65]]), 'more is always fine');
        $this->assertSame([], InventoryAccounting::billProblems(null, []), 'a new bill has nothing to give back');
        $this->assertCount(1, InventoryAccounting::billProblems($invoice->id, []), 'removing all of it is blocked');
    }

    // ───────────────────────────── Helpers ─────────────────────────────

    private function forgetStockConfig(): void
    {
        $property = new \ReflectionProperty(InventoryAccounting::class, 'configs');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    private function startStockBooks(string $from, array $opening = [], ?int $afterId = null): void
    {
        DB::table('crm_settings')->where('workspace_id', $this->workspace->id)->where('key', InventoryAccounting::KEY)->delete();
        DB::table('crm_settings')->insert(['workspace_id' => $this->workspace->id, 'key' => InventoryAccounting::KEY, 'created_at' => now(), 'updated_at' => now(),
            'value' => json_encode(['from' => $from, 'after_id' => $afterId ?? (int) DB::table('crm_inventory_movements')->max('id'), 'opening' => (object) $opening])]);
        $this->forgetStockConfig();
    }

    /** An item made directly (with its opening movement when it has a quantity), named uniquely. */
    private function item(string $name, array $fields = []): CrmInventoryItem
    {
        CrmWorkspaceContext::set($this->workspace->id);
        $qty = (float) ($fields['quantity'] ?? 0);
        $item = CrmInventoryItem::create(array_merge(['name' => $name.' '.$this->tag, 'unit' => 'sheets', 'category' => 'Paper'], $fields, ['quantity' => 0]));
        if ($qty > 0) {
            app(InventoryService::class)->move($item, 'in', $qty, ['unit_cost' => $fields['unit_cost'] ?? null, 'reason' => 'opening', 'reference' => 'Opening stock'], null);
        }

        return $item->fresh();
    }
}
