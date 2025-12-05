<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, migrate existing data to JSON format
        $categories = DB::table('categories')->get();

        Schema::table('categories', function (Blueprint $table) {
            // Add new JSON columns
            $table->json('name_translations')->nullable();
            $table->json('description_translations')->nullable();
        });

        // Migrate existing data - wrap current values as default language (en)
        foreach ($categories as $category) {
            DB::table('categories')
                ->where('id', $category->id)
                ->update([
                    'name_translations' => json_encode(['en' => $category->name]),
                    'description_translations' => $category->description
                        ? json_encode(['en' => $category->description])
                        : null,
                ]);
        }

        // Now drop old columns and rename new ones
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['name', 'description']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->renameColumn('name_translations', 'name');
            $table->renameColumn('description_translations', 'description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get existing data before changing schema
        $categories = DB::table('categories')->get();

        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_varchar')->nullable();
            $table->text('description_text')->nullable();
        });

        // Extract default language value (en) from JSON
        foreach ($categories as $category) {
            $name = json_decode($category->name, true);
            $description = $category->description ? json_decode($category->description, true) : null;

            DB::table('categories')
                ->where('id', $category->id)
                ->update([
                    'name_varchar' => $name['en'] ?? array_values($name)[0] ?? 'Unnamed',
                    'description_text' => $description ? ($description['en'] ?? array_values($description)[0] ?? null) : null,
                ]);
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['name', 'description']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->renameColumn('name_varchar', 'name');
            $table->renameColumn('description_text', 'description');
        });
    }
};
