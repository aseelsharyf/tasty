<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * For each post: keep only one version (prefer active, then latest),
     * delete the rest, and set all posts to draft status.
     */
    public function up(): void
    {
        // Remove all stale workflow configs from settings so the hardcoded defaults are used
        $workflowKeys = DB::table('settings')->where('key', 'like', 'workflow.%')->pluck('key');
        DB::table('settings')->where('key', 'like', 'workflow.%')->delete();
        foreach ($workflowKeys as $key) {
            Cache::forget("setting.{$key}");
        }
        Cache::forget('settings.group.workflow');

        $posts = DB::table('posts')->get();

        foreach ($posts as $post) {
            $versions = DB::table('content_versions')
                ->where('versionable_type', 'App\\Models\\Post')
                ->where('versionable_id', $post->id)
                ->orderByDesc('version_number')
                ->get();

            if ($versions->isEmpty()) {
                continue;
            }

            if ($versions->count() > 1) {
                Log::warning("Post [{$post->id}] has {$versions->count()} versions. Keeping one.", [
                    'post_id' => $post->id,
                    'version_ids' => $versions->pluck('id')->toArray(),
                ]);
            }

            // Prefer the active version, otherwise use the latest
            $keepVersion = $versions->firstWhere('is_active', true) ?? $versions->first();

            // Delete all other versions
            $otherVersionIds = $versions->where('id', '!=', $keepVersion->id)->pluck('id');

            if ($otherVersionIds->isNotEmpty()) {
                DB::table('workflow_transitions')
                    ->whereIn('content_version_id', $otherVersionIds)
                    ->delete();

                DB::table('editorial_comments')
                    ->whereIn('content_version_id', $otherVersionIds)
                    ->delete();

                DB::table('content_versions')
                    ->whereIn('id', $otherVersionIds)
                    ->delete();
            }

            // Reset the kept version to version 1, draft status
            DB::table('content_versions')
                ->where('id', $keepVersion->id)
                ->update([
                    'version_number' => 1,
                    'workflow_status' => 'draft',
                    'is_active' => false,
                ]);

            // Clean up transitions for the kept version
            DB::table('workflow_transitions')
                ->where('content_version_id', $keepVersion->id)
                ->delete();

            // Set post to draft
            DB::table('posts')
                ->where('id', $post->id)
                ->update([
                    'status' => 'draft',
                    'workflow_status' => 'draft',
                    'active_version_id' => null,
                    'draft_version_id' => $keepVersion->id,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible - data was deleted
    }
};
