<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** "Revert" on the stock history: the reversing movement points at the entry it undoes. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('crm_inventory_movements', 'reversal_of_id')) {
            Schema::table('crm_inventory_movements', function (Blueprint $table) {
                $table->unsignedBigInteger('reversal_of_id')->nullable()->index()->after('purchase_invoice_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('crm_inventory_movements', 'reversal_of_id')) {
            Schema::table('crm_inventory_movements', function (Blueprint $table) {
                $table->dropColumn('reversal_of_id');
            });
        }
    }
};
