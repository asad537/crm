<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_customers') && !Schema::hasColumn('crm_customers', 'customer_type')) {
            Schema::table('crm_customers', function (Blueprint $table) {
                $table->string('customer_type', 20)->default('sales')->after('name')->index(); // sales | income
                $table->string('income_head', 80)->nullable()->after('customer_type');         // Rent, Service charges, ...
                $table->decimal('recurring_amount', 14, 2)->nullable()->after('income_head');  // monthly due
                $table->unsignedTinyInteger('recurring_day', false)->nullable()->after('recurring_amount'); // due day of month
                $table->date('recurring_start')->nullable()->after('recurring_day');            // first month that is due
                $table->boolean('recurring_active')->default(true)->after('recurring_start');
            });
        }
        if (!Schema::hasTable('crm_income_entries')) {
            Schema::create('crm_income_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->string('income_head', 80)->nullable();
                $table->string('period', 7)->index();                 // YYYY-MM
                $table->date('due_date');
                $table->decimal('amount_due', 14, 2)->default(0);
                $table->decimal('amount_paid', 14, 2)->default(0);
                $table->date('paid_at')->nullable();
                $table->string('payment_method', 60)->nullable();
                $table->string('reference', 120)->nullable();
                $table->string('proof_path')->nullable();
                $table->string('note', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('paid_recorded_by')->nullable();
                $table->timestamps();
                $table->unique(['customer_id', 'period']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_income_entries');
        if (Schema::hasColumn('crm_customers', 'customer_type')) {
            Schema::table('crm_customers', function (Blueprint $table) {
                $table->dropColumn(['customer_type', 'income_head', 'recurring_amount', 'recurring_day', 'recurring_start', 'recurring_active']);
            });
        }
    }
};
