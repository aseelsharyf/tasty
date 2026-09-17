<?php

use App\Models\Language;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

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

it('clears old and current article caches when post content and slug change', function () {
    $post = Post::factory()->published()->create([
        'slug' => 'original-article-slug',
    ]);

    Cache::put('public:post:original-article-slug', 'stale original article');
    Cache::put('public:post:updated-article-slug', 'stale updated article');

    $post->update([
        'slug' => 'updated-article-slug',
        'content' => [
            'blocks' => [
                [
                    'type' => 'media',
                    'data' => [
                        'singleImageDisplay' => 'portrait',
                    ],
                ],
            ],
        ],
    ]);

    expect(Cache::has('public:post:original-article-slug'))->toBeFalse()
        ->and(Cache::has('public:post:updated-article-slug'))->toBeFalse();
});
