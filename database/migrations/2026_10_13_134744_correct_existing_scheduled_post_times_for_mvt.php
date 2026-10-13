<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('posts')
                ->where('status', 'scheduled')
                ->whereNotNull('scheduled_at')
                ->orderBy('id')
                ->eachById(function (object $post): void {
                    DB::table('posts')
                        ->where('id', $post->id)
                        ->update([
                            'scheduled_at' => Carbon::parse($post->scheduled_at, 'UTC')->subHours(5),
                        ]);
                });

            DB::table('scheduled_layout_assignments')
                ->where('status', 'pending')
                ->orderBy('id')
                ->eachById(function (object $assignment): void {
                    DB::table('scheduled_layout_assignments')
                        ->where('id', $assignment->id)
                        ->update([
                            'scheduled_at' => Carbon::parse($assignment->scheduled_at, 'UTC')->subHours(5),
                        ]);
                });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('posts')
                ->where('status', 'scheduled')
                ->whereNotNull('scheduled_at')
                ->orderBy('id')
                ->eachById(function (object $post): void {
                    DB::table('posts')
                        ->where('id', $post->id)
                        ->update([
                            'scheduled_at' => Carbon::parse($post->scheduled_at, 'UTC')->addHours(5),
                        ]);
                });

            DB::table('scheduled_layout_assignments')
                ->where('status', 'pending')
                ->orderBy('id')
                ->eachById(function (object $assignment): void {
                    DB::table('scheduled_layout_assignments')
                        ->where('id', $assignment->id)
                        ->update([
                            'scheduled_at' => Carbon::parse($assignment->scheduled_at, 'UTC')->addHours(5),
                        ]);
                });
        });
    }
};
