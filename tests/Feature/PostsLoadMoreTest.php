<?php

use App\Models\Language;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Language::create([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
    ]);
});

it('loads more recent posts without optional category or tag filters', function () {
    Post::factory()
        ->count(3)
        ->published()
        ->sequence(
            ['title' => 'First Post', 'published_at' => now()->subMinutes(1)],
            ['title' => 'Second Post', 'published_at' => now()->subMinutes(2)],
            ['title' => 'Third Post', 'published_at' => now()->subMinutes(3)],
        )
        ->create();

    $response = $this->postJson('/api/posts/load-more', [
        'action' => 'recent',
        'page' => 1,
        'perPage' => 2,
        'excludeIds' => [],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('posts.0.title', 'First Post')
        ->assertJsonPath('posts.1.title', 'Second Post')
        ->assertJsonPath('hasMore', true);
});

it('uses exclude ids as the cursor when loading more posts', function () {
    $posts = Post::factory()
        ->count(5)
        ->published()
        ->sequence(
            ['title' => 'Post One', 'published_at' => now()->subMinutes(1)],
            ['title' => 'Post Two', 'published_at' => now()->subMinutes(2)],
            ['title' => 'Post Three', 'published_at' => now()->subMinutes(3)],
            ['title' => 'Post Four', 'published_at' => now()->subMinutes(4)],
            ['title' => 'Post Five', 'published_at' => now()->subMinutes(5)],
        )
        ->create();

    $response = $this->postJson('/api/posts/load-more', [
        'action' => 'recent',
        'page' => 2,
        'perPage' => 2,
        'excludeIds' => $posts->take(2)->pluck('id')->all(),
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('posts.0.title', 'Post Three')
        ->assertJsonPath('posts.1.title', 'Post Four')
        ->assertJsonPath('hasMore', true);
});
