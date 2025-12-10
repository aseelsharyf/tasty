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
        Schema::create('media_item_crops', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Reference to parent media item
            $table->foreignId('media_item_id')->constrained('media_items')->cascadeOnDelete();

            // Preset used (references Setting::getCropPresets() by name)
            $table->string('preset_name', 50);
            $table->string('preset_label', 100);

            // User-provided label for this crop version
            $table->string('label')->nullable();

            // Crop coordinates (percentage-based for responsiveness, 0-100)
            $table->decimal('crop_x', 8, 4);
            $table->decimal('crop_y', 8, 4);
            $table->decimal('crop_width', 8, 4);
            $table->decimal('crop_height', 8, 4);

            // Output dimensions (from preset at creation time)
            $table->integer('output_width');
            $table->integer('output_height');

            // User who created this crop
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('media_item_id');
            $table->index('preset_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_item_crops');
    }
};
