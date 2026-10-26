<?php

namespace Tests\Feature\Accounting;

use App\CrmInventoryItem;
use App\CrmInventoryMovement;
use App\CrmPurchaseInvoice;
use App\CrmPurchaseInvoiceItem;
use App\CrmWorkspace;
use App\Services\FinancialReportService;
use App\Services\InventoryAccounting;
use App\Support\CrmWorkspaceContext;
use App\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Perpetual inventory (IAS 2): stock bought on purchase invoices is Inventory on the balance sheet, stock used on a job is
 * Cost of Sales ("Materials Used"), wastage / damage / count differences are "Stock Loss & Wastage", stock moved in from
 * bills already in expenses is "Purchases Moved Into Stock" and new opening stock is equity. Through every stock operation
 * the balance sheet's balancing figure stays where it was (0 in a fresh workspace) and the trial balance agrees.
 */
class PerpetualInventoryTest extends AccountingTestCase
{
    private const TODAY = '2026-10-26';

    private Vendor $vendor;
    private string $tag;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forgetStockConfig();
        $this->tag = substr(uniqid(), -6);
        $this->vendor = Vendor::create(['workspace_id' => $this->workspace->id, 'name' => 'Paper House '.$this->tag]);
        // Start stock in the books for the fresh workspace now (cut-off today, nothing on hand), before any stock moves.
        InventoryAccounting::config();
    }

    protected function tearDown(): void
    {
        $this->forgetStockConfig();
        parent::tearDown();
    }

    // ───────────────────────────── The whole cycle ─────────────────────────────

    public function test_a_full_stock_cycle_keeps_the_books_balanced(): void
    {
        $art = $this->newItem('Art Card', ['paper_size' => '20x30', 'gsm' => '300']);
        $this->assertMoney(0, $art->quantity);
        $this->assertSame(0, CrmInventoryMovement::where('inventory_item_id', $art->id)->count(), 'no opening movement for qty 0');
        $this->assertBooks(['inventory' => 0, 'opening_stock' => 0, 'payable' => 0], 'new item');

        // 1) Bill on credit: 1000 sheets @ 0.50 (stock) + glue 20 (Consumables).
        $bill = $this->bill([$this->stockLine($art, 1000, 0.5), $this->costLine('Glue', 'Consumables', 1, 20)]);
        $this->assertMoney(520, $bill->total);
        $stockLine = $bill->items->firstWhere('inventory_item_id', $art->id);
        $this->assertSame(CrmPurchaseInvoiceItem::STOCK_CATEGORY, $stockLine->category, 'stock lines get the Inventory category');
        $this->assertSame($art->name.' 20x30 300gsm', $stockLine->name, 'the line is named after the stock item');
        $art->refresh();
        $this->assertMoney(1000, $art->quantity);
        $this->assertMoney(0.5, $art->unit_cost);
        $this->assertBooks(['inventory' => 500, 'payable' => 520, 'consumables' => 20, 'stock_lines' => 0, 'used' => 0, 'profit' => -20], 'bill on credit');

        // 2) 300 sheets used on a job → Cost of Sales at average cost.
        $this->consume($art, 300, 'AMS-0099')->assertSessionHas('success');
        $this->assertBooks(['inventory' => 350, 'used' => 150, 'profit' => -170], 'used on a job');

        // 3) 50 sheets wasted → Stock Loss & Wastage.
        $this->adjust($art, ['reason' => 'wastage', 'quantity_lost' => 50, 'note' => 'make-ready'])->assertSessionHas('success');
        $this->assertMoney(650, $art->fresh()->quantity);
        $this->assertBooks(['inventory' => 325, 'used' => 150, 'loss' => 25], 'wastage');

        // 4) The bill is corrected to 800 sheets @ 0.55: the bill's 1000 @ 0.50 go out at their own price, 800 @ 0.55 come in.
        $this->updateBill($bill, [$this->stockLine($art, 800, 0.55), $this->costLine('Glue', 'Consumables', 1, 20)])->assertSessionHasNoErrors();
        $art->refresh();
        $this->assertMoney(450, $art->quantity);
        $this->assertMoney(460, $bill->fresh()->total);
        $this->assertSame([$art->id => [800.0, 440.0]], $this->billStock($bill));
        $this->assertBooks(['inventory' => 265, 'payable' => 460, 'used' => 150, 'loss' => 25, 'consumables' => 20], 'bill edited down');
        $this->assertEqualsWithDelta(265 / 450, (float) $art->unit_cost, 0.0001, 'item average = value in the books / qty');

        // 5) Cutting the bill to 100 would take out 700 more than is left: blocked, nothing changes.
        $this->updateBill($bill, [$this->stockLine($art, 100, 0.55)])->assertSessionHasErrors('items');
        $this->assertStringContainsString('this bill brought in 800 sheets but only 450 sheets are left', session('errors')->first('items'));
        $this->assertMoney(450, $art->fresh()->quantity);
        $this->assertMoney(460, $bill->fresh()->total);
        $this->assertCount(2, $bill->fresh()->items);

        // 6) A stock count finds 460 (a gain of 10 at average cost reduces the stock loss).
        $this->adjust($art, ['reason' => 'count', 'new_quantity' => 460])->assertSessionHas('success');
        $this->assertMoney(460, $art->fresh()->quantity);
        $this->assertBooks(['inventory' => 270.89, 'loss' => 19.11, 'used' => 150], 'count gain');

        // 7) Deleting the bill would take out 800 sheets with only 460 left: blocked.
        $this->delete(route('crm.purchase_invoices.destroy', $bill->id))->assertSessionHas('error');
        $this->assertStringContainsString('cannot be deleted', session('error'));
        $this->assertNotNull(CrmPurchaseInvoice::find($bill->id));
        $this->assertMoney(460, $art->fresh()->quantity);

        // 8) Stock in for a bill already in expenses → moved from costs into Inventory.
        $board = $this->newItem('Kraft Board');
        $this->post(route('crm.inventory.stock_in', $board->id), ['quantity' => 100, 'unit_cost' => 2, 'reason' => 'expensed', 'reference' => 'VP-77'])
            ->assertSessionHas('success');
        $this->assertBooks(['inventory' => 470.89, 'moved_in' => -200], 'expensed stock in');

        // 9) A new item with opening stock → Inventory + Opening Stock (equity).
        $this->newItem('Process Ink', ['category' => 'Ink', 'unit' => 'kg', 'quantity' => 10, 'unit_cost' => 5]);
        $this->assertBooks(['inventory' => 520.89, 'opening_stock' => 50], 'opening stock');

        // 10) Paying 200 on the bill: payable and cash go down together.
        $this->post(route('crm.purchase_invoices.payments.store', $bill->id), ['amount' => 200, 'paid_at' => self::TODAY, 'method' => 'Cash'])
            ->assertSessionHas('success');
        $this->assertBooks(['payable' => 260, 'cash' => -200, 'inventory' => 520.89, 'profit' => 10.89], 'payment');

        // 11) Another bill, edited up, then deleted while unused: its stock comes back out at its own price.
        $second = $this->bill([$this->stockLine($art, 200, 0.6)]);
        $this->assertBooks(['inventory' => 640.89, 'payable' => 380], 'second bill');
        $this->updateBill($second, [$this->stockLine($art, 300, 0.6)])->assertSessionHasNoErrors();
        $this->assertMoney(760, $art->fresh()->quantity);
        $this->assertBooks(['inventory' => 700.89, 'payable' => 440], 'second bill edited up');
        $this->delete(route('crm.purchase_invoices.destroy', $second->id))->assertRedirect(route('crm.purchase_invoices.index'));
        $this->assertNull(CrmPurchaseInvoice::find($second->id));
        $this->assertMoney(460, $art->fresh()->quantity);
        $this->assertBooks(['inventory' => 520.89, 'payable' => 260, 'cash' => -200, 'opening_stock' => 50, 'used' => 150, 'loss' => 19.11, 'moved_in' => -200,
            'consumables' => 20, 'stock_lines' => 0], 'second bill deleted');

        // Every report still builds.
        foreach (['inventory', 'jobs', 'profit-loss', 'balance-sheet', 'owners-equity', 'expenses', 'trial-balance', 'cash-flow', 'day-book', 'budget'] as $report) {
            $this->get(route('crm.financial_reports.index', ['report' => $report]))->assertOk();
        }
    }

    // ───────────────────────────── Bills ─────────────────────────────

    public function test_a_stock_bill_line_is_inventory_not_a_profit_and_loss_cost(): void
    {
        $art = $this->newItem('Art Card');
        $this->bill([$this->stockLine($art, 1000, 0.5), $this->costLine('Glue', 'Consumables', 1, 20)]);

        $pl = $this->report('profit-loss');
        $this->assertSame([], $this->plCategories($pl, 'Cost of Sales'), 'nothing in Cost of Sales until stock is used');
        $this->assertSame(['Consumables'], $this->plCategories($pl, 'Operating Expenses'), 'only the glue is a cost');
        $this->assertMoney(-20, collect($pl['tables'][0]['rows'])->firstWhere('cells.0', 'Net Profit / (Loss)')['cells'][1]);

        $entries = (new FinancialReportService(Carbon::parse('2026-01-01'), Carbon::parse(self::TODAY)))->entries('costs');
        $this->assertCount(1, $entries['tables'][0]['rows'], 'the stock line is not a cost entry');

        $sheet = $this->sheetLines();
        $this->assertMoney(500, $sheet['inventory'][1]);
        $this->assertSame('Inventory (stock at cost)', $sheet['inventory'][0]);
        $this->assertMoney(520, $sheet['invoice'][1]);
        $this->assertMoney(0, $sheet['opening_stock'][1]);
        $this->assertBalanced('bill');

        $trial = $this->report('trial-balance');
        $row = collect($trial['tables'][0]['rows'])->first(fn ($r) => is_array($r[0] ?? null) && $r[0]['text'] === 'Inventory (stock at cost)');
        $this->assertMoney(500, $row[1], 'Inventory is on the debit side');
    }

    public function test_inventory_is_valued_at_moving_average_cost(): void
    {
        $art = $this->newItem('Art Card');
        $this->bill([$this->stockLine($art, 100, 2.00)]);
        $this->bill([$this->stockLine($art, 100, 1.00)]);
        $art->refresh();
        $this->assertMoney(1.5, $art->unit_cost, '100 @ 2 + 100 @ 1 → 1.50');

        $this->consume($art, 50, 'AMS-0101');
        $this->bill([$this->stockLine($art, 50, 3.00)]);   // 150 @ 1.50 + 50 @ 3.00 → 200 @ 1.875
        $this->consume($art, 100, 'AMS-0102');

        $art->refresh();
        $this->assertMoney(100, $art->quantity);
        $this->assertEqualsWithDelta(1.875, (float) $art->unit_cost, 0.0001);
        $this->assertBooks(['inventory' => 187.5, 'used' => 75 + 187.5, 'payable' => 450], 'moving average');

        // Replay per movement: bills at their price, uses at the average then.
        CrmWorkspaceContext::set($this->workspace->id);
        $events = (new InventoryAccounting())->events()->map(fn ($e) => [$e->kind, round($e->qty, 3), round($e->value, 2)])->values()->all();
        $this->assertSame([['bill', 100.0, 200.0], ['bill', 100.0, 100.0], ['job', -50.0, -75.0], ['bill', 50.0, 150.0], ['job', -100.0, -187.5]], $events);
        $value = (new InventoryAccounting())->itemsAsOf(Carbon::parse(self::TODAY)->endOfDay())->get($art->id);
        $this->assertMoney(100, $value['qty']);
        $this->assertMoney(187.5, $value['value']);
    }

    public function test_editing_a_bill_moves_its_stock_and_cannot_take_back_what_was_used(): void
    {
        $art = $this->newItem('Art Card');
        $bill = $this->bill([$this->stockLine($art, 100, 1)]);

        // Up: 150 in, the old 100 out.
        $this->updateBill($bill, [$this->stockLine($art, 150, 1)])->assertSessionHasNoErrors();
        $this->assertMoney(150, $art->fresh()->quantity);
        $refs = CrmInventoryMovement::where('purchase_invoice_id', $bill->id)->orderBy('id')->pluck('reference')->all();
        $this->assertSame(['Bought on '.$bill->number, 'Corrected on '.$bill->number, 'Replaced (bill edited) '.$bill->number], $refs);
        $this->assertBooks(['inventory' => 150, 'payable' => 150], 'edited up');

        $this->consume($art, 120, 'AMS-0103');

        // Down below what is left (150 − 40 = 110 out, only 30 left): blocked.
        $this->updateBill($bill, [$this->stockLine($art, 40, 1)])->assertSessionHasErrors('items');
        $this->assertStringContainsString('only 30 sheets are left', session('errors')->first('items'));
        // Removing the stock line altogether: blocked too.
        $this->updateBill($bill, [$this->costLine('Paper', 'Raw Material', 150, 1)])->assertSessionHasErrors('items');
        $this->assertMoney(30, $art->fresh()->quantity);
        $this->assertSame([$art->id => [150.0, 150.0]], $this->billStock($bill));

        // Down by what is still on hand: allowed.
        $this->updateBill($bill, [$this->stockLine($art, 140, 1)])->assertSessionHasNoErrors();
        $this->assertMoney(20, $art->fresh()->quantity);
        $this->assertBooks(['inventory' => 20, 'payable' => 140, 'used' => 120], 'edited down');

        // Price corrected after part of the stock was used: the bill comes out at its old price and goes back in at the new one.
        // QUESTION: the whole difference (140 × 0.10 = 14) lands on the 20 sheets still on hand (carried at 1.70 instead of 1.10);
        // the 120 sheets already used stay at 1.00. Totals agree (Inventory 34 + Cost of Sales 120 = bill 154).
        $this->updateBill($bill, [$this->stockLine($art, 140, 1.10)])->assertSessionHasNoErrors();
        $this->assertMoney(20, $art->fresh()->quantity);
        $this->assertBooks(['inventory' => 34, 'payable' => 154, 'used' => 120], 'price corrected');
    }

    public function test_a_bill_whose_stock_is_used_cannot_be_deleted_but_an_unused_one_can(): void
    {
        $art = $this->newItem('Art Card');
        $unused = $this->bill([$this->stockLine($art, 50, 2)]);
        $this->post(route('crm.purchase_invoices.payments.store', $unused->id), ['amount' => 20, 'paid_at' => self::TODAY, 'method' => 'Cash']);
        $this->assertBooks(['inventory' => 100, 'payable' => 80, 'cash' => -20], 'unused bill, part paid');

        $this->delete(route('crm.purchase_invoices.destroy', $unused->id))->assertRedirect(route('crm.purchase_invoices.index'))->assertSessionHas('success');
        $this->assertNull(CrmPurchaseInvoice::find($unused->id));
        $this->assertMoney(0, $art->fresh()->quantity);
        $out = CrmInventoryMovement::where('purchase_invoice_id', $unused->id)->orderByDesc('id')->first();
        $this->assertSame('out', $out->type);
        $this->assertSame('Bill removed '.$unused->number, $out->reference);
        $this->assertSame('Bill changed / removed', InventoryAccounting::booksLabel($out));
        $this->assertBooks(['inventory' => 0, 'payable' => 0, 'cash' => 0, 'profit' => 0], 'unused bill deleted');

        $used = $this->bill([$this->stockLine($art, 50, 2)]);
        $this->consume($art, 10, 'AMS-0104');
        $this->delete(route('crm.purchase_invoices.destroy', $used->id))->assertSessionHas('error');
        $this->assertStringContainsString($used->number.' cannot be deleted.', session('error'));
        $this->assertStringContainsString('this bill brought in 50 sheets but only 40 sheets are left', session('error'));
        $this->assertNotNull(CrmPurchaseInvoice::find($used->id));
        $this->assertCount(1, $used->fresh()->items);
        $this->assertMoney(40, $art->fresh()->quantity);
        $this->assertBooks(['inventory' => 80, 'payable' => 100, 'used' => 20], 'used bill kept');
    }

    public function test_stock_lines_need_a_bill_dated_on_or_after_the_cut_off(): void
    {
        $art = $this->newItem('Art Card');

        // The fresh workspace's books started today: yesterday's bill can't carry stock.
        $this->assertSame(self::TODAY, InventoryAccounting::from()->toDateString());
        $this->bill([$this->stockLine($art, 10, 1)], '2026-10-25', false)->assertSessionHasErrors('items');
        $this->assertStringContainsString('Stock lines are for bills dated 26 Oct 2026 or later', session('errors')->first('items'));
        $this->assertSame(0, CrmPurchaseInvoice::count());

        // With an earlier cut-off (1 Oct), a bill on 30 Sep is still refused, one on 1 Oct goes in.
        $this->startStockBooks('2026-10-01');
        $this->bill([$this->stockLine($art, 10, 1)], '2026-09-30', false)->assertSessionHasErrors('items');
        $this->assertStringContainsString('Stock lines are for bills dated 01 Oct 2026 or later', session('errors')->first('items'));
        $this->assertMoney(0, $art->fresh()->quantity);

        // The same old bill without a stock line is an ordinary cost.
        $old = $this->bill([$this->costLine('Paper', 'Raw Material', 10, 1)], '2026-09-30');
        $this->assertNotNull($old);
        $this->assertMoney(10, $this->plTotals()['cos']['Stock & Raw Material'] ?? 0);

        $onTheDay = $this->bill([$this->stockLine($art, 10, 1)], '2026-10-01');
        $this->assertMoney(10, $art->fresh()->quantity);
        $this->assertSame('2026-10-01', CrmInventoryMovement::where('purchase_invoice_id', $onTheDay->id)->first()->entry_date->toDateString(), 'stock is dated on the bill');

        // Moving the bill's date moves its stock with it.
        $this->updateBill($onTheDay, [$this->stockLine($art, 10, 1)], '2026-10-10')->assertSessionHasNoErrors();
        $this->assertSame(['2026-10-10'], CrmInventoryMovement::where('purchase_invoice_id', $onTheDay->id)->get()->map(fn ($m) => $m->entry_date->toDateString())->unique()->values()->all());
        $this->assertMoney(0, $this->books('2026-10-09')['inventory']);
        $this->assertBooks(['inventory' => 10, 'payable' => 20], 'bill on 10 Oct', '2026-10-10');
        // …but not to before the cut-off.
        $this->updateBill($onTheDay, [$this->stockLine($art, 10, 1)], '2026-09-30')->assertSessionHasErrors('items');

        // The create page tells the user the cut-off day.
        $this->get(route('crm.purchase_invoices.create'))->assertOk()->assertSee('For bills dated 01 Oct 2026 or later.');
    }

    /**
     * A stock bill can't be dated after today: its stock would be on hand at once while the books only take it in on
     * the bill date, so using it today would put Inventory below zero on today's balance sheet.
     */
    public function test_a_future_dated_stock_bill_never_makes_inventory_negative(): void
    {
        $art = $this->newItem('Art Card');
        $count = CrmPurchaseInvoice::count();
        $this->bill([$this->stockLine($art, 100, 1)], '2026-11-05', false)->assertSessionHasErrors('issue_date');
        $this->assertSame($count, CrmPurchaseInvoice::count(), 'the bill is not saved');
        $this->assertSame(0.0, (float) $art->fresh()->quantity, 'no stock came in');
        // Dated today it is accepted.
        $this->bill([$this->stockLine($art, 100, 1)], self::TODAY);
        $this->consume($art, 100, 'AMS-0900');

        $books = $this->books();
        $this->assertGreaterThanOrEqual(-0.005, $books['inventory'], 'Inventory at cost on '.self::TODAY.' must not be negative');
        $this->assertMoney(0, $books['balancing']);
    }

    /**
     * BUG (expected to fail): the inventory report's "Stock Movement (quantities)" table filters and dates movements by
     * created_at (OperationsReports.php:196 and :245), while its Stock Valuation and Inventory Account use the bill date
     * (entry_date). A bill dated 5 Oct but entered on 26 Oct is in the 1–10 Oct valuation (100 sheets) but missing from the
     * quantity table of the same report.
     */
    public function test_the_inventory_report_quantities_follow_the_bill_date_like_its_values(): void
    {
        $this->startStockBooks('2026-10-01');
        $art = $this->newItem('Art Card');
        $this->bill([$this->stockLine($art, 100, 1)], '2026-10-05');

        $report = $this->report('inventory', '2026-10-01', '2026-10-10');
        $valuation = $this->table($report, 'Stock Valuation')['rows'];
        $this->assertSame([[$art->name, 100.0, 100.0]], array_map(fn ($r) => [$r[0]['text'], round($r[3], 3), round($r[5], 2)], $valuation));
        $this->assertMoney(100, collect($report['cards'])->firstWhere('label', 'Bought on Bills')['value']);

        $movement = $this->table($report, 'Stock Movement')['rows'];
        $this->assertSame([[$art->name, 'sheets', 0.0, 100.0, 0.0, 0.0, 100.0]],
            array_map(fn ($r) => array_merge([$r[0]['text'], $r[1]], array_map(fn ($v) => round($v, 3) + 0.0, array_slice($r, 2))), $movement),
            'the quantity table must agree with the valuation of the same report');
    }

    /**
     * QUESTION (asserts current behaviour): a dearer bill removed after part of the item's stock was used. 100 @ 1 + 100 @ 5
     * (average 3), 50 used (150 to Cost of Sales), then the 100 @ 5 bill is deleted: it goes out at its own price (500), the
     * value would be −50, so it is clamped to 0 (InventoryAccounting.php:170). The 50 sheets still on hand (bought at 1.00)
     * are carried at 0 and Cost of Sales ends at 100 instead of 50 — totals agree with the remaining bill (100), the split
     * between Inventory and Cost of Sales does not ("the rest keeps its true average cost", InventoryService.php:19).
     */
    public function test_removing_a_dearer_bill_after_part_of_the_stock_was_used(): void
    {
        $art = $this->newItem('Art Card');
        $this->bill([$this->stockLine($art, 100, 1)]);
        $dear = $this->bill([$this->stockLine($art, 100, 5)]);
        $this->consume($art, 50, 'AMS-0901');
        $this->assertBooks(['inventory' => 450, 'used' => 150, 'payable' => 600], 'before delete');

        $this->delete(route('crm.purchase_invoices.destroy', $dear->id))->assertSessionHas('success');
        $this->assertMoney(50, $art->fresh()->quantity);
        $this->assertMoney(0, $art->fresh()->unit_cost);
        $this->assertBooks(['inventory' => 0, 'used' => 100, 'payable' => 100], 'dear bill deleted');
    }

    public function test_a_stock_line_must_be_an_item_of_this_workspace(): void
    {
        $other = CrmWorkspace::create(['name' => 'Other Test', 'slug' => 'other-test-'.uniqid(), 'is_active' => true]);
        $foreign = CrmInventoryItem::withoutGlobalScopes()->create(['workspace_id' => $other->id, 'name' => 'Foreign Paper '.$this->tag, 'unit' => 'sheets', 'quantity' => 500, 'unit_cost' => 1]);

        $this->bill([$this->stockLine($foreign, 10, 1)], self::TODAY, false)->assertSessionHasErrors('items');
        $this->assertStringContainsString('no longer exists on the Inventory page', session('errors')->first('items'));
        $this->assertMoney(500, CrmInventoryItem::withoutGlobalScopes()->find($foreign->id)->quantity);
        $this->get(route('crm.inventory.movements', $foreign->id))->assertNotFound();
        $this->post(route('crm.inventory.consume', $foreign->id), ['quantity' => 1])->assertNotFound();
    }

    // ───────────────────────────── Inventory page operations ─────────────────────────────

    public function test_stock_on_hand_at_the_cut_off_is_opening_stock_and_its_use_is_cost_of_sales(): void
    {
        // Stock bought in September (expensed then), on hand when the books start on 1 Oct.
        Carbon::setTestNow('2026-09-15 09:00:00');
        $old = $this->newItem('Duplex Board', ['quantity' => 3000, 'unit_cost' => 1.16]);
        $before = CrmInventoryMovement::where('inventory_item_id', $old->id)->firstOrFail();
        Carbon::setTestNow(self::TODAY.' 10:00:00');
        $this->startStockBooks('2026-10-01', [$old->id => [3000, 1.16]], $before->id);

        $this->assertFalse(InventoryAccounting::isBooked($before));
        $this->assertSame('Before 01 Oct 2026 (not in books)', InventoryAccounting::booksLabel($before));

        $september = $this->books('2026-09-30');
        $this->assertNull($september['inventory'], 'no Inventory line before the cut-off');
        $this->assertNull($september['opening_stock']);
        $this->assertMoney(0, $september['balancing']);
        $this->assertBooks(['inventory' => 3480, 'opening_stock' => 3480, 'used' => 0], 'cut-off day', '2026-10-01');

        $this->consume($old, 1000, 'AMS-0100')->assertSessionHas('success');
        $this->assertBooks(['inventory' => 2320, 'opening_stock' => 3480, 'used' => 1160, 'profit' => -1160], 'old stock used');

        // The pre-cut-off movement is not a booked one, but the stock on hand still blocks deleting the item.
        $this->delete(route('crm.inventory.destroy', $old->id))->assertSessionHas('error');
        $this->assertNotNull(CrmInventoryItem::find($old->id));

        $page = $this->get(route('crm.inventory.movements', $old->id))->assertOk();
        $page->assertSee('Before 01 Oct 2026 (not in books)')->assertSee('Cost of Sales (materials used)')->assertSee('2,320.00');

        $report = $this->report('inventory', '2026-10-01', self::TODAY);
        $account = $this->table($report, 'Inventory Account')['rows'];
        $this->assertSame('Opening value', $account[0]['cells'][0]);
        $this->assertMoney(0, $account[0]['cells'][1]);
        $this->assertSame([['Stock on hand on 01 Oct 2026 (taken onto the books as Opening Stock)', 3480.0], ['Used on jobs → Cost of Sales', -1160.0]],
            array_map(fn ($r) => [$r[0], round($r[1], 2)], array_slice($account, 1, -1)));
        $this->assertMoney(2320, end($account)['cells'][1]);

        $equity = $this->report('owners-equity', '2026-10-01', self::TODAY);
        $row = collect($equity['tables'][0]['rows'])->first(fn ($r) => is_array($r[0] ?? null) && $r[0]['text'] === 'Opening Stock (brought onto books)');
        $this->assertNotNull($row, "Owner's Equity shows the opening stock");
        $this->assertMoney(3480, $row[6]);
    }

    public function test_adjustments_post_to_stock_loss_and_cannot_go_below_zero(): void
    {
        $art = $this->newItem('Art Card');
        $this->bill([$this->stockLine($art, 100, 2)]);

        $this->adjust($art, ['reason' => 'damage', 'quantity_lost' => 10])->assertSessionHas('success');
        $this->assertBooks(['inventory' => 180, 'loss' => 20], 'damaged');
        $this->adjust($art, ['reason' => 'expired', 'quantity_lost' => 5])->assertSessionHas('success');
        $this->assertBooks(['inventory' => 170, 'loss' => 30], 'expired');
        $this->adjust($art, ['reason' => 'count', 'new_quantity' => 80])->assertSessionHas('success');
        $this->assertBooks(['inventory' => 160, 'loss' => 40], 'count loss');
        $this->adjust($art, ['reason' => 'count', 'new_quantity' => 90])->assertSessionHas('success');
        $this->assertBooks(['inventory' => 180, 'loss' => 20], 'count gain');
        $this->assertMoney(90, $art->fresh()->quantity);

        $moves = CrmInventoryMovement::where('inventory_item_id', $art->id)->where('type', 'adjust')->orderBy('id')->get();
        $this->assertSame(['damage', 'expired', 'count', 'count'], $moves->pluck('reason')->all());
        $this->assertSame(['Damaged (−)', 'Expired / obsolete (−)', 'Stock count correction (−)', 'Stock count correction (+)'], $moves->pluck('reference')->all());
        $this->assertSame('Stock loss & wastage — Damaged', InventoryAccounting::booksLabel($moves[0]));

        $count = CrmInventoryMovement::count();
        $this->adjust($art, ['reason' => 'wastage', 'quantity_lost' => 91])->assertSessionHas('error', 'Only 90 sheets in stock.');
        $this->adjust($art, ['reason' => 'count', 'new_quantity' => 90])->assertSessionHas('error', 'Quantity unchanged.');
        $this->adjust($art, ['reason' => 'count'])->assertSessionHasErrors('new_quantity');
        $this->adjust($art, ['reason' => 'wastage'])->assertSessionHasErrors('quantity_lost');
        $this->adjust($art, ['reason' => 'theft', 'quantity_lost' => 1])->assertSessionHasErrors('reason');
        $this->adjust($art, ['reason' => 'count', 'new_quantity' => -1])->assertSessionHasErrors('new_quantity');
        $this->assertSame($count, CrmInventoryMovement::count(), 'refused adjustments write nothing');
        $this->assertMoney(90, $art->fresh()->quantity);

        // Counting down to zero leaves nothing in the books.
        $this->adjust($art, ['reason' => 'count', 'new_quantity' => 0])->assertSessionHas('success');
        $this->assertBooks(['inventory' => 0, 'loss' => 200, 'payable' => 200], 'counted to zero');
    }

    public function test_consuming_needs_enough_stock_and_goes_to_cost_of_sales(): void
    {
        $art = $this->newItem('Art Card');
        $this->bill([$this->stockLine($art, 100, 0.5)]);

        $this->consume($art, 100.5, 'AMS-0105')->assertSessionHas('error', 'Not enough stock: only 100 sheets available.');
        $this->assertMoney(100, $art->fresh()->quantity);

        $this->consume($art, 100, 'AMS-0105')->assertSessionHas('success');
        $move = CrmInventoryMovement::where('inventory_item_id', $art->id)->orderByDesc('id')->first();
        $this->assertSame(['out', 'job', 'AMS-0105', 'Used on AMS-0105'], [$move->type, $move->reason, $move->job_number, $move->reference]);
        $this->assertSame('Cost of Sales (materials used)', InventoryAccounting::booksLabel($move));
        $this->assertBooks(['inventory' => 0, 'used' => 50], 'all used');

        $entries = (new FinancialReportService(Carbon::parse('2026-01-01'), Carbon::parse(self::TODAY)))->entries('cos', InventoryAccounting::USED);
        $this->assertCount(1, $entries['tables'][0]['rows']);
        $this->assertSame(['Inventory', InventoryAccounting::USED, 50.0], array_slice($entries['tables'][0]['rows'][0], 3));

        $jobs = collect($this->report('jobs')['tables'][0]['rows'])->keyBy(fn ($r) => $r[0]['text']);
        $this->assertMoney(50, $jobs['AMS-0105'][4], 'Job Profitability shows the stock used');
    }

    public function test_expensed_stock_in_moves_the_cost_into_stock_and_opening_stock_is_equity(): void
    {
        $board = $this->newItem('Kraft Board');

        $this->post(route('crm.inventory.stock_in', $board->id), ['quantity' => 100, 'unit_cost' => 2, 'reason' => 'expensed', 'reference' => 'VP-77'])->assertSessionHas('success');
        $this->assertBooks(['inventory' => 200, 'moved_in' => -200, 'opening_stock' => 0], 'expensed');
        $move = CrmInventoryMovement::where('inventory_item_id', $board->id)->orderByDesc('id')->first();
        $this->assertSame('Moved from expenses into stock', InventoryAccounting::booksLabel($move));

        $this->post(route('crm.inventory.stock_in', $board->id), ['quantity' => 10, 'unit_cost' => 2, 'reason' => 'opening'])->assertSessionHas('success');
        $this->assertBooks(['inventory' => 220, 'moved_in' => -200, 'opening_stock' => 20], 'opening stock in');

        // No reason given → it is a bill already in expenses; a "bill" stock-in is not allowed here (that is a purchase invoice).
        $this->post(route('crm.inventory.stock_in', $board->id), ['quantity' => 5])->assertSessionHas('success');
        $this->assertSame('expensed', CrmInventoryMovement::where('inventory_item_id', $board->id)->orderByDesc('id')->value('reason'));
        $this->assertBooks(['inventory' => 230, 'moved_in' => -210], 'default reason at average cost');
        $this->post(route('crm.inventory.stock_in', $board->id), ['quantity' => 5, 'reason' => 'bill'])->assertSessionHasErrors('reason');

        $ink = $this->newItem('Process Ink', ['category' => 'Ink', 'unit' => 'kg', 'quantity' => 10, 'unit_cost' => 5]);
        $opening = CrmInventoryMovement::where('inventory_item_id', $ink->id)->firstOrFail();
        $this->assertSame(['in', 'opening', 'Opening stock'], [$opening->type, $opening->reason, $opening->reference]);
        $this->assertSame('Opening stock (equity)', InventoryAccounting::booksLabel($opening));
        $this->assertBooks(['inventory' => 280, 'opening_stock' => 70, 'profit' => 210], 'new item with opening stock');

        $pl = $this->report('profit-loss');
        $this->assertContains(InventoryAccounting::MOVED_IN, $this->plCategories($pl, 'Cost of Sales'));
    }

    public function test_an_item_with_stock_or_booked_movements_cannot_be_deleted(): void
    {
        $blank = $this->newItem('Never Used');
        $this->delete(route('crm.inventory.destroy', $blank->id))->assertSessionHas('success', 'Inventory item deleted.');
        $this->assertNull(CrmInventoryItem::find($blank->id));

        $art = $this->newItem('Art Card');
        $this->bill([$this->stockLine($art, 10, 1)]);
        $this->delete(route('crm.inventory.destroy', $art->id))->assertSessionHas('error');
        $this->assertStringContainsString('has stock entries in the books, so it cannot be deleted', session('error'));

        $this->consume($art, 10, 'AMS-0106');
        $this->assertMoney(0, $art->fresh()->quantity);
        $this->delete(route('crm.inventory.destroy', $art->id))->assertSessionHas('error');
        $this->assertNotNull(CrmInventoryItem::find($art->id), 'used-up stock keeps its history');

        $this->signIn($this->makeUser('sales'));
        $this->delete(route('crm.inventory.destroy', $art->id))->assertForbidden();
    }

    public function test_an_items_cost_is_locked_while_it_has_stock(): void
    {
        $art = $this->newItem('Art Card', ['unit_cost' => 0.4]);
        $this->bill([$this->stockLine($art, 100, 0.5)]);
        $this->put(route('crm.inventory.update', $art->id), ['name' => 'Art Card Gloss '.$this->tag, 'unit' => 'sheets', 'unit_cost' => 9.99])->assertSessionHas('success');
        $art->refresh();
        $this->assertSame('Art Card Gloss '.$this->tag, $art->name);
        $this->assertMoney(0.5, $art->unit_cost, 'the average of what was paid stays');
        $this->assertBooks(['inventory' => 50], 'cost edit ignored');

        $this->consume($art, 100, 'AMS-0107');
        $this->put(route('crm.inventory.update', $art->id), ['name' => $art->name, 'unit' => 'sheets', 'unit_cost' => 0.7])->assertSessionHas('success');
        $this->assertMoney(0.7, $art->fresh()->unit_cost, 'with no stock the cost can be set');

        $this->signIn($this->makeUser('sales'));
        $this->put(route('crm.inventory.update', $art->id), ['name' => 'X', 'unit' => 'sheets'])->assertForbidden();
    }

    // ───────────────────────────── Pages & reports ─────────────────────────────

    public function test_the_item_view_page_shows_its_bills_jobs_and_history(): void
    {
        $art = $this->newItem('Art Card', ['paper_size' => '20x30']);
        $bill = $this->bill([$this->stockLine($art, 1000, 0.5)], self::TODAY, true, ['reference' => 'INV-'.$this->tag]);
        $this->consume($art, 300, 'AMS-0099');
        $this->consume($art, 100, 'AMS-0100');
        $this->adjust($art, ['reason' => 'wastage', 'quantity_lost' => 50, 'note' => 'make-ready']);

        $page = $this->get(route('crm.inventory.movements', $art->id))->assertOk();
        $page->assertSee($art->name)
            ->assertSee('Bought on Purchase Invoices')->assertSee($bill->number)->assertSee($this->vendor->name)->assertSee('1 bill(s)')
            ->assertSee('2 job(s)')->assertSee('AMS-0099')->assertSee('AMS-0100')->assertSee('150.00')
            ->assertSee('Inventory (bought on bill)')->assertSee('Cost of Sales (materials used)')->assertSee('Stock loss & wastage — Wastage (production)')
            ->assertSee('make-ready')->assertSee('Bought on '.$bill->number)->assertSee($this->admin->name)
            ->assertSee('275.00')   // value in the books: 550 sheets @ 0.50
            ->assertSee('In 1000 · wastage / adjusted 50')
            ->assertSee(route('crm.purchase_invoices.create', ['stock' => $art->id]), false);

        $this->get(route('crm.inventory.movements', $this->newItem('Empty Item')->id))->assertOk()
            ->assertSee('No purchase invoice has this item yet.')->assertSee('Not used on any job yet.')->assertSee('No movements yet.');
    }

    public function test_the_inventory_report_cards_and_tables(): void
    {
        $art = $this->newItem('Art Card');
        $this->bill([$this->stockLine($art, 1000, 0.5)]);
        $this->consume($art, 300, 'AMS-0099');
        $this->consume($art, 100, 'AMS-0100');
        $this->adjust($art, ['reason' => 'wastage', 'quantity_lost' => 50]);
        $ink = $this->newItem('Process Ink', ['category' => 'Ink', 'unit' => 'kg', 'quantity' => 10, 'unit_cost' => 5, 'reorder_level' => 20]);

        $report = $this->report('inventory');
        $cards = collect($report['cards'])->keyBy('label');
        $this->assertMoney(325, $cards['Stock Value']['value']);
        $this->assertStringContainsString('in the books', $cards['Stock Value']['note']);
        $this->assertMoney(500, $cards['Bought on Bills']['value']);
        $this->assertMoney(200, $cards['Used on Jobs']['value']);
        $this->assertMoney(25, $cards['Stock Loss']['value']);
        $this->assertSame(1, $cards['Low Stock Now']['value']);

        $valuation = $this->table($report, 'Stock Valuation');
        $this->assertSame([[$art->name, 'Paper', 'sheets', 550.0, 0.5, 275.0], [$ink->name, 'Ink', 'kg', 10.0, 5.0, 50.0]],
            array_map(fn ($r) => [$r[0]['text'], $r[1], $r[2], round($r[3], 3), round($r[4], 4), round($r[5], 2)], $valuation['rows']));
        $this->assertSame(route('crm.inventory.movements', $art->id), $valuation['rows'][0][0]['url']);
        $this->assertMoney(325, end($valuation['footer']));

        $account = $this->table($report, 'Inventory Account')['rows'];
        $this->assertSame([['Bought on purchase invoices', 500.0], ['Opening stock added without a bill', 50.0], ['Used on jobs → Cost of Sales', -200.0],
            ['Wastage, damage & count differences → Stock Loss', -25.0]], array_map(fn ($r) => [$r[0], round($r[1], 2)], array_slice($account, 1, -1)));
        $this->assertMoney(0, $account[0]['cells'][1]);
        $this->assertMoney(325, end($account)['cells'][1]);

        $movement = $this->table($report, 'Stock Movement');
        $this->assertSame([[$art->name, 'sheets', 0.0, 1000.0, 400.0, -50.0, 550.0], [$ink->name, 'kg', 0.0, 10.0, 0.0, 0.0, 10.0]],
            array_map(fn ($r) => array_merge([$r[0]['text'], $r[1]], array_map(fn ($v) => round($v, 3) + 0.0, array_slice($r, 2))), $movement['rows']));

        $jobs = $this->table($report, 'Consumption by Job');
        $this->assertSame([['AMS-0099', $art->name, 1, 150.0], ['AMS-0100', $art->name, 1, 50.0]],
            array_map(fn ($r) => [$r[0], $r[1], $r[2], round($r[3], 2)], $jobs['rows']));
        $this->assertMoney(200, end($jobs['footer']));

        $this->get(route('crm.financial_reports.index', ['report' => 'inventory']))->assertOk()->assertSee('Consumption by Job')->assertSee('AMS-0099');
        $this->get(route('crm.financial_reports.export', ['report' => 'inventory']))->assertOk();

        // The Inventory page's Stock Value tile is the same books value.
        $this->get(route('crm.inventory.index'))->assertOk()->assertSee('325.00')->assertSee('Stock is in the books since 26 Oct 2026.');
    }

    public function test_the_purchase_invoice_form_preselects_and_shows_stock_items(): void
    {
        $art = $this->newItem('Art Card', ['paper_size' => '20x30', 'gsm' => '300']);
        $label = $art->name.' 20x30 300gsm';

        $this->get(route('crm.purchase_invoices.create', ['stock' => $art->id]))->assertOk()
            ->assertSee('Add Stock Item (Inventory)')->assertSee($label)
            ->assertSee('var id = '.$art->id.';', false);
        $this->get(route('crm.purchase_invoices.create'))->assertOk()->assertDontSee('var id = '.$art->id.';', false);

        $bill = $this->bill([$this->stockLine($art, 100, 0.5)]);
        $this->get(route('crm.purchase_invoices.edit', $bill->id))->assertOk()->assertSee('data-stock="'.$art->id.'"', false)->assertSee($label);
        $this->get(route('crm.purchase_invoices.show', $bill->id))->assertOk()->assertSee($label);
    }

    // ───────────────────────────── Helpers ─────────────────────────────

    /** The stock-in-the-books config is cached per workspace id for the whole PHP process. */
    private function forgetStockConfig(): void
    {
        $property = new \ReflectionProperty(InventoryAccounting::class, 'configs');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    /** Start the books on another day (replaces the workspace's cut-off config). */
    private function startStockBooks(string $from, array $opening = [], ?int $afterId = null): void
    {
        DB::table('crm_settings')->where('workspace_id', $this->workspace->id)->where('key', InventoryAccounting::KEY)->delete();
        DB::table('crm_settings')->insert(['workspace_id' => $this->workspace->id, 'key' => InventoryAccounting::KEY, 'created_at' => now(), 'updated_at' => now(),
            'value' => json_encode(['from' => $from, 'after_id' => $afterId ?? (int) DB::table('crm_inventory_movements')->max('id'), 'opening' => (object) $opening])]);
        $this->forgetStockConfig();
    }

    /** The stock a bill has put in, rounded: [item id => [qty, value]]. */
    private function billStock(CrmPurchaseInvoice $bill): array
    {
        return array_map(fn ($s) => [round($s[0], 3), round($s[1], 2)], InventoryAccounting::billStock($bill->id));
    }

    private function newItem(string $name, array $fields = []): CrmInventoryItem
    {
        $name .= ' '.$this->tag;
        $this->post(route('crm.inventory.store'), array_merge(['name' => $name, 'category' => 'Paper', 'unit' => 'sheets', 'quantity' => 0], $fields))
            ->assertSessionHasNoErrors()->assertRedirect(route('crm.inventory.index'));

        return CrmInventoryItem::where('name', $name)->firstOrFail();
    }

    private function stockLine(CrmInventoryItem $item, float $qty, float $price): array
    {
        return ['inventory_item_id' => $item->id, 'name' => 'stock', 'category' => 'stock', 'quantity' => $qty, 'unit_price' => $price];
    }

    private function costLine(string $name, string $category, float $qty, float $price): array
    {
        return ['name' => $name, 'category' => $category, 'quantity' => $qty, 'unit_price' => $price];
    }

    /** Post a purchase invoice; with $expectSaved it returns the saved invoice, otherwise the response. */
    private function bill(array $lines, string $date = self::TODAY, bool $expectSaved = true, array $fields = [])
    {
        $count = CrmPurchaseInvoice::count();
        $response = $this->post(route('crm.purchase_invoices.store'), array_merge([
            'vendor_id' => $this->vendor->id, 'issue_date' => $date, 'currency' => 'AED', 'reference' => 'T-'.uniqid(), 'items' => $lines,
        ], $fields));
        if (!$expectSaved) return $response;

        $response->assertSessionHasNoErrors();
        $this->assertSame($count + 1, CrmPurchaseInvoice::count(), 'bill saved');

        return CrmPurchaseInvoice::with('items')->orderByDesc('id')->firstOrFail();
    }

    private function updateBill(CrmPurchaseInvoice $bill, array $lines, string $date = null)
    {
        return $this->put(route('crm.purchase_invoices.update', $bill->id), [
            'vendor_id' => $this->vendor->id, 'issue_date' => $date ?: $bill->issue_date->toDateString(), 'currency' => 'AED', 'reference' => $bill->reference, 'items' => $lines,
        ]);
    }

    private function consume(CrmInventoryItem $item, float $qty, string $job)
    {
        return $this->post(route('crm.inventory.consume', $item->id), ['quantity' => $qty, 'job_number' => $job]);
    }

    private function adjust(CrmInventoryItem $item, array $data)
    {
        return $this->post(route('crm.inventory.adjust', $item->id), $data);
    }

    /** Balance sheet lines by kind as of a day: kind => [label, amount, url, kind]. */
    private function sheetLines(string $asOf = self::TODAY): array
    {
        CrmWorkspaceContext::set($this->workspace->id);
        $end = Carbon::parse($asOf)->endOfDay();
        $sheet = (new FinancialReportService(Carbon::parse('2026-01-01'), $end))->balanceSheetAsOf($end);

        return collect(array_merge($sheet['assets'], $sheet['liabilities'], $sheet['equity']))->keyBy(3)->all();
    }

    private function plTotals(string $to = self::TODAY): array
    {
        CrmWorkspaceContext::set($this->workspace->id);

        return (new FinancialReportService(Carbon::parse('2026-01-01'), Carbon::parse($to)))->plTotalsFor(Carbon::parse('2026-01-01'), Carbon::parse($to)->endOfDay());
    }

    /** The figures the stock touches, as of a day (null = no such line on the balance sheet). */
    private function books(string $asOf = self::TODAY): array
    {
        CrmWorkspaceContext::set($this->workspace->id);
        $end = Carbon::parse($asOf)->endOfDay();
        $service = new FinancialReportService(Carbon::parse('2026-01-01'), $end);
        $sheet = $service->balanceSheetAsOf($end);
        $lines = collect(array_merge($sheet['assets'], $sheet['liabilities'], $sheet['equity']));
        $line = fn (string $kind) => ($found = $lines->firstWhere(3, $kind)) ? (float) $found[1] : null;
        $pl = $service->plTotalsFor(Carbon::parse('2026-01-01'), $end);
        $trial = collect($this->report('trial-balance', '2026-01-01', $asOf)['cards'])->keyBy('label');

        return [
            'cash' => (float) $lines->where(3, 'cash')->sum(1),
            'inventory' => $line('inventory'),
            'opening_stock' => $line('opening_stock'),
            'payable' => $line('invoice'),
            'profit' => $line('profit'),
            'balancing' => $line('balancing'),
            'used' => (float) ($pl['cos'][InventoryAccounting::USED] ?? 0),
            'moved_in' => (float) ($pl['cos'][InventoryAccounting::MOVED_IN] ?? 0),
            'loss' => (float) ($pl['opex'][InventoryAccounting::LOSS] ?? 0),
            'consumables' => (float) ($pl['opex']['Consumables'] ?? 0),
            'stock_lines' => (float) (($pl['cos'][CrmPurchaseInvoiceItem::STOCK_CATEGORY] ?? 0) + ($pl['opex'][CrmPurchaseInvoiceItem::STOCK_CATEGORY] ?? 0)),
            'tb_difference' => (float) $trial['Difference']['value'],
        ];
    }

    /** Assert some book figures, and that the balancing figure did not move (0) and the trial balance agrees. */
    private function assertBooks(array $expected, string $step, string $asOf = self::TODAY): void
    {
        $books = $this->books($asOf);
        foreach ($expected as $key => $value) {
            $this->assertNotNull($books[$key], "$step: $key line is on the balance sheet");
            $this->assertMoney($value, $books[$key], "$step: $key");
        }
        $this->assertMoney(0, $books['balancing'], "$step: balancing figure must not change");
        $this->assertMoney(0, $books['tb_difference'], "$step: trial balance difference");
    }

    private function assertBalanced(string $step): void
    {
        $this->assertBooks([], $step);
    }

    /** P&L category names in a section (without the subtotal). */
    private function plCategories(array $pl, string $section): array
    {
        $names = [];
        $in = false;
        foreach ($pl['tables'][0]['rows'] as $row) {
            if (($row['_style'] ?? null) === 'section') { $in = $row['cells'][0] === $section; continue; }
            if (!$in || isset($row['_style'])) continue;
            if (is_array($row[0]) && abs((float) $row[1]) > 0.004) $names[] = $row[0]['text'];
        }

        return $names;
    }

    private function table(array $report, string $titleStart): array
    {
        foreach ($report['tables'] as $table) {
            if (str_starts_with($table['title'], $titleStart)) return $table;
        }
        $this->fail("No table '$titleStart' in the report");
    }
}
