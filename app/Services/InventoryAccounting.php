<?php

namespace App\Services;

use App\CrmInventoryItem;
use App\CrmInventoryMovement;
use App\CrmPurchaseInvoice;
use App\Support\CrmWorkspaceContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Perpetual inventory (IAS 2) in the books, from a cut-off day onwards.
 *
 * Every stock movement after the cut-off means something for the accounts (its "reason"):
 *   bill      bought on a purchase invoice → Inventory (that bill line is not a cost)
 *   opening   stock that was never in the books (a new item's opening qty) → Inventory + Opening Stock (equity)
 *   expensed  bought on a bill that is already a cost (Vendors tab, demand request) → moved from costs into Inventory
 *   job       used on a job (or returned from it) → Cost of Sales "Materials Used"
 *   wastage / damage / expired / count → "Stock Loss & Wastage" (a count that finds more stock is a gain)
 * Stock on hand on the cut-off day was bought on old bills that were expensed when bought: it is taken onto the books
 * once as opening stock (equity) and from then on its use is Cost of Sales. Movements before the cut-off are not booked
 * and old bills stay as they were.
 *
 * Values are replayed per item at moving-average cost, so Inventory in the balance sheet, Cost of Sales in the
 * P&L and the bills always agree.
 */
class InventoryAccounting
{
    const KEY = 'inventory_perpetual';

    const USED = 'Materials Used (from Stock)';
    const MOVED_IN = 'Purchases Moved Into Stock';
    const LOSS = 'Stock Loss & Wastage';

    /** Why stock was adjusted (all post to Stock Loss & Wastage). */
    const LOSS_REASONS = ['wastage' => 'Wastage (production)', 'damage' => 'Damaged', 'expired' => 'Expired / obsolete', 'count' => 'Stock count correction'];

    /** Where stock added on the Inventory page comes from. */
    const STOCK_IN_SOURCES = [
        'expensed' => 'Bill already recorded as an expense (Vendors tab / Demand Request)',
        'opening' => 'Existing stock, not in the books yet (opening stock)',
    ];

    protected static array $configs = [];

    protected ?Collection $events = null;

    /** Start perpetual inventory for a workspace (once): the cut-off is today, the stock on hand now is the opening stock. */
    public static function start(int $workspaceId): array
    {
        $saved = DB::table('crm_settings')->where('workspace_id', $workspaceId)->where('key', self::KEY)->value('value');
        if ($saved) return json_decode($saved, true);

        $items = DB::table('crm_inventory_items')->where('workspace_id', $workspaceId)->get(['id', 'quantity', 'unit_cost']);
        $config = [
            'from' => now()->toDateString(),
            'after_id' => (int) DB::table('crm_inventory_movements')->max('id'),   // ids are global: anything newer is booked
            'opening' => $items->filter(fn ($i) => abs((float) $i->quantity) > 0.0005)
                ->mapWithKeys(fn ($i) => [$i->id => [(float) $i->quantity, (float) ($i->unit_cost ?? 0)]])->all(),
        ];
        DB::table('crm_settings')->insert(['workspace_id' => $workspaceId, 'key' => self::KEY, 'value' => json_encode($config),
            'created_at' => now(), 'updated_at' => now()]);

        return $config;
    }

    /** ['from' => cut-off day, 'after_id' => last movement before it, 'opening' => [item id => [qty, unit cost]]]. */
    public static function config(): array
    {
        $workspaceId = (int) CrmWorkspaceContext::id();
        if (!$workspaceId) return ['from' => now()->toDateString(), 'after_id' => PHP_INT_MAX, 'opening' => []];

        return self::$configs[$workspaceId] ??= self::start($workspaceId);
    }

    public static function from(): Carbon
    {
        return Carbon::parse(self::config()['from'])->startOfDay();
    }

    /** The day a movement counts in the books: a bill's stock on the bill date, anything else when it was entered. */
    public static function dateOf(CrmInventoryMovement $m): Carbon
    {
        return $m->entry_date ? Carbon::parse($m->entry_date)->startOfDay() : $m->created_at->copy();
    }

    public static function isBooked(CrmInventoryMovement $m): bool
    {
        return $m->id > self::config()['after_id'];
    }

    /** What a movement means for the books: its saved reason, else worked out from how it was made. */
    public static function reasonOf(CrmInventoryMovement $m): string
    {
        if ($m->reason) return $m->reason;
        if ($m->purchase_invoice_id) return 'bill';
        if ($m->type === 'in' && $m->reference === 'Opening stock') return 'opening';
        if ($m->job_id || trim((string) $m->job_number) !== '') return 'job';
        if ($m->type === 'adjust') return 'count';

        return $m->type === 'in' ? 'expensed' : 'job';
    }

    /** Where a movement went in the books, for the stock history page. */
    public static function booksLabel(CrmInventoryMovement $m): string
    {
        if (!self::isBooked($m)) return 'Before ' . self::from()->format('d M Y') . ' (not in books)';
        $reason = self::reasonOf($m);
        if ($reason === 'bill') $label = $m->type === 'out' ? 'Bill changed / removed' : 'Inventory (bought on bill)';
        elseif ($reason === 'opening') $label = 'Opening stock (equity)';
        elseif ($reason === 'expensed') $label = $m->type === 'out' ? 'Back to expenses' : 'Moved from expenses into stock';
        elseif ($reason === 'job') $label = $m->type === 'out' ? 'Cost of Sales (materials used)' : 'Returned from job';
        else $label = 'Stock loss & wastage' . (isset(self::LOSS_REASONS[$reason]) ? ' — ' . self::LOSS_REASONS[$reason] : '');

        return $m->reversal_of_id ? 'Reversed: ' . $label : $label;
    }

    /**
     * Why a stock entry can't be reverted from the stock history, or null when it can. Entries made from a bill, a job
     * card or a demand request are changed there (reverting here would leave that page out of step), and entries from
     * before stock was in the books are part of the opening stock.
     */
    public static function revertProblem(CrmInventoryMovement $m, bool $alreadyReverted): ?string
    {
        $reference = (string) $m->reference;
        if ($m->reversal_of_id) return 'This entry is itself a reversal.';
        if ($alreadyReverted) return 'This entry was already reverted.';
        if ($m->purchase_invoice_id || self::reasonOf($m) === 'bill') return 'This stock came from a purchase invoice: change or remove the line on that bill instead.';
        if (str_starts_with($reference, 'Demand #')) return 'This entry came from a demand request: undo it on that demand request instead.';
        if (str_starts_with($reference, 'Used on job ') || str_starts_with($reference, 'Returned from job ')) return 'This entry came from a job card: change its materials on the job card instead.';
        if (!self::isBooked($m)) return 'This entry is from before stock was in the books (' . self::from()->format('d M Y') . ') and is part of the opening stock.';

        return null;
    }

    /** "Art Card 20x30 300gsm": the name plus size and GSM when the name doesn't already say them. */
    public static function itemLabel(CrmInventoryItem $item): string
    {
        $label = trim((string) $item->name);
        $has = fn ($part) => stripos(str_replace(' ', '', $label), str_replace(' ', '', $part)) !== false;
        $size = trim((string) $item->paper_size);
        if ($size !== '' && !$has($size)) $label .= ' ' . $size;
        $gsm = trim((string) $item->gsm);
        if ($gsm !== '' && is_numeric($gsm)) $gsm .= 'gsm';
        if ($gsm !== '' && !$has($gsm)) $label .= ' ' . $gsm;

        return $label;
    }

    /**
     * Every booked event, replayed per item at moving-average cost:
     * (object) [date, item, movement, kind, qty, value] where value is the change in the Inventory account and kind is
     * start (stock on hand at the cut-off) | bill | opening | expensed | job | loss | correction (stock at or below zero
     * can't carry value: what is left over was used, so it is Cost of Sales).
     */
    public function events(): Collection
    {
        if ($this->events !== null) return $this->events;

        $config = self::config();
        $from = Carbon::parse($config['from'])->startOfDay();
        $items = CrmInventoryItem::orderBy('id')->get()->keyBy('id');
        $moves = CrmInventoryMovement::whereIn('inventory_item_id', $items->keys())->orderBy('id')->get()->groupBy('inventory_item_id');
        $event = fn ($date, $item, $m, $kind, $qty, $value) => (object) ['date' => $date, 'item' => $item, 'movement' => $m, 'kind' => $kind, 'qty' => $qty, 'value' => $value];

        $events = collect();
        foreach ($items as $id => $item) {
            [$qty, $cost] = $config['opening'][$id] ?? [0.0, 0.0];
            $qty = (float) $qty;
            $value = $qty > 0 ? $qty * (float) $cost : 0.0;
            if (abs($qty) > 0.0005) $events->push($event($from, $item, null, 'start', $qty, $value));

            $balance = 0.0;   // balance before each movement, for the direction of adjustments
            foreach ($moves[$id] ?? [] as $m) {
                $signed = $m->type === 'adjust' ? (float) $m->balance_after - $balance : ($m->type === 'out' ? -(float) $m->quantity : (float) $m->quantity);
                $balance = (float) $m->balance_after;
                if ($m->id <= $config['after_id']) continue;

                $reason = self::reasonOf($m);
                $kind = in_array($reason, ['bill', 'opening', 'expensed', 'job'], true) ? $reason : 'loss';
                $date = self::dateOf($m);
                $average = $qty > 0.0005 ? $value / $qty : 0.0;
                // Stock comes in at its own price; it goes out at the average, except a bill taken back out, or an entry
                // reverted, at its own price (so the reversal undoes exactly what the entry booked).
                $unit = ($signed > 0 || $kind === 'bill' || $m->reversal_of_id) && $m->unit_cost !== null ? (float) $m->unit_cost : $average;
                $change = $signed * $unit;
                $events->push($event($date, $item, $m, $kind, $signed, $change));

                $newQty = $qty + $signed;
                $value += $change;
                $target = null;
                if ($newQty <= 0.0005) $target = 0.0;                                   // nothing left, nothing to carry
                elseif ($qty < -0.0005 && $signed > 0) $target = $newQty * $unit;       // part of it covered stock already used
                elseif ($value < 0) $target = 0.0;
                if ($target !== null && abs($target - $value) > 0.0001) {
                    $events->push($event($date, $item, $m, 'correction', 0.0, $target - $value));
                    $value = $target;
                }
                $qty = $newQty;
            }
        }

        return $this->events = $events;
    }

    /** Inventory at cost on a date (nothing before the cut-off: stock was a cost when it was bought). */
    public function valueAsOf(Carbon $end): float
    {
        return round((float) $this->events()->filter(fn ($e) => $e->date->lte($end))->sum('value'), 2);
    }

    /** Stock taken onto the books without a bill by a date: on hand at the cut-off, plus opening stock added since. */
    public function openingStockAsOf(Carbon $end): float
    {
        return round((float) $this->events()->whereIn('kind', ['start', 'opening'])->filter(fn ($e) => $e->date->lte($end))->sum('value'), 2);
    }

    /** Profit & Loss lines: (object) [date, category, item, movement, kind, amount] (amount = cost; negative = a credit). */
    public function costEvents(): Collection
    {
        return $this->events()->whereIn('kind', ['expensed', 'job', 'loss', 'correction'])->map(fn ($e) => (object) [
            'date' => $e->date, 'item' => $e->item, 'movement' => $e->movement, 'kind' => $e->kind, 'amount' => -$e->value,
            'category' => ['expensed' => self::MOVED_IN, 'loss' => self::LOSS][$e->kind] ?? self::USED,
        ])->values();
    }

    /** Per item on a date: [item, qty, value]. */
    public function itemsAsOf(Carbon $end): Collection
    {
        return $this->events()->filter(fn ($e) => $e->date->lte($end))->groupBy(fn ($e) => $e->item->id)
            ->map(fn ($g) => ['item' => $g->first()->item, 'qty' => (float) $g->sum('qty'), 'value' => (float) $g->sum('value')]);
    }

    // ───────────────────────────── Purchase invoice stock lines ─────────────────────────────

    /** Stock a bill has put in so far: [item id => [qty, value]] (its stock-in less what was taken back out). */
    public static function billStock(int $invoiceId): array
    {
        $stock = [];
        foreach (CrmInventoryMovement::where('purchase_invoice_id', $invoiceId)->get() as $m) {
            $sign = $m->type === 'out' ? -1 : 1;
            $stock[$m->inventory_item_id][0] = ($stock[$m->inventory_item_id][0] ?? 0) + $sign * (float) $m->quantity;
            $stock[$m->inventory_item_id][1] = ($stock[$m->inventory_item_id][1] ?? 0) + $sign * (float) $m->quantity * (float) $m->unit_cost;
        }

        return array_filter($stock, fn ($s) => abs($s[0]) > 0.0005 || abs($s[1]) > 0.005);
    }

    /** Why a bill's stock can't change to $wanted ([item id => [qty, value]]): stock it brought in is already used. */
    public static function billProblems(?int $invoiceId, array $wanted): array
    {
        $problems = [];
        foreach ($invoiceId ? self::billStock($invoiceId) : [] as $itemId => [$had]) {
            $keep = (float) ($wanted[$itemId][0] ?? 0);
            if ($had - $keep < 0.0005) continue;
            $item = CrmInventoryItem::find($itemId);
            if ($item && (float) $item->quantity - ($had - $keep) < -0.0005) {
                $n = fn ($v) => rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.');
                $problems[] = $item->name . ': this bill brought in ' . $n($had) . ' ' . $item->unit . ' but only ' . $n((float) $item->quantity) . ' ' . $item->unit
                    . ' are left (the rest is already used). Correct it with a stock adjustment on the Inventory page instead.';
            }
        }

        return $problems;
    }

    /** Bring a bill's stock in line with its stock lines ($wanted: [item id => [qty, value]]), dated on the bill. */
    public function syncBill(CrmPurchaseInvoice $invoice, array $wanted, ?int $userId): void
    {
        $service = app(InventoryService::class);
        $current = self::billStock($invoice->id);
        $date = $invoice->issue_date->toDateString();
        foreach (array_unique(array_merge(array_keys($current), array_keys($wanted))) as $itemId) {
            [$hadQty, $hadValue] = $current[$itemId] ?? [0.0, 0.0];
            [$qty, $value] = $wanted[$itemId] ?? [0.0, 0.0];
            if (abs($hadQty - $qty) < 0.0005 && abs($hadValue - $value) < 0.005) continue;
            $item = CrmInventoryItem::find($itemId);
            if (!$item) continue;
            $extra = ['reason' => 'bill', 'purchase_invoice_id' => $invoice->id, 'entry_date' => $date,
                'note' => trim($invoice->vendor_name . ($invoice->reference ? ' · Inv ' . $invoice->reference : ''))];
            // New quantity in first, then the old one out at its own price: stock never dips below zero on the way.
            if ($qty > 0.0005) {
                $service->move($item, 'in', $qty, $extra + ['unit_cost' => round($value / $qty, 4), 'reference' => ($hadQty > 0.0005 ? 'Corrected on ' : 'Bought on ') . $invoice->number], $userId);
            }
            if ($hadQty > 0.0005) {
                $service->move($item, 'out', $hadQty, $extra + ['unit_cost' => round($hadValue / $hadQty, 4), '_revalue' => true,
                    'reference' => ($qty > 0.0005 ? 'Replaced (bill edited) ' : 'Bill removed ') . $invoice->number], $userId);
            }
        }
        CrmInventoryMovement::where('purchase_invoice_id', $invoice->id)->update(['entry_date' => $date]);
    }
}
