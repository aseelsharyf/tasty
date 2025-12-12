<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'uuid' => Str::uuid(),
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => fake()->paragraphs(5, true),
            'layout' => 'default',
            'status' => Page::STATUS_DRAFT,
            'is_blade' => false,
            'author_id' => User::factory(),
            'meta_title' => null,
            'meta_description' => fake()->sentence(),
            'published_at' => null,
        ];
    }

    /**
     * Set the page as published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Page::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Set the page as a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Page::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }

    /**
     * Set the page to use Blade rendering.
     */
    public function withBlade(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blade' => true,
            'content' => '<div class="prose max-w-none">
    <h1>{{ $page->title }}</h1>
    <p>This is a Blade-rendered page.</p>
    <x-ui.button>Click Me</x-ui.button>
</div>',
        ]);
    }

    /**
     * Set a specific layout.
     */
    public function withLayout(string $layout): static
    {
        return $this->state(fn (array $attributes) => [
            'layout' => $layout,
        ]);
    }
}
