<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_portal_accounts')) return;

        Schema::create('crm_portal_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->string('email')->index();
            $table->string('name')->nullable();
            $table->string('password');
            $table->timestamp('credentials_sent_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_portal_accounts');
    }
};
