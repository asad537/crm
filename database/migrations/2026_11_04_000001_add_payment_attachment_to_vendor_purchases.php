<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The payment proof uploaded in the Payment Summary of a vendor purchase (separate from the invoice attachment). */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vendor_purchases', 'payment_attachment_path')) {
            Schema::table('vendor_purchases', function (Blueprint $table) {
                $table->string('payment_attachment_path')->nullable()->after('attachment_mime');
                $table->string('payment_attachment_name')->nullable()->after('payment_attachment_path');
                $table->string('payment_attachment_mime', 100)->nullable()->after('payment_attachment_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vendor_purchases', 'payment_attachment_path')) {
            Schema::table('vendor_purchases', function (Blueprint $table) {
                $table->dropColumn(['payment_attachment_path', 'payment_attachment_name', 'payment_attachment_mime']);
            });
        }
    }
};
