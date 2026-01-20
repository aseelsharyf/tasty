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
        Schema::create('recipe_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Submission type: single or composite
            $table->string('submission_type')->default('single'); // single, composite

            // Submitter information
            $table->string('submitter_name');
            $table->string('submitter_email');
            $table->string('submitter_phone')->nullable();
            $table->boolean('is_chef')->default(true);
            $table->string('chef_name')->nullable();

            // Recipe basic info
            $table->string('recipe_name');
            $table->string('slug');
            $table->text('description');
            $table->unsignedInteger('prep_time')->nullable();
            $table->unsignedInteger('cook_time')->nullable();
            $table->unsignedInteger('total_time')->nullable();
            $table->unsignedInteger('servings')->nullable();

            // Categories and meal times (JSON arrays of IDs)
            $table->json('categories')->nullable();
            $table->json('meal_times')->nullable();

            // Ingredients (JSON structure with groups)
            $table->json('ingredients')->nullable();

            // Instructions (JSON structure with groups)
            $table->json('instructions')->nullable();

            // For composite meals: child recipes
            $table->json('child_recipes')->nullable();
            $table->foreignId('parent_submission_id')->nullable()->constrained('recipe_submissions')->nullOnDelete();

            // Media
            $table->string('image_path')->nullable();

            // Workflow
            $table->string('status')->default('pending'); // pending, approved, rejected, converted
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            // Reference to converted post
            $table->foreignId('converted_post_id')->nullable()->constrained('posts')->nullOnDelete();

            $table->timestamps();

            $table->index('status');
            $table->index('submission_type');
            $table->index('submitter_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_submissions');
    }
};
