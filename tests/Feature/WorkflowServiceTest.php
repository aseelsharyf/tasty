<?php

use App\Models\ContentVersion;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use App\Services\WorkflowService;
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

    // Create roles
    foreach (['Admin', 'Editor', 'Writer'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    }
});

describe('WorkflowService::createVersion', function () {
    it('creates an initial version for a new post', function () {
        $user = User::factory()->create();
        $user->assignRole('Writer');
        $this->actingAs($user);

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);
        $workflowService = app(WorkflowService::class);

        $version = $workflowService->createVersion($post, [
            'title' => 'Test Title',
            'content' => ['blocks' => []],
        ], 'Initial version');

        expect($version)->toBeInstanceOf(ContentVersion::class)
            ->and($version->version_number)->toBe(1)
            ->and($version->workflow_status)->toBe(ContentVersion::STATUS_DRAFT)
            ->and($version->is_active)->toBeFalse()
            ->and($version->created_by)->toBe($user->id)
            ->and($version->content_snapshot)->toHaveKey('title', 'Test Title')
            ->and($post->fresh()->draft_version_id)->toBe($version->id);
    });

    it('increments version number for subsequent versions', function () {
        $user = User::factory()->create();
        $user->assignRole('Writer');
        $this->actingAs($user);

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);
        $workflowService = app(WorkflowService::class);

        $version1 = $workflowService->createVersion($post, ['title' => 'V1'], 'First');
        $version2 = $workflowService->createVersion($post, ['title' => 'V2'], 'Second');

        expect($version1->version_number)->toBe(1)
            ->and($version2->version_number)->toBe(2);
    });
});

describe('WorkflowService::updateDraftVersion', function () {
    it('updates existing draft version without creating new one', function () {
        $user = User::factory()->create();
        $user->assignRole('Writer');
        $this->actingAs($user);

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);
        $workflowService = app(WorkflowService::class);

        $version = $workflowService->createVersion($post, ['title' => 'Original'], 'Initial');
        $originalId = $version->id;

        $updated = $workflowService->updateDraftVersion($post, ['title' => 'Updated']);

        expect($updated->id)->toBe($originalId)
            ->and($updated->content_snapshot['title'])->toBe('Updated')
            ->and($post->versions()->count())->toBe(1);
    });

    it('creates new version if current is not a draft', function () {
        $user = User::factory()->create();
        $user->assignRole('Editor');
        $this->actingAs($user);

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);
        $workflowService = app(WorkflowService::class);

        // Create and transition to review (no longer a draft)
        $version = $workflowService->createVersion($post, ['title' => 'Original'], 'Initial');
        $workflowService->transition($version, ContentVersion::STATUS_REVIEW);

        $newVersion = $workflowService->updateDraftVersion($post, ['title' => 'New Draft']);

        expect($newVersion->id)->not->toBe($version->id)
            ->and($newVersion->workflow_status)->toBe(ContentVersion::STATUS_DRAFT)
            ->and($post->versions()->count())->toBe(2);
    });
});

describe('WorkflowService::transition', function () {
    it('transitions from draft to review', function () {
        $user = User::factory()->create();
        $user->assignRole('Writer');
        $this->actingAs($user);

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);
        $workflowService = app(WorkflowService::class);

        $version = $workflowService->createVersion($post, ['title' => 'Test'], 'Initial');
        $transition = $workflowService->transition($version, ContentVersion::STATUS_REVIEW, 'Ready for review');

        expect($transition->from_status)->toBe(ContentVersion::STATUS_DRAFT)
            ->and($transition->to_status)->toBe(ContentVersion::STATUS_REVIEW)
            ->and($transition->comment)->toBe('Ready for review')
            ->and($version->fresh()->workflow_status)->toBe(ContentVersion::STATUS_REVIEW)
            ->and($post->fresh()->workflow_status)->toBe(ContentVersion::STATUS_REVIEW);
    });

    it('throws exception for unauthorized transition', function () {
        $user = User::factory()->create();
        $user->assignRole('Writer');
        $this->actingAs($user);

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);
        $workflowService = app(WorkflowService::class);

        $version = $workflowService->createVersion($post, ['title' => 'Test'], 'Initial');

        // Writers cannot directly approve content
        expect(fn () => $workflowService->transition($version, ContentVersion::STATUS_APPROVED))
            ->toThrow(Exception::class);
    });

    it('editor can approve after copydesk review', function () {
        $writer = User::factory()->create();
        $writer->assignRole('Writer');

        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $post = Post::factory()->draft()->create(['author_id' => $writer->id]);
        $workflowService = app(WorkflowService::class);

        // Writer creates and submits
        $this->actingAs($writer);
        $version = $workflowService->createVersion($post, ['title' => 'Test'], 'Initial');
        $workflowService->transition($version, ContentVersion::STATUS_REVIEW);

        // Editor reviews and sends to copydesk
        $this->actingAs($editor);
        $workflowService->transition($version, ContentVersion::STATUS_COPYDESK);

        // Editor approves from copydesk
        $workflowService->transition($version, ContentVersion::STATUS_APPROVED);

        expect($version->fresh()->workflow_status)->toBe(ContentVersion::STATUS_APPROVED);
    });
});

describe('WorkflowService::publishVersion', function () {
    it('publishes an approved version', function () {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');
        $this->actingAs($editor);

        $post = Post::factory()->draft()->create(['author_id' => $editor->id]);
        $workflowService = app(WorkflowService::class);

        // Create version with required fields for publishing
        $version = ContentVersion::factory()
            ->forPost($post)
            ->approved()
            ->create([
                'created_by' => $editor->id,
                'content_snapshot' => [
                    'title' => 'Test Post',
                    'content' => ['blocks' => []],
                ],
            ]);

        // Add category and tag for publish validation
        $category = \App\Models\Category::factory()->create();
        $tag = \App\Models\Tag::factory()->create();
        $post->categories()->attach($category);
        $post->tags()->attach($tag);
        $post->update(['draft_version_id' => $version->id]);

        $workflowService->publishVersion($version);

        expect($version->fresh()->is_active)->toBeTrue()
            ->and($version->fresh()->workflow_status)->toBe(ContentVersion::STATUS_PUBLISHED)
            ->and($post->fresh()->status)->toBe('published')
            ->and($post->fresh()->active_version_id)->toBe($version->id);
    });

    it('throws exception when publishing non-approved version', function () {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');
        $this->actingAs($editor);

        $post = Post::factory()->draft()->create(['author_id' => $editor->id]);
        $version = ContentVersion::factory()
            ->forPost($post)
            ->draft()
            ->create(['created_by' => $editor->id]);

        $workflowService = app(WorkflowService::class);

        expect(fn () => $workflowService->publishVersion($version))
            ->toThrow(Exception::class, 'Only approved versions can be published');
    });
});

describe('WorkflowService::revertToVersion', function () {
    it('creates a new draft from a published version', function () {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');
        $this->actingAs($editor);

        $post = Post::factory()->published()->create(['author_id' => $editor->id]);

        // Create a published version
        $publishedVersion = ContentVersion::factory()
            ->forPost($post)
            ->published()
            ->create([
                'created_by' => $editor->id,
                'version_number' => 1,
                'content_snapshot' => [
                    'title' => 'Published Title',
                    'content' => ['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'Content']]]],
                ],
            ]);

        $post->update([
            'active_version_id' => $publishedVersion->id,
            'draft_version_id' => $publishedVersion->id,
        ]);

        $workflowService = app(WorkflowService::class);
        $newDraft = $workflowService->revertToVersion($publishedVersion);

        expect($newDraft)->toBeInstanceOf(ContentVersion::class)
            ->and($newDraft->id)->not->toBe($publishedVersion->id)
            ->and($newDraft->version_number)->toBe(2)
            ->and($newDraft->workflow_status)->toBe(ContentVersion::STATUS_DRAFT)
            ->and($newDraft->is_active)->toBeFalse()
            ->and($newDraft->content_snapshot['title'])->toBe('Published Title')
            ->and($post->fresh()->draft_version_id)->toBe($newDraft->id);
    });

    it('preserves the active published version when creating draft', function () {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');
        $this->actingAs($editor);

        $post = Post::factory()->published()->create(['author_id' => $editor->id]);

        $publishedVersion = ContentVersion::factory()
            ->forPost($post)
            ->published()
            ->create([
                'created_by' => $editor->id,
                'version_number' => 1,
            ]);

        $post->update(['active_version_id' => $publishedVersion->id]);

        $workflowService = app(WorkflowService::class);
        $newDraft = $workflowService->revertToVersion($publishedVersion);

        // The published version should still be active
        expect($publishedVersion->fresh()->is_active)->toBeTrue()
            ->and($post->fresh()->active_version_id)->toBe($publishedVersion->id)
            ->and($post->fresh()->status)->toBe('published');
    });
});

describe('WorkflowService::unpublish (via transition)', function () {
    it('unpublishes content and sets version as draft for editing', function () {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');
        $this->actingAs($editor);

        $post = Post::factory()->published()->create(['author_id' => $editor->id]);

        $publishedVersion = ContentVersion::factory()
            ->forPost($post)
            ->published()
            ->create([
                'created_by' => $editor->id,
                'version_number' => 1,
            ]);

        $post->update([
            'active_version_id' => $publishedVersion->id,
            'draft_version_id' => $publishedVersion->id,
            'workflow_status' => ContentVersion::STATUS_PUBLISHED,
        ]);

        $workflowService = app(WorkflowService::class);
        $workflowService->transition($publishedVersion, ContentVersion::STATUS_DRAFT);

        $post->refresh();
        $publishedVersion->refresh();

        expect($post->status)->toBe('draft')
            ->and($post->published_at)->toBeNull()
            ->and($post->active_version_id)->toBeNull()
            ->and($post->draft_version_id)->toBe($publishedVersion->id)
            ->and($publishedVersion->is_active)->toBeFalse()
            ->and($publishedVersion->workflow_status)->toBe(ContentVersion::STATUS_DRAFT);
    });
});

describe('Version Integrity', function () {
    it('validates that version belongs to the correct post', function () {
        $user = User::factory()->create();
        $user->assignRole('Writer');
        $this->actingAs($user);

        $post1 = Post::factory()->draft()->create(['author_id' => $user->id]);
        $post2 = Post::factory()->draft()->create(['author_id' => $user->id]);

        $version = ContentVersion::factory()
            ->forPost($post1)
            ->draft()
            ->create(['created_by' => $user->id]);

        // Version should belong to post1, not post2
        expect($version->versionable_id)->toBe($post1->id)
            ->and($version->versionable_id)->not->toBe($post2->id);
    });

    it('handles posts with no versions gracefully', function () {
        $user = User::factory()->create();
        $user->assignRole('Writer');
        $this->actingAs($user);

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);

        expect($post->versions()->count())->toBe(0)
            ->and($post->draftVersion)->toBeNull()
            ->and($post->activeVersion)->toBeNull();
    });

    it('correctly identifies draft vs active version', function () {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');
        $this->actingAs($editor);

        $post = Post::factory()->published()->create(['author_id' => $editor->id]);

        $publishedVersion = ContentVersion::factory()
            ->forPost($post)
            ->published()
            ->create([
                'created_by' => $editor->id,
                'version_number' => 1,
            ]);

        $draftVersion = ContentVersion::factory()
            ->forPost($post)
            ->draft()
            ->withVersionNumber(2)
            ->create(['created_by' => $editor->id]);

        $post->update([
            'active_version_id' => $publishedVersion->id,
            'draft_version_id' => $draftVersion->id,
        ]);

        $post->refresh();

        expect($post->activeVersion->id)->toBe($publishedVersion->id)
            ->and($post->activeVersion->is_active)->toBeTrue()
            ->and($post->draftVersion->id)->toBe($draftVersion->id)
            ->and($post->draftVersion->workflow_status)->toBe(ContentVersion::STATUS_DRAFT);
    });
});

describe('Workflow Transitions Available', function () {
    it('returns correct transitions for draft status as writer', function () {
        $user = User::factory()->create();
        $user->assignRole('Writer');
        $this->actingAs($user);

        $post = Post::factory()->draft()->create(['author_id' => $user->id]);
        $version = ContentVersion::factory()
            ->forPost($post)
            ->draft()
            ->create(['created_by' => $user->id]);

        $workflowService = app(WorkflowService::class);
        $transitions = $workflowService->getAvailableTransitions($user, $version);

        $toStatuses = collect($transitions)->pluck('to')->toArray();

        expect($toStatuses)->toContain(ContentVersion::STATUS_REVIEW);
    });

    it('returns correct transitions for review status as editor', function () {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');
        $this->actingAs($editor);

        $post = Post::factory()->draft()->create(['author_id' => $editor->id]);
        $version = ContentVersion::factory()
            ->forPost($post)
            ->inReview()
            ->create(['created_by' => $editor->id]);

        $workflowService = app(WorkflowService::class);
        $transitions = $workflowService->getAvailableTransitions($editor, $version);

        $toStatuses = collect($transitions)->pluck('to')->toArray();

        expect($toStatuses)->toContain(ContentVersion::STATUS_COPYDESK)
            ->and($toStatuses)->toContain(ContentVersion::STATUS_REJECTED);
    });
});
