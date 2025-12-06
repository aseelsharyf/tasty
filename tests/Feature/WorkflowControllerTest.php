<?php

use App\Models\ContentVersion;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ensure English language exists
    Language::firstOrCreate(['code' => 'en'], [
        'name' => 'English',
        'native_name' => 'English',
        'direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
    ]);

    // Create permissions
    $permissions = ['workflow.publish', 'workflow.revert'];
    foreach ($permissions as $permName) {
        \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => $permName,
            'guard_name' => 'web',
        ]);
    }

    // Create roles
    foreach (['Admin', 'Editor', 'Writer'] as $roleName) {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        // Give Editor and Admin the workflow permissions
        if (in_array($roleName, ['Editor', 'Admin'])) {
            $role->givePermissionTo($permissions);
        }
    }
});

describe('POST /cms/workflow/versions/{version}/transition', function () {
    it('allows writer to submit draft for review', function () {
        $user = User::factory()->create();
        $user->assignRole('Writer');

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);
        $version = ContentVersion::factory()
            ->forPost($post)
            ->draft()
            ->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->postJson("/cms/workflow/versions/{$version->uuid}/transition", [
            'to_status' => 'review',
            'comment' => 'Ready for review',
        ]);

        $response->assertSuccessful()
            ->assertJson(['success' => true]);

        expect($version->fresh()->workflow_status)->toBe('review');
    });

    it('rejects unauthorized transitions', function () {
        $user = User::factory()->create();
        $user->assignRole('Writer');

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);
        $version = ContentVersion::factory()
            ->forPost($post)
            ->draft()
            ->create(['created_by' => $user->id]);

        // Writers cannot directly approve
        $response = $this->actingAs($user)->postJson("/cms/workflow/versions/{$version->uuid}/transition", [
            'to_status' => 'approved',
        ]);

        $response->assertStatus(422);
        expect($version->fresh()->workflow_status)->toBe('draft');
    });

    it('allows editor to transition through workflow', function () {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $post = Post::factory()->draft()->create(['author_id' => $editor->id]);
        $version = ContentVersion::factory()
            ->forPost($post)
            ->inReview()
            ->create(['created_by' => $editor->id]);

        // Editor sends to copydesk
        $response = $this->actingAs($editor)->postJson("/cms/workflow/versions/{$version->uuid}/transition", [
            'to_status' => 'copydesk',
        ]);

        $response->assertSuccessful();
        expect($version->fresh()->workflow_status)->toBe('copydesk');

        // Editor approves from copydesk
        $response = $this->actingAs($editor)->postJson("/cms/workflow/versions/{$version->uuid}/transition", [
            'to_status' => 'approved',
        ]);

        $response->assertSuccessful();
        expect($version->fresh()->workflow_status)->toBe('approved');
    });
});

describe('POST /cms/workflow/versions/{version}/revert', function () {
    it('creates a new draft from a published version', function () {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $post = Post::factory()->published()->create(['author_id' => $editor->id]);
        $publishedVersion = ContentVersion::factory()
            ->forPost($post)
            ->published()
            ->create([
                'created_by' => $editor->id,
                'version_number' => 1,
                'content_snapshot' => [
                    'title' => 'Original Title',
                    'content' => ['blocks' => []],
                ],
            ]);

        $post->update([
            'active_version_id' => $publishedVersion->id,
            'draft_version_id' => $publishedVersion->id,
        ]);

        $response = $this->actingAs($editor)->postJson("/cms/workflow/versions/{$publishedVersion->uuid}/revert");

        $response->assertSuccessful()
            ->assertJson(['success' => true]);

        $newVersion = ContentVersion::where('versionable_id', $post->id)
            ->where('version_number', 2)
            ->first();

        expect($newVersion)->not->toBeNull()
            ->and($newVersion->workflow_status)->toBe('draft')
            ->and($newVersion->content_snapshot['title'])->toBe('Original Title')
            ->and($post->fresh()->draft_version_id)->toBe($newVersion->id);
    });

    it('preserves active version when creating draft', function () {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $post = Post::factory()->published()->create(['author_id' => $editor->id]);
        $publishedVersion = ContentVersion::factory()
            ->forPost($post)
            ->published()
            ->create([
                'created_by' => $editor->id,
                'version_number' => 1,
            ]);

        $post->update(['active_version_id' => $publishedVersion->id]);

        $this->actingAs($editor)->postJson("/cms/workflow/versions/{$publishedVersion->uuid}/revert");

        // Published version should still be active
        expect($publishedVersion->fresh()->is_active)->toBeTrue()
            ->and($post->fresh()->active_version_id)->toBe($publishedVersion->id)
            ->and($post->fresh()->status)->toBe('published');
    });

    it('requires authentication', function () {
        $post = Post::factory()->published()->create();
        $version = ContentVersion::factory()
            ->forPost($post)
            ->published()
            ->create();

        $response = $this->postJson("/cms/workflow/versions/{$version->uuid}/revert");

        $response->assertUnauthorized();
    });
});

describe('POST /cms/workflow/versions/{version}/publish', function () {
    it('publishes an approved version', function () {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $post = Post::factory()->draft()->create(['author_id' => $editor->id]);
        $version = ContentVersion::factory()
            ->forPost($post)
            ->approved()
            ->create([
                'created_by' => $editor->id,
                'content_snapshot' => [
                    'title' => 'Test Title',
                    'content' => ['blocks' => []],
                ],
            ]);

        // Add required category and tag
        $category = \App\Models\Category::factory()->create();
        $tag = \App\Models\Tag::factory()->create();
        $post->categories()->attach($category);
        $post->tags()->attach($tag);
        $post->update(['draft_version_id' => $version->id]);

        $response = $this->actingAs($editor)->postJson("/cms/workflow/versions/{$version->uuid}/publish");

        $response->assertSuccessful();

        expect($version->fresh()->is_active)->toBeTrue()
            ->and($version->fresh()->workflow_status)->toBe('published')
            ->and($post->fresh()->status)->toBe('published')
            ->and($post->fresh()->active_version_id)->toBe($version->id);
    });

    it('rejects publishing non-approved version', function () {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $post = Post::factory()->draft()->create(['author_id' => $editor->id]);
        $version = ContentVersion::factory()
            ->forPost($post)
            ->draft()
            ->create(['created_by' => $editor->id]);

        $response = $this->actingAs($editor)->postJson("/cms/workflow/versions/{$version->uuid}/publish");

        $response->assertStatus(422);
        expect($version->fresh()->is_active)->toBeFalse();
    });
});

describe('POST /cms/workflow/{type}/{uuid}/unpublish', function () {
    it('unpublishes a published post', function () {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $post = Post::factory()->published()->create(['author_id' => $editor->id]);
        $version = ContentVersion::factory()
            ->forPost($post)
            ->published()
            ->create(['created_by' => $editor->id]);

        $post->update([
            'active_version_id' => $version->id,
            'workflow_status' => 'published',
        ]);

        $response = $this->actingAs($editor)->postJson("/cms/workflow/post/{$post->uuid}/unpublish");

        $response->assertSuccessful();

        $post->refresh();
        expect($post->status)->toBe('draft')
            ->and($post->published_at)->toBeNull()
            ->and($post->active_version_id)->toBeNull();
    });
});

describe('GET /cms/workflow/{type}/{uuid}/history', function () {
    it('returns version history for a post', function () {
        $user = User::factory()->create();
        $user->assignRole('Writer');

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);

        ContentVersion::factory()
            ->forPost($post)
            ->draft()
            ->withVersionNumber(1)
            ->create(['created_by' => $user->id]);

        ContentVersion::factory()
            ->forPost($post)
            ->inReview()
            ->withVersionNumber(2)
            ->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->getJson("/cms/workflow/post/{$post->uuid}/history");

        $response->assertSuccessful()
            ->assertJsonCount(2, 'versions');
    });
});

describe('GET /cms/workflow/versions/{version}/transitions', function () {
    it('returns available transitions for current user', function () {
        $user = User::factory()->create();
        $user->assignRole('Writer');

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);
        $version = ContentVersion::factory()
            ->forPost($post)
            ->draft()
            ->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->getJson("/cms/workflow/versions/{$version->uuid}/transitions");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'current_status',
                'transitions',
            ]);

        // Writer should be able to submit for review
        $transitions = collect($response->json('transitions'));
        expect($transitions->pluck('to'))->toContain('review');
    });

    it('returns different transitions for editor', function () {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $post = Post::factory()->draft()->create(['author_id' => $editor->id]);
        $version = ContentVersion::factory()
            ->forPost($post)
            ->inReview()
            ->create(['created_by' => $editor->id]);

        $response = $this->actingAs($editor)->getJson("/cms/workflow/versions/{$version->uuid}/transitions");

        $response->assertSuccessful();

        $transitions = collect($response->json('transitions'));
        expect($transitions->pluck('to'))->toContain('copydesk')
            ->and($transitions->pluck('to'))->toContain('rejected');
    });
});

describe('Editorial Comments', function () {
    it('adds a comment to a version', function () {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);
        $version = ContentVersion::factory()
            ->forPost($post)
            ->inReview()
            ->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->postJson("/cms/workflow/versions/{$version->uuid}/comments", [
            'content' => 'Please fix the introduction',
            'type' => 'revision_request',
        ]);

        $response->assertSuccessful()
            ->assertJson(['success' => true]);

        expect($version->editorialComments()->count())->toBe(1);
    });

    it('lists comments for a version', function () {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);
        $version = ContentVersion::factory()
            ->forPost($post)
            ->inReview()
            ->create(['created_by' => $user->id]);

        // Add some comments
        $version->editorialComments()->create([
            'user_id' => $user->id,
            'content' => 'Comment 1',
            'type' => 'general',
        ]);
        $version->editorialComments()->create([
            'user_id' => $user->id,
            'content' => 'Comment 2',
            'type' => 'revision_request',
        ]);

        $response = $this->actingAs($user)->getJson("/cms/workflow/versions/{$version->uuid}/comments");

        $response->assertSuccessful()
            ->assertJsonCount(2, 'comments');
    });

    it('resolves a comment', function () {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);
        $version = ContentVersion::factory()
            ->forPost($post)
            ->inReview()
            ->create(['created_by' => $user->id]);

        $comment = $version->editorialComments()->create([
            'user_id' => $user->id,
            'content' => 'Please fix this',
            'type' => 'revision_request',
        ]);

        $response = $this->actingAs($user)->postJson("/cms/workflow/comments/{$comment->uuid}/resolve");

        $response->assertSuccessful();
        expect($comment->fresh()->is_resolved)->toBeTrue();
    });
});
