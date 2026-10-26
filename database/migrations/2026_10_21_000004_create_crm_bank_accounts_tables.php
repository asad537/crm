<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Money movement tables that can be tagged with the bank / cash account the money went through. */
    const MOVEMENT_TABLES = [
        'crm_order_payments', 'customer_sales', 'crm_income_entries', 'vendor_purchase_payments', 'demand_request_payments',
        'crm_expenses', 'crm_purchase_invoice_payments', 'crm_payroll_lines', 'crm_employee_advances', 'crm_fixed_assets',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('crm_bank_accounts')) {
            Schema::create('crm_bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->string('name', 120);
                $table->string('type', 10)->default('bank');              // bank | cash
                $table->string('bank_name', 120)->nullable();
                $table->string('account_number', 60)->nullable();         // account # / IBAN
                $table->string('currency', 10)->default('AED');
                $table->decimal('opening_balance', 14, 2)->default(0);
                $table->date('opening_date');                             // balance as of this date; earlier movements are inside it
                $table->json('auto_methods')->nullable();                  // payment methods booked here when no account was picked
                $table->boolean('is_active')->default(true);
                $table->string('notes', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('crm_bank_transfers')) {
            Schema::create('crm_bank_transfers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->unsignedBigInteger('from_account_id')->index();
                $table->unsignedBigInteger('to_account_id')->index();
                $table->date('transfer_date');
                $table->decimal('amount', 14, 2);
                $table->string('reference', 120)->nullable();
                $table->string('note', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
        foreach (self::MOVEMENT_TABLES as $name) {
            if (Schema::hasTable($name) && !Schema::hasColumn($name, 'bank_account_id')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->unsignedBigInteger('bank_account_id')->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::MOVEMENT_TABLES as $name) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'bank_account_id')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->dropIndex(['bank_account_id']);
                    $table->dropColumn('bank_account_id');
                });
            }
        }
        Schema::dropIfExists('crm_bank_transfers');
        Schema::dropIfExists('crm_bank_accounts');
    }
};
