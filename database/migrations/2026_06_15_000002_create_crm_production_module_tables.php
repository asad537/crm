<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrmProductionModuleTables extends Migration
{
    public function up()
    {
        Schema::create('production_facilities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('city');
            $table->string('country');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('production_machines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('production_facility_id');
            $table->string('name');
            $table->string('code')->unique();
            $table->string('printing_method');
            $table->string('status')->default('available');
            $table->timestamps();

            $table->foreign('production_facility_id')
                ->references('id')->on('production_facilities')->onDelete('cascade');
        });

        Schema::create('production_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sales_order_id')->unique();
            $table->unsignedBigInteger('production_facility_id')->nullable();
            $table->unsignedBigInteger('production_machine_id')->nullable();
            $table->unsignedBigInteger('production_manager_id')->nullable();
            $table->unsignedBigInteger('press_operator_id')->nullable();
            $table->string('printing_method')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('pending_planning');
            $table->unsignedInteger('planned_quantity')->default(0);
            $table->unsignedInteger('good_quantity')->default(0);
            $table->unsignedInteger('waste_quantity')->default(0);
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_due_at')->nullable();
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('planning_notes')->nullable();
            $table->text('press_setup_notes')->nullable();
            $table->text('adjustment_notes')->nullable();
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('production_facility_id')->references('id')->on('production_facilities')->onDelete('set null');
            $table->foreign('production_machine_id')->references('id')->on('production_machines')->onDelete('set null');
            $table->foreign('production_manager_id')->references('id')->on('crm_users')->onDelete('set null');
            $table->foreign('press_operator_id')->references('id')->on('crm_users')->onDelete('set null');
        });

        Schema::create('production_first_sheet_checks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('production_job_id');
            $table->unsignedBigInteger('qc_inspector_id')->nullable();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->boolean('proof_match_passed')->default(false);
            $table->boolean('cmyk_density_passed')->default(false);
            $table->boolean('spot_color_passed')->default(false);
            $table->boolean('registration_passed')->default(false);
            $table->boolean('print_defect_passed')->default(false);
            $table->boolean('supervisor_approved')->default(false);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('production_job_id')->references('id')->on('production_jobs')->onDelete('cascade');
            $table->foreign('qc_inspector_id')->references('id')->on('crm_users')->onDelete('set null');
        });

        Schema::create('production_job_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('production_job_id');
            $table->unsignedBigInteger('crm_user_id')->nullable();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('production_job_id')->references('id')->on('production_jobs')->onDelete('cascade');
            $table->foreign('crm_user_id')->references('id')->on('crm_users')->onDelete('set null');
        });

        DB::table('production_facilities')->insert([
            ['name' => 'Lahore Production Facility', 'city' => 'Lahore', 'country' => 'Pakistan', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dubai Production Facility', 'city' => 'Dubai', 'country' => 'UAE', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Chicago Production Facility', 'city' => 'Chicago', 'country' => 'USA', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('production_job_logs');
        Schema::dropIfExists('production_first_sheet_checks');
        Schema::dropIfExists('production_jobs');
        Schema::dropIfExists('production_machines');
        Schema::dropIfExists('production_facilities');
    }
}
