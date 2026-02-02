<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_placements', function (Blueprint $table) {
            $table->renameColumn('slot_number', 'slot');
        });
    }

    public function down(): void
    {
        Schema::table('ad_placements', function (Blueprint $table) {
            $table->renameColumn('slot', 'slot_number');
        });
    }
};
