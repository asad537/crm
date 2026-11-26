<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('voice_call_queue')) {
            return;
        }
        Schema::create('voice_call_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('crm_email_id')->index();
            $table->string('phone', 30);
            $table->string('status', 20)->default('pending')->index(); // pending | calling | done | failed | cancelled
            $table->string('conversation_id', 100)->nullable();
            $table->unsignedInteger('duration_secs')->nullable();
            $table->string('note', 500)->nullable();
            $table->unsignedBigInteger('queued_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_call_queue');
    }
};
