<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_purchase_invoices')) {
            Schema::create('crm_purchase_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->unsignedBigInteger('vendor_id')->nullable()->index();
                $table->string('vendor_name');                    // snapshot, survives vendor renames/deletes
                $table->date('issue_date')->index();
                $table->date('due_date')->nullable()->index();
                $table->string('reference', 120)->nullable();     // supplier's invoice #
                $table->string('project', 120)->nullable();
                $table->text('description')->nullable();
                $table->string('currency', 10)->default('AED');
                $table->decimal('total', 14, 2)->default(0);
                $table->string('attachment_path')->nullable();
                $table->string('attachment_name')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('crm_purchase_invoice_items')) {
            Schema::create('crm_purchase_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('purchase_invoice_id')->index();
                $table->string('name');
                $table->decimal('quantity', 14, 3);
                $table->decimal('unit_price', 14, 2);
                $table->decimal('line_total', 14, 2);
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('crm_purchase_invoice_payments')) {
            Schema::create('crm_purchase_invoice_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('purchase_invoice_id')->index();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->decimal('amount', 14, 2);
                $table->date('paid_at');
                $table->string('method', 60)->nullable();
                $table->string('reference', 120)->nullable();
                $table->string('note', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_purchase_invoice_payments');
        Schema::dropIfExists('crm_purchase_invoice_items');
        Schema::dropIfExists('crm_purchase_invoices');
    }
};
