<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if slot column needs type change (only needed if it's not already varchar)
        $columnType = DB::selectOne("
            SELECT data_type
            FROM information_schema.columns
            WHERE table_name = 'ad_placements' AND column_name = 'slot'
        ");

        if ($columnType && $columnType->data_type !== 'character varying') {
            // Change column type from smallint to string using raw SQL for PostgreSQL
            DB::statement('ALTER TABLE ad_placements ALTER COLUMN slot TYPE varchar(50) USING slot::varchar');
        }
    }

    public function down(): void
    {
        // Note: This down migration may fail if there are non-numeric values in the slot column
        DB::statement('ALTER TABLE ad_placements ALTER COLUMN slot TYPE smallint USING slot::smallint');
    }
};
