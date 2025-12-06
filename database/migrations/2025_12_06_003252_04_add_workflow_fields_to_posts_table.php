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
            // Reference to the currently active/published version
            $table->foreignId('active_version_id')
                ->nullable()
                ->constrained('content_versions')
                ->nullOnDelete();

            // Reference to the latest draft/working version
            $table->foreignId('draft_version_id')
                ->nullable()
                ->constrained('content_versions')
                ->nullOnDelete();

            // Current workflow status of the post (mirrors the draft version status for quick queries)
            $table->string('workflow_status')->default('draft')->after('status');

            // Index for workflow queries
            $table->index('workflow_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['active_version_id']);
            $table->dropForeign(['draft_version_id']);
            $table->dropIndex(['workflow_status']);
            $table->dropColumn(['active_version_id', 'draft_version_id', 'workflow_status']);
        });
    }
};
