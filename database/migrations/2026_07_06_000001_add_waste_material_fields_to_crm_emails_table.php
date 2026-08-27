<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWasteMaterialFieldsToCrmEmailsTable extends Migration
{
    public function up()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->decimal('waste_material_percentage', 8, 2)->nullable()->default(0)->after('discount');
            $table->decimal('waste_material_amount', 10, 2)->nullable()->default(0)->after('waste_material_percentage');
        });
    }

    public function down()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->dropColumn(['waste_material_percentage', 'waste_material_amount']);
        });
    }
}
