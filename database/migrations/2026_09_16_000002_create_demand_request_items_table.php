<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('demand_request_items')) {
            return;
        }
        Schema::create('demand_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('demand_request_id')->index();
            $table->integer('position')->default(0);
            $table->string('category')->nullable();
            $table->string('job_no')->nullable();
            $table->string('description')->nullable();
            $table->string('specification')->nullable();
            $table->string('qty', 60)->nullable();          // free text: "1500 sheets", "N/A", "Nil"...
            $table->decimal('estimated_price', 14, 2)->nullable();
            $table->decimal('estimated_total', 14, 2)->default(0);
            // ---- receive side (Phase 3) ----
            $table->boolean('received')->default(false);
            $table->timestamp('received_at')->nullable();
            $table->decimal('actual_price', 14, 2)->nullable();
            $table->decimal('actual_total', 14, 2)->default(0);
            $table->string('paid_by')->nullable();          // Petty Cash / Bank / Cash / person
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('outstanding', 14, 2)->default(0);
            $table->string('vendor_name')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_request_items');
    }
};
