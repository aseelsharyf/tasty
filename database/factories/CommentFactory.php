<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        $isRegistered = fake()->boolean(60); // 60% chance of registered user

        return [
            'post_id' => Post::factory(),
            'user_id' => $isRegistered ? User::factory() : null,
            'parent_id' => null,
            'content' => fake()->paragraph(rand(1, 4)),
            'status' => fake()->randomElement([
                Comment::STATUS_PENDING,
                Comment::STATUS_APPROVED,
                Comment::STATUS_APPROVED, // More likely to be approved
                Comment::STATUS_APPROVED,
            ]),
            'author_name' => $isRegistered ? null : fake()->name(),
            'author_email' => $isRegistered ? null : fake()->safeEmail(),
            'author_website' => $isRegistered ? null : fake()->optional(0.3)->url(),
            'author_ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'is_edited' => false,
            'edited_by' => null,
            'edited_at' => null,
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => Comment::STATUS_PENDING,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => Comment::STATUS_APPROVED,
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn () => [
            'status' => Comment::STATUS_SPAM,
            'content' => fake()->randomElement([
                'Check out this amazing deal! '.fake()->url(),
                'I made $5000 from home! '.fake()->url(),
                'Buy cheap pills at '.fake()->url(),
                fake()->paragraph().' '.fake()->url().' '.fake()->url().' '.fake()->url(),
            ]),
        ]);
    }

    public function trashed(): static
    {
        return $this->state(fn () => [
            'status' => Comment::STATUS_TRASHED,
        ]);
    }

    public function byRegisteredUser(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory(),
            'author_name' => null,
            'author_email' => null,
            'author_website' => null,
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'author_name' => fake()->name(),
            'author_email' => fake()->safeEmail(),
            'author_website' => fake()->optional(0.3)->url(),
        ]);
    }

    public function replyTo(Comment $parent): static
    {
        return $this->state(fn () => [
            'post_id' => $parent->post_id,
            'parent_id' => $parent->id,
        ]);
    }

    public function edited(): static
    {
        return $this->state(fn () => [
            'is_edited' => true,
            'edited_by' => User::factory(),
            'edited_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function forPost(Post $post): static
    {
        return $this->state(fn () => [
            'post_id' => $post->id,
        ]);
    }
}
