<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('design_jobs', 'receive_date')) {
                $table->date('receive_date')->nullable()->after('title');
            }
            if (!Schema::hasColumn('design_jobs', 'client_approval_date')) {
                $table->date('client_approval_date')->nullable()->after('receive_date');
            }
            if (!Schema::hasColumn('design_jobs', 'due_date')) {
                $table->date('due_date')->nullable()->after('estimated_delivery_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('design_jobs', function (Blueprint $table) {
            foreach (['receive_date', 'client_approval_date', 'due_date'] as $c) {
                if (Schema::hasColumn('design_jobs', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
