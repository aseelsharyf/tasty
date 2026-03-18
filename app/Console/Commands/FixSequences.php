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
        $columns = DB::select("
            SELECT
                c.column_default,
                t.table_name,
                c.column_name
            FROM information_schema.tables t
            JOIN information_schema.columns c ON c.table_name = t.table_name AND c.table_schema = t.table_schema
            WHERE t.table_schema = 'public'
              AND t.table_type = 'BASE TABLE'
              AND c.column_default LIKE 'nextval%'
        ");

        $sequences = [];
        foreach ($columns as $col) {
            if (preg_match("/nextval\('([^']+)'/", $col->column_default, $matches)) {
                $col->sequence_name = $matches[1];
                $sequences[] = $col;
            }
        }

        $this->line('Found '.count($sequences).' sequences.');

        $fixed = 0;

        foreach ($sequences as $seq) {
            try {
                $maxId = DB::table($seq->table_name)->max($seq->column_name) ?? 0;

                if ($maxId === 0) {
                    continue;
                }

                $seqState = DB::selectOne(
                    'SELECT last_value, is_called FROM '.DB::getQueryGrammar()->wrap($seq->sequence_name)
                );

                $currentVal = $seqState->last_value;
                $isCalled = $seqState->is_called;

                if ($currentVal < $maxId || ($currentVal == $maxId && ! $isCalled)) {
                    DB::statement('SELECT setval(?, ?)', [$seq->sequence_name, $maxId]);
                    $this->line("<info>Fixed</info> {$seq->table_name}.{$seq->column_name}: {$currentVal} (is_called: ".($isCalled ? 'true' : 'false').") → {$maxId}");
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
