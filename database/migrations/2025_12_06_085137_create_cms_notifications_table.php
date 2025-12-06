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
        Schema::create('cms_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Who receives this notification
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Notification type for filtering/grouping
            // Types: comment, comment_resolved, workflow_submitted, workflow_approved,
            //        workflow_rejected, workflow_published, mention, assignment, system
            $table->string('type');

            // Title and message
            $table->string('title');
            $table->text('body')->nullable();

            // Icon for display (Lucide icon name)
            $table->string('icon')->nullable();

            // Color theme: info, success, warning, error, neutral
            $table->string('color')->default('info');

            // What triggered this notification (polymorphic)
            // Can be Post, ContentVersion, EditorialComment, etc.
            $table->nullableMorphs('notifiable');

            // Link to navigate to when clicked
            $table->string('action_url')->nullable();
            $table->string('action_label')->nullable();

            // Who triggered this notification
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();

            // Read status
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index(['user_id', 'read_at', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_notifications');
    }
};
