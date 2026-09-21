<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('demand_id')->nullable()->after('job_id');
            $table->string('demand_no', 50)->nullable()->after('demand_id');
            $table->decimal('deduction', 14, 2)->default(0)->after('shipping_cost');
            $table->string('up_imposition', 50)->nullable()->after('gsm');
            $table->string('gp_status', 40)->nullable()->after('up_imposition');
        });

        Schema::create('vendor_purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->unsignedBigInteger('vendor_purchase_id');
            $table->unsignedBigInteger('demand_id')->nullable();
            $table->string('demand_no', 50)->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('method', 40)->nullable();
            $table->date('paid_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('receipt_name')->nullable();
            $table->string('receipt_mime')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index('vendor_purchase_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_purchase_payments');
        Schema::table('vendor_purchases', function (Blueprint $table) {
            $table->dropColumn(['demand_id', 'demand_no', 'deduction', 'up_imposition', 'gp_status']);
        });
    }
};
