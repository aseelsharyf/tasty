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
        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('route_name')->unique()->comment('Named route or special key like "homepage"');
            $table->string('page_type')->default('static')->comment('static, dynamic, or model');
            $table->json('meta_title')->nullable()->comment('Translatable meta title');
            $table->json('meta_description')->nullable()->comment('Translatable meta description');
            $table->json('meta_keywords')->nullable()->comment('Translatable keywords array');
            $table->json('og_title')->nullable()->comment('Open Graph title');
            $table->json('og_description')->nullable()->comment('Open Graph description');
            $table->string('og_image')->nullable()->comment('Open Graph image path');
            $table->string('og_type')->default('website');
            $table->string('twitter_card')->default('summary_large_image');
            $table->json('twitter_title')->nullable();
            $table->json('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->nullable()->comment('robots meta directive');
            $table->json('json_ld')->nullable()->comment('Custom JSON-LD structured data');
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('page_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
    }
};
