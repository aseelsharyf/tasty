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
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('featured_tag_id')->nullable()->after('featured_media_id')->constrained('tags')->nullOnDelete();
            $table->foreignId('sponsor_id')->nullable()->after('featured_tag_id')->constrained('sponsors')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sponsor_id');
            $table->dropConstrainedForeignId('featured_tag_id');
        });
    }
};
