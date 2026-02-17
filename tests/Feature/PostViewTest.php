<?php

use App\Jobs\RecordViewJob;
use App\Models\Category;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Language::firstOrCreate(['code' => 'en'], [
        'name' => 'English',
        'native_name' => 'English',
        'direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
        'order' => 1,
    ]);
});

test('visiting a published post dispatches RecordViewJob', function () {
    Queue::fake();

    $category = Category::factory()->create();
    $post = Post::factory()->published()->create();
    $post->categories()->attach($category);

    $this->get(route('post.show', [
        'category' => $category->slug,
        'post' => $post->slug,
    ]));

    Queue::assertPushed(RecordViewJob::class, function ($job) use ($post) {
        return $job->data['type'] === 'post' && $job->data['model_id'] === $post->id;
    });
});

test('bot requests do not dispatch RecordViewJob', function () {
    Queue::fake();

    $category = Category::factory()->create();
    $post = Post::factory()->published()->create();
    $post->categories()->attach($category);

    $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])
        ->get(route('post.show', [
            'category' => $category->slug,
            'post' => $post->slug,
        ]));

    Queue::assertNotPushed(RecordViewJob::class);
});

test('PostView session deduplication prevents duplicate within 30 minutes', function () {
    $post = Post::factory()->published()->create();
    $sessionId = 'test-session-123';

    $result1 = PostView::record([
        'post_id' => $post->id,
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'referrer' => null,
        'session_id' => $sessionId,
    ]);

    $result2 = PostView::record([
        'post_id' => $post->id,
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'referrer' => null,
        'session_id' => $sessionId,
    ]);

    expect($result1)->toBeInstanceOf(PostView::class);
    expect($result2)->toBeNull();
    expect(PostView::count())->toBe(1);
});

test('PostView allows duplicate after 30 minutes', function () {
    $post = Post::factory()->published()->create();
    $sessionId = 'test-session-456';

    PostView::create([
        'post_id' => $post->id,
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'referrer' => null,
        'session_id' => $sessionId,
        'viewed_at' => now()->subMinutes(31),
    ]);

    $result = PostView::record([
        'post_id' => $post->id,
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'referrer' => null,
        'session_id' => $sessionId,
    ]);

    expect($result)->toBeInstanceOf(PostView::class);
    expect(PostView::count())->toBe(2);
});

test('PostView allows different sessions on same post', function () {
    $post = Post::factory()->published()->create();

    PostView::record([
        'post_id' => $post->id,
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'referrer' => null,
        'session_id' => 'session-a',
    ]);

    PostView::record([
        'post_id' => $post->id,
        'user_id' => null,
        'ip_address' => '127.0.0.2',
        'user_agent' => 'Mozilla/5.0',
        'referrer' => null,
        'session_id' => 'session-b',
    ]);

    expect(PostView::count())->toBe(2);
});

test('RecordViewJob creates PostView record', function () {
    $post = Post::factory()->published()->create();

    $job = new RecordViewJob([
        'type' => 'post',
        'model_id' => $post->id,
        'user_id' => null,
        'ip_address' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0',
        'referrer' => 'https://google.com',
        'session_id' => 'job-session-123',
    ]);

    $job->handle();

    expect(PostView::count())->toBe(1);
    $view = PostView::first();
    expect($view->post_id)->toBe($post->id);
    expect($view->ip_address)->toBe('192.168.1.1');
    expect($view->session_id)->toBe('job-session-123');
});
