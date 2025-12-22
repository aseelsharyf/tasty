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
        Schema::create('sponsors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->json('name');
            $table->string('slug')->unique();
            $table->json('url')->nullable();
            $table->foreignId('featured_media_id')->nullable()->constrained('media_items')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('order');
        });

        // Pivot table for sponsor-post relationship
        Schema::create('post_sponsor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sponsor_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['post_id', 'sponsor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_sponsor');
        Schema::dropIfExists('sponsors');
    }
};
