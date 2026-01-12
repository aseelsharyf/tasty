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
        // Realistic food article titles
        $titles = [
            'The Ghost Kitchen Feeding Malé After Dark',
            'A New Wave of Island Chefs',
            'The Secret to Perfect Mas Riha',
            'Where Locals Eat in Hulhumalé',
            'The Rise of Maldivian Fine Dining',
            'Street Food Revolution',
            'The Best Cafes in the Capital',
            'Cooking with Coconut: A Deep Dive',
            'The Fish Markets of Malé',
            'Traditional Recipes, Modern Twist',
            'The Art of Roshi Making',
            'Sustainable Seafood in the Maldives',
            'A Chef\'s Journey Home',
            'The Spice Traders of the Indian Ocean',
            'Breakfast Traditions Across the Atolls',
            'The Coffee Culture Revolution',
            'Farm to Table in Paradise',
            'The Women Behind Island Cuisine',
            'Late Night Eats in the City',
            'The Heritage of Hedhikaa',
        ];
        $title = fake()->randomElement($titles);

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
            'ON CULTURE',
            'THE SPREAD',
            'BITE CLUB',
            'FRESH CATCH',
            'SPICE ROUTE',
        ];

        // Subtitles - compelling secondary headlines
        $subtitles = [
            'A culinary journey through the islands',
            'The story behind the flavors',
            'Where tradition meets innovation',
            'Exploring local ingredients and techniques',
            'A taste of paradise',
            'Behind the scenes with local chefs',
            'The flavors that define a generation',
            'From ocean to table',
            'The art of island cooking',
            'Secrets from the kitchen',
        ];

        return [
            'author_id' => User::factory(),
            'language_code' => fn () => Language::first()?->code ?? 'en',
            'title' => $title,
            'kicker' => fake()->randomElement($kickers),
            'subtitle' => fake()->randomElement($subtitles),
            'slug' => \Illuminate\Support\Str::slug($title).'-'.fake()->unique()->randomNumber(5),
            'excerpt' => fake()->paragraph(2),
            'content' => $this->generateEditorJsContent(),
            'post_type' => fake()->randomElement([Post::TYPE_ARTICLE, Post::TYPE_RECIPE]),
            'status' => fake()->randomElement([Post::STATUS_DRAFT, Post::STATUS_PUBLISHED]),
            'workflow_status' => 'draft',
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

    /**
     * Set the post as in editorial review (draft status with review workflow).
     */
    public function inReview(): static
    {
        return $this->state(fn () => [
            'status' => Post::STATUS_DRAFT,
            'workflow_status' => 'review',
            'published_at' => null,
        ]);
    }

    /**
     * Set the post as in copydesk (draft status with copydesk workflow).
     */
    public function inCopydesk(): static
    {
        return $this->state(fn () => [
            'status' => Post::STATUS_DRAFT,
            'workflow_status' => 'copydesk',
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
