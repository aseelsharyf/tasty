<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->updateWorkflows(function (array $workflow): array {
            $stateKeys = collect($workflow['states'] ?? [])->pluck('key');

            if (! $stateKeys->contains('parked')) {
                return $workflow;
            }

            if (! $stateKeys->contains('scheduled')) {
                $workflow['states'][] = [
                    'key' => 'scheduled',
                    'label' => 'Scheduled',
                    'color' => 'warning',
                    'icon' => 'i-lucide-calendar-clock',
                ];
            }

            $transitions = collect($workflow['transitions'] ?? []);
            $roles = $workflow['publish_roles'] ?? ['Editor', 'Admin', 'Developer'];

            if (! $transitions->contains(fn (array $transition): bool => $transition['from'] === 'parked' && $transition['to'] === 'published')) {
                $workflow['transitions'][] = [
                    'from' => 'parked',
                    'to' => 'published',
                    'roles' => $roles,
                    'label' => 'Publish',
                ];
            }

            if (! $transitions->contains(fn (array $transition): bool => $transition['from'] === 'parked' && $transition['to'] === 'scheduled')) {
                $workflow['transitions'][] = [
                    'from' => 'parked',
                    'to' => 'scheduled',
                    'roles' => $roles,
                    'label' => 'Schedule',
                ];
            }

            return $workflow;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Existing workflow settings may already contain these transitions.
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $callback
     */
    private function updateWorkflows(callable $callback): void
    {
        DB::table('settings')
            ->where('key', 'workflow.default')
            ->orWhere('key', 'like', 'workflow.post_type.%')
            ->get()
            ->each(function (object $setting) use ($callback): void {
                $workflow = is_string($setting->value)
                    ? json_decode($setting->value, true)
                    : $setting->value;

                if (! is_array($workflow)) {
                    return;
                }

                DB::table('settings')
                    ->where('id', $setting->id)
                    ->update([
                        'value' => json_encode($callback($workflow), JSON_THROW_ON_ERROR),
                        'updated_at' => now(),
                    ]);

                Cache::forget("setting.{$setting->key}");
            });
    }
};
