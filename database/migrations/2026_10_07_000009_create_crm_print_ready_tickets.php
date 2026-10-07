<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('crm_users', 'print_ready_access')) {
            Schema::table('crm_users', function (Blueprint $table) {
                $table->boolean('print_ready_access')->default(false)->after('proposal_access');
            });
        }

        if (!Schema::hasTable('crm_print_ready_tickets')) {
            Schema::create('crm_print_ready_tickets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->unsignedBigInteger('manual_order_id')->index();
                $table->unsignedBigInteger('production_brief_id')->nullable()->index();
                $table->string('ticket_number', 40)->nullable();
                $table->string('job_number', 60)->nullable();
                $table->string('client_name')->nullable();
                $table->json('products')->nullable();
                $table->string('folder_path', 500)->nullable();
                $table->date('printers_deadline')->nullable();
                $table->date('clients_deadline')->nullable();
                $table->text('brief_notes')->nullable();
                $table->string('status', 30)->default('requested')->index(); // requested, in_progress, change_requested, completed
                $table->unsignedBigInteger('assigned_designer_id')->nullable()->index();
                $table->string('output_path', 500)->nullable();   // designer's print-ready server path
                $table->text('designer_note')->nullable();
                $table->text('change_request_note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('claimed_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('crm_print_ready_files')) {
            Schema::create('crm_print_ready_files', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id')->index();
                $table->string('path', 500);
                $table->string('name');
                $table->unsignedBigInteger('size')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('crm_print_ready_notes')) {
            Schema::create('crm_print_ready_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id')->index();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('type', 30)->default('note'); // note, status, change_request, file
                $table->text('body');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_print_ready_notes');
        Schema::dropIfExists('crm_print_ready_files');
        Schema::dropIfExists('crm_print_ready_tickets');
        if (Schema::hasColumn('crm_users', 'print_ready_access')) {
            Schema::table('crm_users', function (Blueprint $table) { $table->dropColumn('print_ready_access'); });
        }
    }
};
