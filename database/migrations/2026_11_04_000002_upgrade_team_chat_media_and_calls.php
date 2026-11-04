<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Team Chat: file details on messages, and voice / video calls (the call's log shows in the conversation). */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('crm_internal_messages', 'attachment_size')) {
            Schema::table('crm_internal_messages', function (Blueprint $table) {
                $table->string('attachment_mime', 100)->nullable()->after('attachment_name');
                $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
                $table->unsignedBigInteger('call_id')->nullable()->after('attachment_size');
            });
        }

        if (!Schema::hasTable('crm_team_calls')) {
            Schema::create('crm_team_calls', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->unsignedBigInteger('caller_id');
                $table->unsignedBigInteger('callee_id');
                $table->string('type', 10);      // audio | video
                $table->string('status', 20);    // ringing | active | ended | missed | declined
                $table->longText('offer');       // WebRTC session descriptions, exchanged through these two columns
                $table->longText('answer')->nullable();
                $table->timestamp('answered_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->unsignedBigInteger('ended_by')->nullable();
                $table->timestamp('caller_seen_at')->nullable();
                $table->timestamp('callee_seen_at')->nullable();
                $table->timestamps();
                $table->index(['callee_id', 'status']);
                $table->index(['caller_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_team_calls');
        if (Schema::hasColumn('crm_internal_messages', 'attachment_size')) {
            Schema::table('crm_internal_messages', function (Blueprint $table) {
                $table->dropColumn(['attachment_mime', 'attachment_size', 'call_id']);
            });
        }
    }
};
