<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Team Lead final-offer form allows profit margin up to 1000%, but the
     * column was decimal(5,2) (max 999.99), causing a 22003 out-of-range error.
     * Widen it so large margins can be stored.
     */
    public function up(): void
    {
        Schema::table('estimate_ticket_options', function (Blueprint $table) {
            $table->decimal('profit_margin_percentage', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('estimate_ticket_options', function (Blueprint $table) {
            $table->decimal('profit_margin_percentage', 5, 2)->nullable()->change();
        });
    }
};
