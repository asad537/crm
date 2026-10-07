<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_manual_order_production_briefs')) return;

        Schema::create('crm_manual_order_production_briefs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manual_order_id')->unique();
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            // Job briefing
            $table->string('job_number', 60)->nullable();
            $table->date('brief_date')->nullable();
            $table->string('production_type', 60)->nullable();
            $table->string('job_type', 40)->nullable();
            $table->string('client_name')->nullable();
            // Product description blocks (one per product)
            $table->json('products')->nullable();
            // Footer
            $table->string('folder_path', 500)->nullable();
            $table->date('job_forwarding_date')->nullable();
            $table->date('printers_deadline')->nullable();
            $table->date('clients_deadline')->nullable();
            $table->text('additional_requirements')->nullable();
            $table->string('status', 30)->default('sent');
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_manual_order_production_briefs');
    }
};
