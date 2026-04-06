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
            $table->boolean('has_video')->default(false)->after('cover_video_id');
        });

        // Compute has_video for existing posts
        $posts = \App\Models\Post::withTrashed()->get();
        foreach ($posts as $post) {
            $post->update(['has_video' => $post->computeHasVideo()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('has_video');
        });
    }
};
