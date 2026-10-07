<?php

namespace Database\Factories;

use App\Models\ContentVersion;
use App\Models\Post;
use App\Models\ScheduledLayoutAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScheduledLayoutAssignment>
 */
class ScheduledLayoutAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'content_version_id' => function (array $attributes): int {
                $post = Post::query()->findOrFail($attributes['post_id']);

                return ContentVersion::factory()->forPost($post)->create()->id;
            },
            'layout_type' => 'homepage',
            'page_layout_id' => null,
            'section_id' => fake()->uuid(),
            'slot_index' => fake()->numberBetween(0, 5),
            'scheduled_at' => fake()->dateTimeBetween('+1 hour', '+1 week'),
            'status' => ScheduledLayoutAssignment::STATUS_PENDING,
            'processed_at' => null,
            'last_error' => null,
        ];
    }
}
