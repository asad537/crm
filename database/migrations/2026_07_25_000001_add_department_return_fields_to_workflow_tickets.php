<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepartmentReturnFieldsToWorkflowTickets extends Migration
{
    public function up()
    {
        Schema::table('design_requirement_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('design_requirement_tickets', 'return_note')) $table->text('return_note')->nullable();
            if (!Schema::hasColumn('design_requirement_tickets', 'returned_by')) $table->unsignedBigInteger('returned_by')->nullable()->index();
            if (!Schema::hasColumn('design_requirement_tickets', 'returned_at')) $table->timestamp('returned_at')->nullable();
        });

        Schema::table('estimate_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('estimate_tickets', 'returned_to')) $table->string('returned_to', 20)->nullable()->index();
            if (!Schema::hasColumn('estimate_tickets', 'return_note')) $table->text('return_note')->nullable();
            if (!Schema::hasColumn('estimate_tickets', 'returned_by')) $table->unsignedBigInteger('returned_by')->nullable()->index();
            if (!Schema::hasColumn('estimate_tickets', 'returned_at')) $table->timestamp('returned_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('design_requirement_tickets', function (Blueprint $table) {
            $columns = array_values(array_filter(['return_note', 'returned_by', 'returned_at'], function ($column) {
                return Schema::hasColumn('design_requirement_tickets', $column);
            }));
            if ($columns) $table->dropColumn($columns);
        });

        Schema::table('estimate_tickets', function (Blueprint $table) {
            $columns = array_values(array_filter(['returned_to', 'return_note', 'returned_by', 'returned_at'], function ($column) {
                return Schema::hasColumn('estimate_tickets', $column);
            }));
            if ($columns) $table->dropColumn($columns);
        });
    }
}
