<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        // Get published posts
        $posts = Post::published()->get();

        if ($posts->isEmpty()) {
            $this->command->warn('No published posts found. Run PostSeeder first.');

            return;
        }

        // Get some users for registered comments
        $users = User::all();

        foreach ($posts as $post) {
            // Create 2-8 root comments per post
            $commentCount = rand(2, 8);

            $rootComments = Comment::factory()
                ->count($commentCount)
                ->forPost($post)
                ->create([
                    'user_id' => fn () => fake()->boolean(60) ? $users->random()->id : null,
                ]);

            // Add some replies to random root comments
            foreach ($rootComments as $rootComment) {
                if (fake()->boolean(40)) { // 40% chance of having replies
                    $replyCount = rand(1, 3);

                    $replies = [];
                    for ($i = 0; $i < $replyCount; $i++) {
                        $replies[] = Comment::factory()
                            ->replyTo($rootComment)
                            ->create([
                                'user_id' => fake()->boolean(60) ? $users->random()->id : null,
                            ]);
                    }

                    // Occasionally add a reply to a reply (2 levels deep)
                    if (! empty($replies) && fake()->boolean(20)) {
                        Comment::factory()
                            ->replyTo($replies[0])
                            ->create([
                                'user_id' => fake()->boolean(60) ? $users->random()->id : null,
                            ]);
                    }
                }
            }
        }

        // Add some specific test cases

        // Add pending comments for moderation queue testing
        $randomPosts = $posts->random(min(5, $posts->count()));
        foreach ($randomPosts as $post) {
            Comment::factory()
                ->count(rand(1, 3))
                ->pending()
                ->forPost($post)
                ->anonymous()
                ->create();
        }

        // Add some spam comments
        $spamPosts = $posts->random(min(3, $posts->count()));
        foreach ($spamPosts as $post) {
            Comment::factory()
                ->spam()
                ->forPost($post)
                ->anonymous()
                ->create();
        }

        // Add some trashed comments
        Comment::factory()
            ->count(5)
            ->trashed()
            ->create([
                'post_id' => fn () => $posts->random()->id,
            ]);

        // Add some edited comments
        Comment::factory()
            ->count(3)
            ->approved()
            ->edited()
            ->create([
                'post_id' => fn () => $posts->random()->id,
                'edited_by' => fn () => $users->first()->id,
            ]);

        $totalComments = Comment::count();
        $this->command->info("Created {$totalComments} comments across {$posts->count()} posts.");
        $this->command->info('  - Pending: '.Comment::pending()->count());
        $this->command->info('  - Approved: '.Comment::approved()->count());
        $this->command->info('  - Spam: '.Comment::spam()->count());
        $this->command->info('  - Trashed: '.Comment::where('status', Comment::STATUS_TRASHED)->count());
    }
}
