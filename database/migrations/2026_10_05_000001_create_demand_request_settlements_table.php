<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('demand_request_settlements')) {
            return;
        }
        Schema::create('demand_request_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('demand_request_id')->index();
            $table->decimal('amount', 14, 2)->default(0);        // money the company handed to the accountant
            $table->string('method')->nullable();                // Cash / Bank ...
            $table->string('note')->nullable();
            $table->date('paid_at')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('proof_name')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_request_settlements');
    }
};
