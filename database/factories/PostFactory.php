<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        // Keep titles short (3-6 words)
        $title = fake()->sentence(rand(3, 6), false);

        // Kickers are short uppercase labels
        $kickers = [
            'TASTY FEATURE',
            'FOOD REVIEW',
            'CHEF PROFILE',
            'ISLAND CUISINE',
            'RECIPE',
            'DINING OUT',
            'KITCHEN TIPS',
            'LOCAL FLAVORS',
            'SEAFOOD',
            'STREET FOOD',
            null, // Sometimes no kicker
            null,
        ];

        return [
            'author_id' => User::factory(),
            'language_code' => fn () => Language::first()?->code ?? 'en',
            'title' => $title,
            'kicker' => fake()->randomElement($kickers),
            'subtitle' => fake()->optional(0.5)->sentence(),
            'slug' => \Illuminate\Support\Str::slug($title).'-'.fake()->unique()->randomNumber(5),
            'excerpt' => fake()->paragraph(2),
            'content' => $this->generateEditorJsContent(),
            'post_type' => fake()->randomElement([Post::TYPE_ARTICLE, Post::TYPE_RECIPE]),
            'status' => fake()->randomElement([Post::STATUS_DRAFT, Post::STATUS_PUBLISHED, Post::STATUS_PENDING]),
            'published_at' => fn (array $attributes) => $attributes['status'] === Post::STATUS_PUBLISHED ? fake()->dateTimeBetween('-1 year', 'now') : null,
            'allow_comments' => true,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => Post::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => Post::STATUS_PENDING,
            'published_at' => null,
        ]);
    }

    public function article(): static
    {
        return $this->state(fn () => [
            'post_type' => Post::TYPE_ARTICLE,
            'custom_fields' => null,
        ]);
    }

    public function recipe(): static
    {
        return $this->state(fn () => [
            'post_type' => Post::TYPE_RECIPE,
            'custom_fields' => [
                'prep_time' => fake()->numberBetween(5, 30),
                'cook_time' => fake()->numberBetween(10, 120),
                'servings' => fake()->numberBetween(2, 8),
                'difficulty' => fake()->randomElement(['easy', 'medium', 'hard']),
            ],
        ]);
    }

    public function withLanguage(string $code): static
    {
        return $this->state(fn () => [
            'language_code' => $code,
        ]);
    }

    protected function generateEditorJsContent(): array
    {
        $blocks = [];

        // Add 2-5 paragraphs
        $paragraphCount = rand(2, 5);
        for ($i = 0; $i < $paragraphCount; $i++) {
            $blocks[] = [
                'id' => fake()->unique()->regexify('[a-zA-Z0-9]{10}'),
                'type' => 'paragraph',
                'data' => [
                    'text' => fake()->paragraph(rand(3, 6)),
                ],
            ];
        }

        return [
            'time' => now()->timestamp * 1000,
            'blocks' => $blocks,
            'version' => '2.28.0',
        ];
    }
}
