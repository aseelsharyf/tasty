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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();

            // Core content
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->json('content')->nullable(); // Editor.js block data

            // Post type and status
            $table->string('post_type')->default('article'); // article, recipe
            $table->string('status')->default('draft'); // draft, pending, published, scheduled

            // Publishing
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();

            // Featured image (will use Spatie Media Library, but keep reference)
            $table->unsignedBigInteger('featured_image_id')->nullable();

            // Recipe-specific fields (stored as JSON for flexibility)
            $table->json('recipe_meta')->nullable(); // prep_time, cook_time, servings, difficulty, ingredients, nutrition

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'post_type']);
            $table->index('published_at');
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
