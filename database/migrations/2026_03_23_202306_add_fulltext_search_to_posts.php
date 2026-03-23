<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            Schema::table('posts', function ($table) {
                $table->text('searchable_text')->nullable();
            });

            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // Add a plain text column for search
        DB::statement('ALTER TABLE posts ADD COLUMN searchable_text TEXT');

        // Create a function to extract text from Editor.js JSON and build searchable_text
        DB::statement("
            CREATE OR REPLACE FUNCTION posts_update_searchable_text() RETURNS TRIGGER AS \$\$
            BEGIN
                NEW.searchable_text :=
                    COALESCE(NEW.title::text, '') || ' ' ||
                    COALESCE(NEW.subtitle, '') || ' ' ||
                    COALESCE(NEW.excerpt, '') || ' ' ||
                    COALESCE(NEW.kicker, '') || ' ' ||
                    COALESCE(
                        (SELECT string_agg(elem->>'text', ' ')
                         FROM jsonb_array_elements(
                             CASE WHEN jsonb_typeof(NEW.content::jsonb->'blocks') = 'array' THEN NEW.content::jsonb->'blocks' ELSE '[]'::jsonb END
                         ) AS elem
                         WHERE elem->>'text' IS NOT NULL
                        ), ''
                    );
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // Create trigger to auto-update searchable_text on insert/update
        DB::statement('
            CREATE TRIGGER posts_searchable_text_trigger
            BEFORE INSERT OR UPDATE OF title, subtitle, excerpt, kicker, content
            ON posts
            FOR EACH ROW
            EXECUTE FUNCTION posts_update_searchable_text()
        ');

        // Backfill existing posts
        DB::statement("
            UPDATE posts SET searchable_text =
                COALESCE(title::text, '') || ' ' ||
                COALESCE(subtitle, '') || ' ' ||
                COALESCE(excerpt, '') || ' ' ||
                COALESCE(kicker, '') || ' ' ||
                COALESCE(
                    (SELECT string_agg(elem->>'text', ' ')
                     FROM jsonb_array_elements(
                         CASE WHEN jsonb_typeof(content::jsonb->'blocks') = 'array' THEN content::jsonb->'blocks' ELSE '[]'::jsonb END
                     ) AS elem
                     WHERE elem->>'text' IS NOT NULL
                    ), ''
                )
        ");

        // GIN index for full-text search using tsvector
        DB::statement("
            CREATE INDEX posts_searchable_tsvector_idx
            ON posts
            USING GIN (to_tsvector('english', COALESCE(searchable_text, '')))
        ");

        // GIN trigram index for fuzzy/typo-tolerant search
        DB::statement('
            CREATE INDEX posts_searchable_trgm_idx
            ON posts
            USING GIN (searchable_text gin_trgm_ops)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS posts_searchable_text_trigger ON posts');
            DB::statement('DROP FUNCTION IF EXISTS posts_update_searchable_text()');
            DB::statement('DROP INDEX IF EXISTS posts_searchable_trgm_idx');
            DB::statement('DROP INDEX IF EXISTS posts_searchable_tsvector_idx');
        }

        Schema::table('posts', function ($table) {
            $table->dropColumn('searchable_text');
        });
    }
};
