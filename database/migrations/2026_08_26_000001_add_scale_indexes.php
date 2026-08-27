<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Composite indexes for high-scale (millions of rows) performance.
 * Each hot filter combination gets a covering index so MySQL never needs a full-table scan.
 * Uses IF NOT EXISTS logic to be re-run safely.
 */
class AddScaleIndexes extends Migration
{
    protected $indexes = [
        // Team chat: sender/receiver + unread + workspace
        'crm_internal_messages' => [
            'im_workspace_recv_read_idx' => ['workspace_id', 'receiver_id', 'is_read'],
            'im_workspace_sender_recv_idx' => ['workspace_id', 'sender_id', 'receiver_id', 'created_at'],
        ],
        // Vendor Purchases: date-filtered vendor detail + payment_status filter
        'vendor_purchases' => [
            'vp_vendor_date_idx' => ['vendor_id', 'purchase_date'],
            'vp_workspace_payment_idx' => ['workspace_id', 'payment_status'],
            'vp_workspace_date_idx' => ['workspace_id', 'purchase_date'],
        ],
        // Team Performance: user_name + status + date range
        'crm_status_logs' => [
            'csl_user_status_date_idx' => ['user_name', 'new_status', 'created_at'],
        ],
        // Customer chats (Team Chat): per-order unread counts
        'customer_chats' => [
            'cc_order_sender_read_idx' => ['sales_order_id', 'sender_type', 'is_read'],
        ],
        // Invoice list ordering — orders sorted by order_marked_at
        'crm_emails' => [
            'crm_emails_status_marked_idx' => ['status', 'order_marked_at'],
        ],
        // Sales orders — status + payment_status combo
        'sales_orders' => [
            'so_status_payment_idx' => ['status', 'payment_status'],
        ],
    ];

    public function up()
    {
        foreach ($this->indexes as $table => $indexes) {
            if (!Schema::hasTable($table)) continue;
            $existing = collect(DB::select("SHOW INDEX FROM `$table`"))->pluck('Key_name')->unique();
            foreach ($indexes as $name => $columns) {
                if ($existing->contains($name)) continue;
                // Verify all columns exist before creating index.
                $missing = array_filter($columns, fn ($c) => !Schema::hasColumn($table, $c));
                if (!empty($missing)) continue;
                $cols = implode(',', array_map(fn ($c) => "`$c`", $columns));
                try {
                    DB::statement("CREATE INDEX `$name` ON `$table` ($cols)");
                } catch (\Throwable $e) {
                    // log and continue
                    \Log::warning("scale-index skip {$table}.{$name}: ".$e->getMessage());
                }
            }
        }
    }

    public function down()
    {
        foreach ($this->indexes as $table => $indexes) {
            if (!Schema::hasTable($table)) continue;
            foreach (array_keys($indexes) as $name) {
                try { DB::statement("DROP INDEX `$name` ON `$table`"); } catch (\Throwable $e) {}
            }
        }
    }
}
