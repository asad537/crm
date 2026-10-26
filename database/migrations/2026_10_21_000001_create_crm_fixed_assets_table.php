<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_fixed_assets')) {
            Schema::create('crm_fixed_assets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->date('purchase_date')->index();
                $table->string('name');                          // what was bought (laptop, cutting machine...)
                $table->string('category', 80)->index();         // Machinery, Computers & IT, Furniture...
                $table->string('supplier_name')->nullable();     // purchased from
                $table->decimal('amount', 14, 2);                // purchase price
                $table->string('currency', 10)->default('AED');
                $table->string('payment_method', 60);            // how it was bought: Cash, Bank Transfer, Credit...
                $table->string('reference', 120)->nullable();    // invoice / bill #
                $table->string('receipt_path')->nullable();
                $table->string('receipt_name')->nullable();
                $table->string('note', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_fixed_assets');
    }
};
