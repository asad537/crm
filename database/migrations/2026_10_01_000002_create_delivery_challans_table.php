<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_challans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->unsignedBigInteger('design_job_id')->nullable()->index();
            $table->string('challan_no')->unique();
            $table->date('challan_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('job_no')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('po_reference')->nullable();
            $table->json('items')->nullable();
            $table->text('remarks')->nullable();
            $table->string('prepared_by')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_contact')->nullable();
            $table->string('received_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_challans');
    }
};
