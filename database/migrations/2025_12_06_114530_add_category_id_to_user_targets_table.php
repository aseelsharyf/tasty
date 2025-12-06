<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_targets', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->nullOnDelete();
        });

        // Update unique constraint to include category_id
        Schema::table('user_targets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'period_type', 'period_start']);
            $table->unique(['user_id', 'category_id', 'period_type', 'period_start'], 'user_targets_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_targets', function (Blueprint $table) {
            $table->dropUnique('user_targets_unique');
            $table->unique(['user_id', 'period_type', 'period_start']);
        });

        Schema::table('user_targets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
