<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('demand_requests')) {
            return;
        }
        Schema::create('demand_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedInteger('request_no')->default(1);
            $table->date('request_date');
            $table->string('requested_by')->nullable();
            $table->string('priority', 20)->default('Normal');
            $table->string('status', 30)->default('Submitted');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('estimated_total', 14, 2)->default(0);
            $table->decimal('actual_total', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_requests');
    }
};
