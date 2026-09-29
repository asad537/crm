<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddFoamToDesignJobCards extends Migration
{
    public function up()
    {
        Schema::table('design_job_cards', function (Blueprint $table) {
            $table->string('foam_type')->nullable();
            $table->string('foam_type_other')->nullable();
            $table->string('foam_color')->nullable();
            $table->string('foam_color_other')->nullable();
            $table->decimal('foam_thickness', 8, 2)->nullable();
            $table->unsignedInteger('foam_qty')->nullable();
        });

        $this->shiftSavedSteps(4, 1);
    }

    public function down()
    {
        $this->shiftSavedSteps(5, -1);

        Schema::table('design_job_cards', function (Blueprint $table) {
            $table->dropColumn([
                'foam_type', 'foam_type_other', 'foam_color',
                'foam_color_other', 'foam_thickness', 'foam_qty',
            ]);
        });
    }

    private function shiftSavedSteps($from, $offset)
    {
        DB::table('design_job_cards')->whereNotNull('section_choices')->chunkById(200, function ($cards) use ($from, $offset) {
            foreach ($cards as $card) {
                $choices = json_decode($card->section_choices, true);
                if (!is_array($choices)) {
                    continue;
                }
                $changed = false;
                foreach (['__active_step', '__completed_step'] as $key) {
                    if (isset($choices[$key]) && is_numeric($choices[$key]) && (int) $choices[$key] >= $from) {
                        $choices[$key] = (int) $choices[$key] + $offset;
                        $changed = true;
                    }
                }
                if ($changed) {
                    DB::table('design_job_cards')->where('id', $card->id)
                        ->update(['section_choices' => json_encode($choices)]);
                }
            }
        });
    }
}
