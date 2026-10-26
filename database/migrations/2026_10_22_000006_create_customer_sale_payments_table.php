<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payments received on a customer sale from "Make Receipt" (date, method, account). The sale's paid_amount
        // stays the total paid; whatever it has above these rows was paid on the sale date (older data).
        if (!Schema::hasTable('customer_sale_payments')) {
            Schema::create('customer_sale_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->unsignedBigInteger('customer_sale_id')->index();
                $table->decimal('amount', 14, 2);
                $table->date('paid_at');
                $table->string('method', 60)->nullable();
                $table->unsignedBigInteger('bank_account_id')->nullable()->index();
                $table->string('reference', 255)->nullable();
                $table->string('note', 2000)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_sale_payments');
    }
};
