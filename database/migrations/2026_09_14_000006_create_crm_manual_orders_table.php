<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_manual_orders')) return;

        Schema::create('crm_manual_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->unsignedBigInteger('crm_email_id')->nullable()->index();

            // Invoice header
            $table->string('user_name')->nullable();
            $table->string('enquiry_number')->nullable();
            $table->string('invoice_status')->default('unpaid');
            $table->string('invoice_number')->nullable();
            $table->string('website')->nullable();
            $table->string('currency')->default('USD');
            $table->date('invoice_date')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('payment_term')->nullable();

            // Billing / shipping stored as JSON blobs (name, company, street, city, state, country, zip, phone)
            $table->json('billing')->nullable();
            $table->json('shipping')->nullable();

            // Other detail header
            $table->string('sales_person')->nullable();
            $table->string('shipping_method')->nullable();
            $table->string('shipping_term')->nullable();
            $table->string('payment_term_via')->nullable();
            $table->text('additional_info')->nullable();

            // Order line items (array of box_style, stock, color, length, width, height, unit,
            // finishing, additional_info, qty, unit_price, other_charges, line_total)
            $table->json('line_items')->nullable();

            // Payment detail
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->decimal('package_price', 12, 2)->default(0);
            $table->decimal('rush_charges', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_manual_orders');
    }
};
