<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCreatedByToCrmEmails extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('crm_emails', 'created_by')) {
            Schema::table('crm_emails', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->after('workspace_id')->index();
            });
        }

        DB::table('crm_emails')
            ->where(function ($query) {
                $query->where('source', 'crm_inquiry')
                    ->orWhere(function ($legacy) {
                        $legacy->where('source', 'manual')
                            ->whereNotNull('inquiry_quantities');
                    });
            })
            ->whereNull('created_by')
            ->update(['created_by' => DB::raw('COALESCE(assigned_by, assigned_to)')]);
    }

    public function down()
    {
        if (Schema::hasColumn('crm_emails', 'created_by')) {
            Schema::table('crm_emails', function (Blueprint $table) {
                $table->dropIndex(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
}
