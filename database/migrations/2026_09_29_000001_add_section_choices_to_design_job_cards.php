<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSectionChoicesToDesignJobCards extends Migration
{
    public function up()
    {
        Schema::table('design_job_cards', function (Blueprint $table) {
            $table->json('section_choices')->nullable();
        });
    }

    public function down()
    {
        Schema::table('design_job_cards', function (Blueprint $table) {
            $table->dropColumn('section_choices');
        });
    }
}
