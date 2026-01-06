<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrates posts with 'pending' status to 'draft' status.
     * The workflow_status field now tracks editorial review stages (review, copydesk, approved),
     * while status only tracks publication state (draft, published, scheduled).
     */
    public function up(): void
    {
        // Update all posts with status='pending' to status='draft'
        // Their workflow_status already correctly reflects their editorial stage
        DB::table('posts')
            ->where('status', 'pending')
            ->update(['status' => 'draft']);
    }

    /**
     * Reverse the migrations.
     *
     * Note: This reversal is a best-effort attempt. Posts that were originally 'pending'
     * and are now in 'review' or 'approved' workflow status will be reverted.
     */
    public function down(): void
    {
        // Revert posts that are in review/copydesk/approved workflow status back to pending
        DB::table('posts')
            ->where('status', 'draft')
            ->whereIn('workflow_status', ['review', 'copydesk', 'approved'])
            ->update(['status' => 'pending']);
    }
};
