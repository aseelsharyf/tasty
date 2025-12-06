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
            // Target type: 'all' for combined, or specific type (post, image, audio, video)
            $table->string('target_type', 20)->default('post')->after('category_id');
        });

        // Update unique constraint to include target_type
        Schema::table('user_targets', function (Blueprint $table) {
            $table->dropUnique('user_targets_unique');
            $table->unique(
                ['user_id', 'category_id', 'target_type', 'period_type', 'period_start'],
                'user_targets_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_targets', function (Blueprint $table) {
            $table->dropUnique('user_targets_unique');
            $table->unique(
                ['user_id', 'category_id', 'period_type', 'period_start'],
                'user_targets_unique'
            );
        });

        Schema::table('user_targets', function (Blueprint $table) {
            $table->dropColumn('target_type');
        });
    }
};
