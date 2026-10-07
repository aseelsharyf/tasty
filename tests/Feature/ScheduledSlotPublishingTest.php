<?php

use App\Models\Category;
use App\Models\ContentVersion;
use App\Models\Language;
use App\Models\Post;
use App\Models\ScheduledLayoutAssignment;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Services\WorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Language::firstOrCreate(['code' => 'en'], [
        'name' => 'English',
        'native_name' => 'English',
        'direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
    ]);

    $permission = Permission::firstOrCreate(['name' => 'posts.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Editor', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);
});

it('keeps the current slot live until the scheduled post is published', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $currentPost = Post::factory()->published()->create();
    $scheduledPost = Post::factory()->draft()->create([
        'author_id' => $editor->id,
        'title' => 'Tomorrow Story',
        'slug' => 'tomorrow-story',
        'workflow_status' => ContentVersion::STATUS_COPYDESK,
    ]);
    $category = Category::factory()->create();
    $tag = Tag::factory()->create();
    $scheduledPost->categories()->attach($category);
    $scheduledPost->tags()->attach($tag);

    $version = ContentVersion::factory()
        ->forPost($scheduledPost)
        ->create([
            'created_by' => $editor->id,
            'workflow_status' => ContentVersion::STATUS_COPYDESK,
            'content_snapshot' => [
                'title' => 'Tomorrow Story',
                'content' => ['blocks' => []],
                'category_ids' => [$category->id],
                'tag_ids' => [$tag->id],
            ],
        ]);
    $scheduledPost->update(['draft_version_id' => $version->id]);

    Setting::set('layouts.homepage', [
        'version' => 1,
        'sections' => [[
            'id' => 'hero-section',
            'type' => 'hero',
            'slots' => [[
                'index' => 0,
                'mode' => 'manual',
                'postId' => $currentPost->id,
                'content' => [],
            ]],
        ]],
    ], 'layouts');

    $scheduledAt = now()->addHour()->startOfMinute();

    $this->actingAs($editor)
        ->postJson("/cms/posts/{$scheduledPost->uuid}/publish-with-slot", [
            'versionUuid' => $version->uuid,
            'sectionId' => 'hero-section',
            'slotIndex' => 0,
            'layoutType' => 'homepage',
            'pageLayoutId' => null,
            'mode' => 'scheduled',
            'scheduledAt' => $scheduledAt->toDateTimeString(),
        ])
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'mode' => 'scheduled',
        ]);

    expect($scheduledPost->fresh()->status)->toBe(Post::STATUS_SCHEDULED)
        ->and($version->fresh()->workflow_status)->toBe(ContentVersion::STATUS_SCHEDULED)
        ->and($scheduledPost->fresh()->scheduledLayoutAssignment)
        ->status->toBe(ScheduledLayoutAssignment::STATUS_PENDING)
        ->and(Setting::get('layouts.homepage')['sections'][0]['slots'][0]['postId'])
        ->toBe($currentPost->id);

    $this->travelTo($scheduledAt->copy()->addMinute());
    $this->artisan('posts:process-scheduled-posts')->assertSuccessful();

    expect($scheduledPost->fresh()->status)->toBe(Post::STATUS_PUBLISHED)
        ->and($scheduledPost->fresh()->scheduled_at)->toBeNull()
        ->and($version->fresh()->workflow_status)->toBe(ContentVersion::STATUS_PUBLISHED)
        ->and($scheduledPost->fresh()->scheduledLayoutAssignment)
        ->status->toBe(ScheduledLayoutAssignment::STATUS_COMPLETED)
        ->and($scheduledPost->fresh()->scheduledLayoutAssignment->processed_at)->not->toBeNull()
        ->and(Setting::get('layouts.homepage')['sections'][0]['slots'][0]['postId'])
        ->toBe($scheduledPost->id);
});

it('requires a future date when scheduling a slot assignment', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');
    $post = Post::factory()->draft()->create(['author_id' => $editor->id]);
    $version = ContentVersion::factory()->forPost($post)->inReview()->create(['created_by' => $editor->id]);

    $this->actingAs($editor)
        ->postJson("/cms/posts/{$post->uuid}/publish-with-slot", [
            'versionUuid' => $version->uuid,
            'sectionId' => 'hero-section',
            'slotIndex' => 0,
            'layoutType' => 'homepage',
            'mode' => 'scheduled',
            'scheduledAt' => now()->subMinute()->toDateTimeString(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduledAt');
});

it('cancels the reserved slot when a scheduled post returns to copy desk', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');
    $post = Post::factory()->draft()->create([
        'author_id' => $editor->id,
        'status' => Post::STATUS_SCHEDULED,
        'workflow_status' => ContentVersion::STATUS_SCHEDULED,
        'scheduled_at' => now()->addHour(),
    ]);
    $version = ContentVersion::factory()->forPost($post)->create([
        'created_by' => $editor->id,
        'workflow_status' => ContentVersion::STATUS_SCHEDULED,
    ]);

    ScheduledLayoutAssignment::factory()->create([
        'post_id' => $post->id,
        'content_version_id' => $version->id,
        'scheduled_at' => $post->scheduled_at,
    ]);

    app(WorkflowService::class)->transition(
        $version,
        ContentVersion::STATUS_COPYDESK,
        null,
        $editor,
    );

    expect($post->fresh()->status)->toBe(Post::STATUS_DRAFT)
        ->and($post->fresh()->scheduled_at)->toBeNull()
        ->and($post->fresh()->scheduledLayoutAssignment->status)
        ->toBe(ScheduledLayoutAssignment::STATUS_CANCELLED);
});
