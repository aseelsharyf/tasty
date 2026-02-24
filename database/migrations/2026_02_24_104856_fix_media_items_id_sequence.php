<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reset the media_items id sequence to match the actual max id.
     * This fixes "duplicate key value violates unique constraint" errors
     * caused by the sequence falling out of sync with existing data.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('media_items', 'id'), COALESCE((SELECT MAX(id) FROM media_items), 0) + 1, false)");
        }
    }

    public function down(): void
    {
        // No rollback needed
    }
};
