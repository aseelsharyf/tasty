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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->json('label'); // Translatable
            $table->string('type')->default('custom'); // custom, page, post, category, external
            $table->string('url')->nullable(); // For custom/external links
            $table->string('linkable_type')->nullable(); // For polymorphic relations
            $table->unsignedBigInteger('linkable_id')->nullable();
            $table->string('target')->default('_self'); // _self, _blank
            $table->string('icon')->nullable(); // Optional icon class
            $table->json('css_classes')->nullable(); // Optional CSS classes
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['menu_id', 'parent_id', 'order']);
            $table->index(['linkable_type', 'linkable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
