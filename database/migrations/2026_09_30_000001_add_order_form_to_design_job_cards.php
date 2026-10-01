<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_job_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('design_job_cards', 'order_form')) {
                $table->json('order_form')->nullable()->after('section_choices');
            }
        });
    }

    public function down(): void
    {
        Schema::table('design_job_cards', function (Blueprint $table) {
            if (Schema::hasColumn('design_job_cards', 'order_form')) {
                $table->dropColumn('order_form');
            }
        });
    }
};
