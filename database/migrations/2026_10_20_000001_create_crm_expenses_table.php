<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_expenses')) {
            Schema::create('crm_expenses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->date('expense_date')->index();
                $table->string('category', 80)->index();          // what the expense is for (Rent, Fuel, ...)
                $table->string('name');                           // expense title
                $table->string('paid_from', 60);                  // Cash, Bank Transfer, Petty Cash, ...
                $table->string('payee_type', 20)->default('other'); // supplier | customer | other
                $table->string('payee_name')->nullable();
                $table->decimal('amount', 14, 2);
                $table->string('currency', 10)->default('AED');
                $table->string('reference', 120)->nullable();
                $table->string('note', 500)->nullable();
                $table->string('proof_path')->nullable();
                $table->string('proof_name')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_expenses');
    }
};
