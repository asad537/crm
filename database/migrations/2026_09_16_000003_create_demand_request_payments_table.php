<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('demand_request_payments')) {
            return;
        }
        Schema::create('demand_request_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('demand_request_id')->index();
            $table->unsignedBigInteger('item_id')->nullable();   // null = general / advance (not against a specific item)
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('method')->nullable();                // Petty Cash / Bank / Cash / Direct Payment ...
            $table->string('paid_to')->nullable();               // vendor / person the money went to
            $table->string('note')->nullable();
            $table->date('paid_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_request_payments');
    }
};
