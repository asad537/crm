<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_manual_order_payments')) return;

        Schema::create('crm_manual_order_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manual_order_id')->index();
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->decimal('amount', 14, 2);
            $table->date('paid_at');
            $table->string('method', 100)->nullable();
            $table->string('reference', 255)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_manual_order_payments');
    }
};
