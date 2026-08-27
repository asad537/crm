<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryToVendorsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('vendors', 'category')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->string('category', 100)->nullable()->after('name');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('vendors', 'category')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
}
