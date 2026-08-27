<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPredesignFieldsToCrmEmails extends Migration
{
    public function up()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->string('printing')->nullable();
            $table->string('finish_size')->nullable();
            $table->string('open_size')->nullable();
            $table->text('csr_comment')->nullable();
            $table->string('website')->nullable();
            $table->decimal('price_offered', 15, 2)->nullable();
            $table->text('inquiry_quantities')->nullable();
        });
    }

    public function down()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->dropColumn(['printing', 'finish_size', 'open_size', 'csr_comment', 'website', 'price_offered', 'inquiry_quantities']);
        });
    }
}
