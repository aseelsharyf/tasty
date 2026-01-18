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
        Schema::table('products', function (Blueprint $table) {
            // Short description (great for cards)
            $table->json('short_description')->nullable()->after('description');

            // Brand field
            $table->string('brand')->nullable()->after('short_description');

            // Availability enum: in_stock, out_of_stock, preorder
            $table->string('availability')->default('in_stock')->after('currency');

            // Is featured flag for homepage/sections
            $table->boolean('is_featured')->default(false)->after('is_active');
        });

        // Create product_images pivot table for multiple images
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_item_id')->constrained('media_items')->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'media_item_id']);
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['short_description', 'brand', 'availability', 'is_featured']);
        });
    }
};
