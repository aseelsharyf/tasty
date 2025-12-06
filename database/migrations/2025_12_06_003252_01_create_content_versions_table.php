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
        Schema::create('content_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Polymorphic relationship to content (Post, Page, etc.)
            $table->morphs('versionable');

            // Version number (auto-incremented per content item)
            $table->unsignedInteger('version_number');

            // Snapshot of content at this version (JSON)
            // Contains all fields: title, subtitle, excerpt, content, recipe_meta, etc.
            $table->json('content_snapshot');

            // Workflow status: draft, review, copydesk, approved, rejected
            $table->string('workflow_status')->default('draft');

            // Is this the currently published/active version?
            $table->boolean('is_active')->default(false);

            // Who created this version
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            // Optional note for this version
            $table->text('version_note')->nullable();

            $table->timestamps();

            // Unique version number per content item
            $table->unique(['versionable_type', 'versionable_id', 'version_number'], 'content_versions_unique');

            // Index for finding active version
            $table->index(['versionable_type', 'versionable_id', 'is_active'], 'content_versions_active');

            // Index for workflow queries
            $table->index(['versionable_type', 'versionable_id', 'workflow_status'], 'content_versions_workflow');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_versions');
    }
};
