<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update draft posts that were previously published to 'unpublished' status.
     * These are posts with status='draft' that have at least one content version
     * that was published, indicating they were previously live.
     */
    public function up(): void
    {
        DB::table('posts')
            ->where('status', 'draft')
            ->whereNull('published_at')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('content_versions')
                    ->whereColumn('content_versions.versionable_id', 'posts.id')
                    ->where('content_versions.versionable_type', 'App\\Models\\Post')
                    ->where('content_versions.workflow_status', 'published');
            })
            ->update(['status' => 'unpublished']);
    }

    /**
     * Reverse: set unpublished posts back to draft.
     */
    public function down(): void
    {
        DB::table('posts')
            ->where('status', 'unpublished')
            ->update(['status' => 'draft']);
    }
};
