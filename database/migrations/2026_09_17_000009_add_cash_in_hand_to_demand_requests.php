<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demand_requests', function (Blueprint $table) {
            $table->decimal('cash_in_hand_used', 14, 2)->default(0)->after('notes');
            $table->string('cash_in_hand_note', 500)->nullable()->after('cash_in_hand_used');
        });
    }

    public function down(): void
    {
        Schema::table('demand_requests', function (Blueprint $table) {
            $table->dropColumn(['cash_in_hand_used', 'cash_in_hand_note']);
        });
    }
};
