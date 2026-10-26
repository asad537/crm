<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Files attached to a customer sale on the Customers tab (PO, signed invoice, delivery note, receipt...). */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_sale_attachments')) {
            Schema::create('customer_sale_attachments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->unsignedBigInteger('customer_sale_id')->index();
                $table->string('path');
                $table->string('original_name');
                $table->string('mime', 120)->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_sale_attachments');
    }
};
