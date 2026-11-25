<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Auto-estimates created by the Alex chat bot have no human requester,
    // so requested_by must allow NULL (it was NOT NULL with no default).
    public function up(): void
    {
        if (Schema::hasColumn('estimate_tickets', 'requested_by')) {
            DB::statement('ALTER TABLE estimate_tickets MODIFY requested_by BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Left nullable on purpose; no safe automatic rollback.
    }
};
