<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only rename if slot_number exists (local dev environment)
        if (Schema::hasColumn('ad_placements', 'slot_number')) {
            Schema::table('ad_placements', function (Blueprint $table) {
                $table->renameColumn('slot_number', 'slot');
            });
        }
        // If slot already exists, no action needed (staging/production)
    }

    public function down(): void
    {
        if (Schema::hasColumn('ad_placements', 'slot')) {
            Schema::table('ad_placements', function (Blueprint $table) {
                $table->renameColumn('slot', 'slot_number');
            });
        }
    }
};
