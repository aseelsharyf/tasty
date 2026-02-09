<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSequences extends Command
{
    protected $signature = 'app:fix-sequences';

    protected $description = 'Reset all PostgreSQL sequences to match the current max ID in each table';

    public function handle(): int
    {
        $sequences = DB::select("
            SELECT
                seq.relname AS sequence_name,
                tab.relname AS table_name,
                attr.attname AS column_name
            FROM pg_class seq
            JOIN pg_depend dep ON dep.objid = seq.oid
            JOIN pg_class tab ON dep.refobjid = tab.oid
            JOIN pg_attribute attr ON attr.attrelid = tab.oid AND attr.attnum = dep.refobjsubid
            WHERE seq.relkind = 'S'
              AND dep.deptype = 'a'
        ");

        if (empty($sequences)) {
            $this->warn('No sequences found via pg_depend, falling back to information_schema...');
            $sequences = DB::select("
                SELECT
                    column_default,
                    table_name,
                    column_name
                FROM information_schema.columns
                WHERE column_default LIKE 'nextval%'
                  AND table_schema = 'public'
            ");

            foreach ($sequences as $seq) {
                preg_match("/nextval\('([^']+)'/", $seq->column_default, $matches);
                $seq->sequence_name = $matches[1] ?? null;
            }

            $sequences = array_filter($sequences, fn ($s) => $s->sequence_name !== null);
        }

        $fixed = 0;

        foreach ($sequences as $seq) {
            try {
                $maxId = DB::table($seq->table_name)->max($seq->column_name) ?? 0;

                if ($maxId === 0) {
                    continue;
                }

                $currentVal = DB::selectOne(
                    "SELECT last_value FROM " . DB::getQueryGrammar()->wrap($seq->sequence_name)
                )->last_value;

                if ($currentVal < $maxId) {
                    DB::statement("SELECT setval(?, ?)", [$seq->sequence_name, $maxId]);
                    $this->line("<info>Fixed</info> {$seq->table_name}.{$seq->column_name}: {$currentVal} → {$maxId}");
                    $fixed++;
                }
            } catch (\Exception $e) {
                $this->warn("Skipped {$seq->table_name}: {$e->getMessage()}");
            }
        }

        if ($fixed === 0) {
            $this->info('All sequences are already in sync.');
        } else {
            $this->info("Fixed {$fixed} sequence(s).");
        }

        return self::SUCCESS;
    }
}
