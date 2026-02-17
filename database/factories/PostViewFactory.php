<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostView>
 */
class PostViewFactory extends Factory
{
    protected $model = PostView::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'referrer' => fake()->optional(0.6)->url(),
            'session_id' => fake()->sha256(),
            'viewed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function forPost(Post $post): static
    {
        return $this->state(fn () => ['post_id' => $post->id]);
    }

    public function byUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function viewedAt(\DateTimeInterface $date): static
    {
        return $this->state(fn () => ['viewed_at' => $date]);
    }

    public function today(): static
    {
        return $this->state(fn () => [
            'viewed_at' => fake()->dateTimeBetween('today', 'now'),
        ]);
    }

    public function thisWeek(): static
    {
        return $this->state(fn () => [
            'viewed_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
