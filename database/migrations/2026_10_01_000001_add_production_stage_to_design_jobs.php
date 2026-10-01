<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_jobs', function (Blueprint $table) {
            $table->string('production_stage')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('design_jobs', function (Blueprint $table) {
            $table->dropColumn('production_stage');
        });
    }
};
