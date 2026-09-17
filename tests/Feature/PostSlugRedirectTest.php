<?php

use App\Models\Category;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostSlugRedirect;
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
        'order' => 1,
    ]);
});

it('records regenerated slugs and permanently redirects shared URLs', function () {
    $category = Category::factory()->create(['slug' => 'features']);
    $post = Post::factory()->published()->create([
        'title' => 'A Shared Island Story',
        'slug' => 'untitled-article-42',
    ]);
    $post->categories()->attach($category);

    $this->artisan('posts:regenerate-slugs')
        ->expectsOutputToContain('untitled-article-42 → a-shared-island-story')
        ->assertSuccessful();

    $post->refresh();

    expect($post->slug)->toBe('a-shared-island-story')
        ->and(PostSlugRedirect::query()->where([
            'post_id' => $post->id,
            'old_slug' => 'untitled-article-42',
        ])->exists())->toBeTrue();

    $this->get(route('post.show', [
        'category' => $category->slug,
        'post' => 'untitled-article-42',
    ]))->assertStatus(301)
        ->assertRedirect(route('post.show', [
            'category' => $category->slug,
            'post' => $post->slug,
        ]));
});

it('does not write slugs or redirect history during a dry run', function () {
    $post = Post::factory()->draft()->create([
        'title' => 'Dry Run Story',
        'slug' => 'untitled-article-11',
    ]);

    $this->artisan('posts:regenerate-slugs', ['--dry-run' => true])
        ->assertSuccessful();

    expect($post->fresh()->slug)->toBe('untitled-article-11')
        ->and(PostSlugRedirect::query()->exists())->toBeFalse();
});

it('skips posts whose titles are still placeholders', function () {
    $post = Post::factory()->draft()->create([
        'title' => 'Untitled Article',
        'slug' => 'untitled-article-25',
    ]);

    $this->artisan('posts:regenerate-slugs')
        ->expectsOutputToContain("Skipping post #{$post->id} — title is empty or still a placeholder")
        ->assertSuccessful();

    expect($post->fresh()->slug)->toBe('untitled-article-25')
        ->and(PostSlugRedirect::query()->exists())->toBeFalse();
});

it('does not create redirect history for ordinary CMS-style slug edits', function () {
    $post = Post::factory()->draft()->create(['slug' => 'original-slug']);

    $post->update(['slug' => 'manually-edited-slug']);

    expect(PostSlugRedirect::query()->exists())->toBeFalse();
});

it('does not silently replace a placeholder slug when the title is edited', function () {
    $post = Post::factory()->draft()->create([
        'title' => 'Untitled',
        'slug' => 'untitled-article-18',
    ]);

    $post->update(['title' => 'A Finished Headline']);

    expect($post->fresh()->slug)->toBe('untitled-article-18')
        ->and(PostSlugRedirect::query()->exists())->toBeFalse();
});

it('blocks publishing while a placeholder slug remains', function () {
    $post = Post::factory()->draft()->create([
        'title' => 'Ready Story',
        'slug' => 'untitled-article-7',
    ]);

    expect(fn () => $post->publish())
        ->toThrow(RuntimeException::class, 'Replace the placeholder slug before publishing this post.');

    expect($post->fresh()->status)->toBe(Post::STATUS_DRAFT);
});
