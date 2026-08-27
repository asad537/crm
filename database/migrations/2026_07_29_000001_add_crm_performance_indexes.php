<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCrmPerformanceIndexes extends Migration
{
    public function up()
    {
        $this->addIndexIfMissing('crm_emails', 'crm_emails_workspace_inbox_index', ['workspace_id', 'is_spam', 'is_rejected', 'status', 'assigned_to']);
        $this->addIndexIfMissing('crm_emails', 'crm_emails_workspace_estimate_index', ['workspace_id', 'estimate_status', 'assigned_to']);
        $this->addIndexIfMissing('crm_emails', 'crm_emails_workspace_created_index', ['workspace_id', 'created_at']);
        $this->addIndexIfMissing('estimate_tickets', 'estimate_tickets_workspace_status_lead_index', ['workspace_id', 'status', 'team_lead_id']);
        $this->addIndexIfMissing('estimate_tickets', 'estimate_tickets_workspace_status_estimator_index', ['workspace_id', 'status', 'estimator_id']);
        $this->addIndexIfMissing('estimate_tickets', 'estimate_tickets_requester_status_index', ['requested_by', 'status']);
        $this->addIndexIfMissing('design_requirement_tickets', 'design_tickets_workspace_status_claimed_index', ['workspace_id', 'status', 'claimed_by']);
        $this->addIndexIfMissing('sales_orders', 'sales_orders_status_index', ['status']);
        $this->addIndexIfMissing('sales_orders', 'sales_orders_shipping_stage_index', ['shipping_stage']);
        $this->addIndexIfMissing('sales_orders', 'sales_orders_created_at_index', ['created_at']);
    }

    public function down()
    {
        foreach ([
            ['crm_emails', 'crm_emails_workspace_inbox_index'],
            ['crm_emails', 'crm_emails_workspace_estimate_index'],
            ['crm_emails', 'crm_emails_workspace_created_index'],
            ['estimate_tickets', 'estimate_tickets_workspace_status_lead_index'],
            ['estimate_tickets', 'estimate_tickets_workspace_status_estimator_index'],
            ['estimate_tickets', 'estimate_tickets_requester_status_index'],
            ['design_requirement_tickets', 'design_tickets_workspace_status_claimed_index'],
            ['sales_orders', 'sales_orders_status_index'],
            ['sales_orders', 'sales_orders_shipping_stage_index'],
            ['sales_orders', 'sales_orders_created_at_index'],
        ] as $index) {
            if ($this->indexExists($index[0], $index[1])) {
                DB::statement("ALTER TABLE `{$index[0]}` DROP INDEX `{$index[1]}`");
            }
        }
    }

    private function addIndexIfMissing($table, $name, array $columns)
    {
        if (!$this->indexExists($table, $name)) {
            $columnSql = implode('`, `', $columns);
            DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$name}` (`{$columnSql}`)");
        }
    }

    private function indexExists($table, $name)
    {
        return count(DB::select(
            'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$table, $name]
        )) > 0;
    }
}
