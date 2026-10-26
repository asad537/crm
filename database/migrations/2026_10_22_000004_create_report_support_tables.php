<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Yearly budget per Profit & Loss category (Budget vs Actual report).
        if (!Schema::hasTable('crm_budgets')) {
            Schema::create('crm_budgets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->unsignedSmallInteger('year');
                $table->string('group', 10);                      // income | cos | opex
                $table->string('category', 120);
                $table->decimal('amount', 14, 2)->default(0);     // for the whole year
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['workspace_id', 'year', 'group', 'category'], 'crm_budgets_unique');
            });
        }
        // Bank reconciliation: which bank / cash movements have cleared on the bank statement...
        if (!Schema::hasTable('crm_bank_clearances')) {
            Schema::create('crm_bank_clearances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->unsignedBigInteger('bank_account_id')->index();
                $table->string('movement_key', 80);               // "expense:12", "transfer:3"...
                $table->date('cleared_date');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->unique(['bank_account_id', 'movement_key'], 'crm_bank_clearances_unique');
            });
        }
        // ...and each reconciliation done against a statement balance.
        if (!Schema::hasTable('crm_bank_reconciliations')) {
            Schema::create('crm_bank_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->unsignedBigInteger('bank_account_id')->index();
                $table->date('statement_date');
                $table->decimal('statement_balance', 14, 2);
                $table->decimal('book_balance', 14, 2);
                $table->decimal('uncleared_in', 14, 2)->default(0);
                $table->decimal('uncleared_out', 14, 2)->default(0);
                $table->decimal('difference', 14, 2)->default(0);
                $table->string('note', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
        // Small per-workspace settings (e.g. WPS establishment ID).
        if (!Schema::hasTable('crm_settings')) {
            Schema::create('crm_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->string('key', 80);
                $table->text('value')->nullable();
                $table->timestamps();
                $table->unique(['workspace_id', 'key']);
            });
        }
        // WPS (Wage Protection System) salary file needs these per employee.
        Schema::table('crm_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_employees', 'mol_person_id')) $table->string('mol_person_id', 30)->nullable()->after('emirates_id');
            if (!Schema::hasColumn('crm_employees', 'bank_routing_code')) $table->string('bank_routing_code', 20)->nullable()->after('iban');
        });
    }

    public function down(): void
    {
        Schema::table('crm_employees', function (Blueprint $table) {
            foreach (['mol_person_id', 'bank_routing_code'] as $column) {
                if (Schema::hasColumn('crm_employees', $column)) $table->dropColumn($column);
            }
        });
        Schema::dropIfExists('crm_settings');
        Schema::dropIfExists('crm_bank_reconciliations');
        Schema::dropIfExists('crm_bank_clearances');
        Schema::dropIfExists('crm_budgets');
    }
};
