<?php

namespace Database\Factories;

use App\Models\ContentVersion;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentVersion>
 */
class ContentVersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'versionable_type' => Post::class,
            'versionable_id' => Post::factory(),
            'version_number' => 1,
            'content_snapshot' => [
                'title' => fake()->sentence(),
                'subtitle' => fake()->optional()->sentence(),
                'excerpt' => fake()->paragraph(),
                'content' => [
                    'time' => now()->timestamp * 1000,
                    'blocks' => [
                        ['id' => fake()->uuid(), 'type' => 'paragraph', 'data' => ['text' => fake()->paragraph()]],
                    ],
                    'version' => '2.28.0',
                ],
                'meta_title' => fake()->optional()->sentence(),
                'meta_description' => fake()->optional()->text(150),
                'custom_fields' => [],
                'category_ids' => [1], // Default category for approval validation
                'tag_ids' => [1], // Default tag for approval validation
            ],
            'workflow_status' => ContentVersion::STATUS_DRAFT,
            'is_active' => false,
            'created_by' => User::factory(),
            'version_note' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'workflow_status' => ContentVersion::STATUS_DRAFT,
            'is_active' => false,
        ]);
    }

    public function inReview(): static
    {
        return $this->state(fn () => [
            'workflow_status' => ContentVersion::STATUS_REVIEW,
            'is_active' => false,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'workflow_status' => ContentVersion::STATUS_APPROVED,
            'is_active' => false,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'workflow_status' => ContentVersion::STATUS_PUBLISHED,
            'is_active' => true,
        ]);
    }

    public function forPost(Post $post): static
    {
        return $this->state(fn () => [
            'versionable_type' => Post::class,
            'versionable_id' => $post->id,
        ]);
    }

    public function withVersionNumber(int $number): static
    {
        return $this->state(fn () => [
            'version_number' => $number,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'is_active' => true,
        ]);
    }
}
