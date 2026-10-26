<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Money received that is not a payment on an invoice ("Make Receipt" → Other income): scrap sales, commission, refunds...
        if (!Schema::hasTable('crm_direct_receipts')) {
            Schema::create('crm_direct_receipts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->date('receipt_date')->index();
                $table->string('category', 80);                   // income category in Profit & Loss
                $table->string('description');
                $table->string('payer_type', 20)->default('other');   // customer | other
                $table->string('payer_name')->nullable();
                $table->decimal('amount', 14, 2);
                $table->string('currency', 10)->default('AED');
                $table->string('method', 60)->nullable();
                $table->unsignedBigInteger('bank_account_id')->nullable()->index();
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
        Schema::dropIfExists('crm_direct_receipts');
    }
};
