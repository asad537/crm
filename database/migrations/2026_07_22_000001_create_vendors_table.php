<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
class CreateVendorsTable extends Migration { public function up(){ Schema::create('vendors',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('workspace_id')->nullable()->index();$t->string('name');$t->string('phone')->nullable();$t->string('email')->nullable();$t->text('address')->nullable();$t->text('notes')->nullable();$t->timestamps();$t->index(['workspace_id','name']);}); } public function down(){Schema::dropIfExists('vendors');} }
