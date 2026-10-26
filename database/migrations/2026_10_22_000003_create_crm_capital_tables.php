<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Owners / partners of the business; each has a capital account and a drawings account.
        if (!Schema::hasTable('crm_capital_owners')) {
            Schema::create('crm_capital_owners', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->string('name');
                $table->decimal('share_percent', 5, 2)->nullable();     // ownership share, for information
                $table->string('notes', 500)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
        // Money (or value) an owner put into the business ("capital") or took out for themselves ("drawing").
        if (!Schema::hasTable('crm_capital_entries')) {
            Schema::create('crm_capital_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->unsignedBigInteger('owner_id')->index();
                $table->date('entry_date')->index();
                $table->string('type', 20);                             // capital | drawing
                $table->decimal('amount', 14, 2);
                $table->string('method', 60)->nullable();               // Cash, Bank Transfer... or "Non-cash / Opening"
                $table->unsignedBigInteger('bank_account_id')->nullable()->index();
                $table->string('reference', 120)->nullable();
                $table->string('note', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_capital_entries');
        Schema::dropIfExists('crm_capital_owners');
    }
};
