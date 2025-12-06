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
        Schema::create('editorial_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Which version this comment is on
            $table->foreignId('content_version_id')->constrained('content_versions')->cascadeOnDelete();

            // Who wrote this comment
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // The comment content
            $table->text('content');

            // Optional: reference to a specific block in the editor
            // This allows pointing to specific parts of the content
            $table->string('block_id')->nullable();

            // Comment type: general, revision_request, approval
            $table->string('type')->default('general');

            // Is this comment resolved?
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('content_version_id');
            $table->index('user_id');
            $table->index(['content_version_id', 'is_resolved']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('editorial_comments');
    }
};
