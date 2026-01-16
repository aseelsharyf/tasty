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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->json('title');
            $table->string('slug')->unique();
            $table->json('description')->nullable();
            $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('featured_media_id')->nullable()->constrained('media_items')->nullOnDelete();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('affiliate_url', 2048);
            $table->string('affiliate_source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Future checkout fields (nullable for now)
            $table->string('sku')->nullable()->unique();
            $table->unsignedInteger('stock_quantity')->nullable();
            $table->boolean('track_inventory')->default(false);
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->json('metadata')->nullable();

            $table->index('is_active');
            $table->index('order');
            $table->index(['product_category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
