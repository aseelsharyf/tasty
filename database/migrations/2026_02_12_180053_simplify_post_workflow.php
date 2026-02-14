<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simplify post workflow: Draft → Copy Desk → Published, with Parked status.
     * Remove review, approved, unpublished statuses.
     * Add scheduled_copydesk_at column.
     */
    public function up(): void
    {
        // Remove all old workflow configs from settings so the hardcoded defaults are used
        $workflowKeys = DB::table('settings')->where('key', 'like', 'workflow.%')->pluck('key');
        DB::table('settings')->where('key', 'like', 'workflow.%')->delete();

        // Clear cached settings so the app uses hardcoded defaults immediately
        foreach ($workflowKeys as $key) {
            Cache::forget("setting.{$key}");
        }
        Cache::forget('settings.group.workflow');

        // Add scheduled_copydesk_at column
        Schema::table('posts', function (Blueprint $table) {
            $table->timestamp('scheduled_copydesk_at')->nullable()->after('scheduled_at');
        });

        // Migrate existing workflow data in posts
        DB::table('posts')
            ->where('workflow_status', 'review')
            ->update(['workflow_status' => 'copydesk']);

        DB::table('posts')
            ->where('workflow_status', 'approved')
            ->update(['workflow_status' => 'parked']);

        DB::table('posts')
            ->where('status', 'unpublished')
            ->update(['status' => 'draft']);

        // Migrate existing workflow data in content_versions
        DB::table('content_versions')
            ->where('workflow_status', 'review')
            ->update(['workflow_status' => 'copydesk']);

        DB::table('content_versions')
            ->where('workflow_status', 'approved')
            ->update(['workflow_status' => 'parked']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse workflow status changes
        DB::table('posts')
            ->where('workflow_status', 'parked')
            ->update(['workflow_status' => 'approved']);

        DB::table('content_versions')
            ->where('workflow_status', 'parked')
            ->update(['workflow_status' => 'approved']);

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('scheduled_copydesk_at');
        });
    }
};
