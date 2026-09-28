<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDesignJobCardsTables extends Migration
{
    public function up()
    {
        Schema::create('design_job_cards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('design_job_id')->nullable()->unique();

            // Card 1 — Job header
            $table->date('job_date')->nullable();
            $table->string('job_no')->nullable();
            $table->string('product')->nullable();
            $table->unsignedInteger('order_qty')->nullable();
            $table->boolean('priority_urgent')->default(false);
            $table->boolean('priority_critical')->default(false);
            $table->boolean('priority_substandard')->default(false);
            $table->date('priority_date')->nullable();

            // Card 2 — Dummy / sample approval
            $table->date('dummy_sent_on')->nullable();
            $table->date('dummy_approved_on')->nullable();
            $table->string('dummy_approved_by')->nullable();

            // Card 3 — Job briefing
            $table->decimal('box_l', 10, 2)->nullable();
            $table->decimal('box_w', 10, 2)->nullable();
            $table->decimal('box_h', 10, 2)->nullable();
            $table->string('box_unit')->nullable(); // cm | inches | mm
            $table->decimal('open_l', 10, 2)->nullable();
            $table->decimal('open_w', 10, 2)->nullable();
            $table->string('box_type')->nullable(); // hard | soft | other
            $table->string('box_type_other')->nullable();

            // Card 6 — Printing
            $table->string('printing_method')->nullable(); // offset | digital | other
            $table->string('printing_method_other')->nullable();
            $table->unsignedInteger('ctp_plates')->nullable();
            $table->string('pms')->nullable();
            $table->boolean('coating_uv')->default(false);
            $table->boolean('coating_coating')->default(false);
            $table->boolean('coating_varnish')->default(false);
            $table->boolean('coating_other')->default(false);
            $table->string('coating_other_text')->nullable();
            $table->unsignedInteger('total_plates')->nullable();
            $table->time('printing_start')->nullable();
            $table->time('printing_end')->nullable();
            $table->unsignedInteger('printing_total_minutes')->nullable();

            // Card 7 — Lamination
            $table->boolean('lam_gloss')->default(false);
            $table->boolean('lam_matte')->default(false);
            $table->boolean('lam_soft_touch')->default(false);
            $table->boolean('lam_other')->default(false);
            $table->string('lam_other_text')->nullable();
            $table->unsignedInteger('lam_other_qty')->nullable();
            $table->time('lam_start')->nullable();
            $table->time('lam_end')->nullable();
            $table->unsignedInteger('lam_total_minutes')->nullable();

            // Card 8 — Screen printing / Spot UV
            $table->unsignedInteger('screen_colors')->nullable();
            $table->boolean('screen_uv')->default(false);
            $table->time('screen_start')->nullable();
            $table->time('screen_end')->nullable();
            $table->unsignedInteger('screen_total_minutes')->nullable();

            // Card 9 — Foiling
            $table->boolean('foil_gold')->default(false);
            $table->string('foil_gold_shade')->nullable();
            $table->boolean('foil_silver')->default(false);
            $table->string('foil_silver_shade')->nullable();
            $table->boolean('foil_other')->default(false);
            $table->string('foil_other_shade')->nullable();
            $table->time('foil_start')->nullable();
            $table->time('foil_end')->nullable();
            $table->unsignedInteger('foil_total_minutes')->nullable();

            // Card 10 — Corrugation
            $table->string('corr_color')->nullable(); // brown | white | other
            $table->string('corr_color_other')->nullable();
            $table->string('corr_ply')->nullable();
            $table->time('corr_start')->nullable();
            $table->time('corr_end')->nullable();
            $table->unsignedInteger('corr_total_minutes')->nullable();

            // Card 11 — Diecutting
            $table->boolean('die_full')->default(false);
            $table->boolean('die_half')->default(false);
            $table->boolean('die_embossing')->default(false);
            $table->boolean('die_debossing')->default(false);
            $table->time('die_start')->nullable();
            $table->time('die_end')->nullable();
            $table->unsignedInteger('die_total_minutes')->nullable();

            // Card 12 — Pasting
            $table->boolean('paste_tape')->default(false);
            $table->boolean('paste_glue')->default(false);
            $table->boolean('paste_double')->default(false);
            $table->boolean('paste_pvc_window')->default(false);
            $table->boolean('paste_other')->default(false);
            $table->string('paste_other_text')->nullable();
            $table->unsignedInteger('paste_other_qty')->nullable();

            // Card 13 — Quality check
            $table->string('qc_result')->nullable(); // approved | rejected
            $table->text('qc_comments')->nullable();
            $table->string('qc_approved_by')->nullable();

            // Card 14 — Job timeline
            $table->string('timeline_status')->nullable(); // on_time | delayed
            $table->unsignedInteger('delay_days')->nullable();
            $table->text('delay_reason')->nullable();

            $table->timestamps();

            $table->foreign('design_job_id')->references('id')->on('design_jobs')->onDelete('cascade');
        });

        // Card 4 — Procurement list (repeatable)
        Schema::create('design_job_card_materials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('design_job_card_id');
            $table->unsignedInteger('position')->default(0);
            $table->string('item')->nullable();
            $table->string('specs')->nullable();
            $table->string('qty')->nullable();
            $table->date('needed_by')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->foreign('design_job_card_id', 'djc_materials_card_fk')
                ->references('id')->on('design_job_cards')->onDelete('cascade');
        });

        // Card 5 — Paper / Board / Stock (repeatable)
        Schema::create('design_job_card_stocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('design_job_card_id');
            $table->unsignedInteger('position')->default(0);
            $table->string('material')->nullable();
            $table->string('gsm')->nullable();
            $table->decimal('sheet_l', 10, 2)->nullable();
            $table->decimal('sheet_w', 10, 2)->nullable();
            $table->string('sheet_qty')->nullable();
            $table->string('wastage')->nullable();
            $table->decimal('cutting_l', 10, 2)->nullable();
            $table->decimal('cutting_w', 10, 2)->nullable();
            $table->string('total_sheets')->nullable();
            $table->timestamps();

            $table->foreign('design_job_card_id', 'djc_stocks_card_fk')
                ->references('id')->on('design_job_cards')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('design_job_card_stocks');
        Schema::dropIfExists('design_job_card_materials');
        Schema::dropIfExists('design_job_cards');
    }
}
