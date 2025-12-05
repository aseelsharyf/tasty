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
        // First, get existing data
        $tags = DB::table('tags')->get();

        Schema::table('tags', function (Blueprint $table) {
            // Add new JSON column
            $table->json('name_translations')->nullable();
        });

        // Migrate existing data - wrap current values as default language (en)
        foreach ($tags as $tag) {
            DB::table('tags')
                ->where('id', $tag->id)
                ->update([
                    'name_translations' => json_encode(['en' => $tag->name]),
                ]);
        }

        // Drop old column and rename new one
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->renameColumn('name_translations', 'name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get existing data before changing schema
        $tags = DB::table('tags')->get();

        Schema::table('tags', function (Blueprint $table) {
            $table->string('name_varchar')->nullable();
        });

        // Extract default language value (en) from JSON
        foreach ($tags as $tag) {
            $name = json_decode($tag->name, true);

            DB::table('tags')
                ->where('id', $tag->id)
                ->update([
                    'name_varchar' => $name['en'] ?? array_values($name)[0] ?? 'Unnamed',
                ]);
        }

        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->renameColumn('name_varchar', 'name');
        });
    }
};
