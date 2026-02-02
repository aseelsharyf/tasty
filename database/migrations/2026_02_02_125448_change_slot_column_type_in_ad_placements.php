<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change column type from smallint to string using raw SQL for PostgreSQL
        DB::statement('ALTER TABLE ad_placements ALTER COLUMN slot TYPE varchar(50) USING slot::varchar');
    }

    public function down(): void
    {
        // Note: This down migration may fail if there are non-numeric values in the slot column
        DB::statement('ALTER TABLE ad_placements ALTER COLUMN slot TYPE smallint USING slot::smallint');
    }
};
