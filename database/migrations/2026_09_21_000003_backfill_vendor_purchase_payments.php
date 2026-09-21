<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Existing purchases carry a paid_amount but no payment rows. Seed one initial
        // payment each so the payments table becomes the single source of truth.
        $purchases = DB::table('vendor_purchases')
            ->where('paid_amount', '>', 0)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('vendor_purchase_payments')
                    ->whereColumn('vendor_purchase_payments.vendor_purchase_id', 'vendor_purchases.id');
            })
            ->get(['id', 'workspace_id', 'paid_amount', 'payment_method', 'purchase_date', 'created_by']);

        foreach ($purchases as $p) {
            DB::table('vendor_purchase_payments')->insert([
                'workspace_id' => $p->workspace_id,
                'vendor_purchase_id' => $p->id,
                'amount' => $p->paid_amount,
                'method' => $p->payment_method,
                'paid_at' => $p->purchase_date,
                'note' => 'Initial payment (migrated)',
                'created_by' => $p->created_by,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('vendor_purchase_payments')->where('note', 'Initial payment (migrated)')->delete();
    }
};
