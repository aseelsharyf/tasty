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
        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Type: image, video_local, video_embed
            $table->string('type');

            // For video embeds (YouTube/Vimeo)
            $table->string('embed_url')->nullable();
            $table->string('embed_provider')->nullable();
            $table->string('embed_video_id')->nullable();
            $table->string('embed_thumbnail_url')->nullable();

            // Translatable fields (stored as JSON)
            $table->json('title')->nullable();
            $table->json('caption')->nullable();
            $table->json('description')->nullable();
            $table->json('alt_text')->nullable();

            // Credits - can be user reference OR free text
            $table->foreignId('credit_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('credit_name')->nullable();
            $table->string('credit_url')->nullable();
            $table->string('credit_role')->nullable();

            // Metadata
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('duration')->nullable();

            // Organization
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'created_at']);
            $table->index('folder_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_items');
    }
};
