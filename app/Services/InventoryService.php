<?php

namespace App\Services;

use App\CrmInventoryItem;
use App\CrmInventoryMovement;
use App\DemandRequestItem;
use Illuminate\Support\Facades\DB;

/**
 * Stock ledger glue between Demand Requests (stock IN when an item is received) and
 * Job Cards (stock OUT for the materials a job consumes). Every change is a movement row
 * with the running balance, so the Inventory page shows where stock came from and went.
 */
class InventoryService
{
    /** Apply one movement under a row lock and return it. $type: in | out | adjust. */
    public function move(CrmInventoryItem $item, string $type, float $qty, array $extra, ?int $userId): CrmInventoryMovement
    {
        return DB::transaction(function () use ($item, $type, $qty, $extra, $userId) {
            $locked = CrmInventoryItem::withoutGlobalScopes()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $signed = $extra['_signed'] ?? ($type === 'out' ? -$qty : $qty);
            $oldQty = (float) $locked->quantity;
            $balance = round($oldQty + $signed, 3);
            $locked->quantity = $balance;
            // Weighted-average cost: 1000 @ 20 then 1000 @ 18 → the item is carried at 19.00.
            // Each purchase keeps its own price on the movement row; the item holds the average.
            if ($type === 'in' && isset($extra['unit_cost']) && $extra['unit_cost'] !== '' && $extra['unit_cost'] !== null) {
                $newCost = (float) $extra['unit_cost'];
                $oldCost = (float) ($locked->unit_cost ?? 0);
                $locked->unit_cost = ($oldQty > 0 && $oldCost > 0)
                    ? round(($oldQty * $oldCost + $qty * $newCost) / ($oldQty + $qty), 4)
                    : $newCost;
            }
            $locked->save();
            $item->quantity = $balance;
            $item->unit_cost = $locked->unit_cost;
            if ($type === 'out' && (!isset($extra['unit_cost']) || $extra['unit_cost'] === '' || $extra['unit_cost'] === null)) {
                $extra['unit_cost'] = $locked->unit_cost; // what this consumption cost the job
            }
            return CrmInventoryMovement::create([
                'workspace_id' => $locked->workspace_id,
                'inventory_item_id' => $locked->id,
                'type' => $type,
                'quantity' => abs($qty),
                'balance_after' => $balance,
                'job_id' => $extra['job_id'] ?? null,
                'job_number' => $extra['job_number'] ?? null,
                'vendor_purchase_id' => $extra['vendor_purchase_id'] ?? null,
                'unit_cost' => $extra['unit_cost'] ?? null,
                'reference' => $extra['reference'] ?? null,
                'note' => $extra['note'] ?? null,
                'created_by' => $userId,
            ]);
        });
    }

    /** Numeric part of a free-text qty ("6250", "5,000 sheets", "2 rolls") or null. */
    public static function numericQty($qty): ?float
    {
        $s = str_replace(',', '', trim((string) $qty));
        if ($s === '' || !preg_match('/-?\d+(?:\.\d+)?/', $s, $m)) return null;
        $n = (float) $m[0];
        return $n > 0 ? $n : null;
    }

    /** Inventory item that matches a demand line (name + specification + GSM, case-insensitive), created if missing. */
    public function itemForDemandLine(DemandRequestItem $line, int $workspaceId, array $o = []): ?CrmInventoryItem
    {
        $name = trim((string) ($o['name'] ?? $line->description));
        if ($name === '') return null;
        $spec = trim((string) ($o['paper_size'] ?? $line->specification)) ?: null;
        $gsm = trim((string) ($o['gsm'] ?? $line->gsm)) ?: null;
        $q = CrmInventoryItem::withoutGlobalScopes()->where('workspace_id', $workspaceId)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
        $q->where(function ($w) use ($spec) { $spec ? $w->whereRaw('LOWER(paper_size) = ?', [mb_strtolower($spec)]) : $w->whereNull('paper_size')->orWhere('paper_size', ''); });
        $q->where(function ($w) use ($gsm) { $gsm ? $w->whereRaw('LOWER(gsm) = ?', [mb_strtolower($gsm)]) : $w->whereNull('gsm')->orWhere('gsm', ''); });
        $found = $q->orderBy('id')->first();
        if ($found) return $found;
        return CrmInventoryItem::create([
            'workspace_id' => $workspaceId,
            'name' => $name,
            'category' => trim((string) ($o['category'] ?? $line->category)) ?: null,
            'paper_size' => $spec,
            'gsm' => $gsm,
            'unit' => trim((string) ($o['unit'] ?? '')) ?: 'pcs',
            'quantity' => 0,
            'unit_cost' => $o['unit_cost'] ?? $line->estimated_price,
        ]);
    }

    /**
     * Put a received demand line INTO inventory. This is an explicit step the user confirms
     * (name / size / GSM / unit / qty / cost can be corrected in $o). Idempotent per line.
     */
    public function receiveDemandLine(DemandRequestItem $line, ?int $userId, array $o = []): ?CrmInventoryMovement
    {
        $extra = (array) ($line->extra ?? []);
        if (!empty($extra['inventory_movement_id'])) return null; // already stocked in
        $qty = isset($o['qty']) && $o['qty'] !== '' ? (float) $o['qty'] : self::numericQty($line->qty);
        $request = $line->request()->first();
        if (!$qty || $qty <= 0 || !$request) return null;
        $item = $this->itemForDemandLine($line, (int) $request->workspace_id, $o);
        if (!$item) return null;
        $mv = $this->move($item, 'in', $qty, [
            'unit_cost' => isset($o['unit_cost']) && $o['unit_cost'] !== '' ? (float) $o['unit_cost'] : ($line->actual_price ?: $line->estimated_price),
            'reference' => 'Demand #' . $request->request_no . ' received',
            'note' => trim((string) ($o['note'] ?? '')) ?: (trim(implode(' · ', array_filter([$line->vendor_name, $line->vendor_invoice_no ? 'Inv ' . $line->vendor_invoice_no : null]))) ?: null),
        ], $userId);
        $extra['inventory_item_id'] = $item->id;
        $extra['inventory_movement_id'] = $mv->id;
        $extra['inventory_qty'] = $qty;
        $line->forceFill(['extra' => $extra])->saveQuietly();
        return $mv;
    }

    /** Undo a received line (stock goes back OUT). Idempotent. */
    public function unreceiveDemandLine(DemandRequestItem $line, ?int $userId): ?CrmInventoryMovement
    {
        $extra = (array) ($line->extra ?? []);
        if (empty($extra['inventory_item_id']) || empty($extra['inventory_qty'])) return null;
        $item = CrmInventoryItem::withoutGlobalScopes()->find($extra['inventory_item_id']);
        $request = $line->request()->first();
        $mv = null;
        if ($item) {
            $mv = $this->move($item, 'out', (float) $extra['inventory_qty'], ['reference' => 'Demand #' . ($request->request_no ?? '?') . ' receipt undone'], $userId);
        }
        unset($extra['inventory_item_id'], $extra['inventory_movement_id'], $extra['inventory_qty']);
        $line->forceFill(['extra' => $extra ?: null])->saveQuietly();
        return $mv;
    }

    /** A received line's qty was edited: keep the stock in step with it. */
    public function resyncReceivedQty(DemandRequestItem $line, ?int $userId): void
    {
        $extra = (array) ($line->extra ?? []);
        if (empty($extra['inventory_item_id'])) return;
        $new = self::numericQty($line->qty) ?: 0.0;
        $old = (float) ($extra['inventory_qty'] ?? 0);
        $diff = round($new - $old, 3);
        if ($diff == 0.0) return;
        $item = CrmInventoryItem::withoutGlobalScopes()->find($extra['inventory_item_id']);
        if (!$item) return;
        $request = $line->request()->first();
        $this->move($item, $diff > 0 ? 'in' : 'out', abs($diff), ['reference' => 'Demand #' . ($request->request_no ?? '?') . ' qty corrected'], $userId);
        $extra['inventory_qty'] = $new;
        $line->forceFill(['extra' => $extra])->saveQuietly();
    }

    /** Items whose stock cannot cover the extra usage ($after − $before): [name => available]. Read-only. */
    public function shortfalls(array $before, array $after): array
    {
        $short = [];
        foreach ($after as $id => $qty) {
            $diff = round((float) $qty - (float) ($before[$id] ?? 0), 3);
            if ($diff <= 0) continue;
            $item = CrmInventoryItem::withoutGlobalScopes()->find($id);
            if ($item && (float) $item->quantity < $diff) $short[$item->name] = (float) $item->quantity;
        }
        return $short;
    }

    /**
     * Reconcile a job card's material consumption: $before/$after are maps inventory_item_id => qty.
     * Returns a list of shortfalls [item name => available] when the new usage cannot be covered
     * (nothing is written in that case).
     */
    public function syncJobConsumption(array $before, array $after, ?int $jobId, ?string $jobNumber, ?int $userId): array
    {
        $short = $this->shortfalls($before, $after);
        if ($short) return $short;
        $plan = [];
        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $id) {
            $diff = round((float) ($after[$id] ?? 0) - (float) ($before[$id] ?? 0), 3);
            if ($diff == 0.0) continue;
            $item = CrmInventoryItem::withoutGlobalScopes()->find($id);
            if ($item) $plan[] = [$item, $diff];
        }
        foreach ($plan as [$item, $diff]) {
            $this->move($item, $diff > 0 ? 'out' : 'in', abs($diff), [
                'job_id' => $jobId, 'job_number' => $jobNumber,
                'reference' => ($diff > 0 ? 'Used on job ' : 'Returned from job ') . ($jobNumber ?: '#' . $jobId),
            ], $userId);
        }
        return [];
    }
}
