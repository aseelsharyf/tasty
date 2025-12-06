<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ContentVersion;
use App\Models\EditorialComment;
use App\Models\Language;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Models\WorkflowTransition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CmsWorkflowDataSeeder extends Seeder
{
    /**
     * Seed comprehensive CMS workflow data with realistic transitions,
     * editorial comments, and version history.
     */
    public function run(): void
    {
        // Get users by role
        $admins = User::role('Admin')->get();
        $editors = User::role('Editor')->get();
        $writers = User::role('Writer')->get();

        if ($admins->isEmpty() || $editors->isEmpty() || $writers->isEmpty()) {
            $this->command->error('Missing required roles. Run RolesAndPermissionsSeeder first.');

            return;
        }

        // Combine editors and admins for editorial roles
        $editorialTeam = $editors->merge($admins);

        // Get languages
        $languages = Language::active()->get();
        if ($languages->isEmpty()) {
            $this->command->error('No active languages found.');

            return;
        }

        // Get categories and tags
        $categories = Category::all();
        $tags = Tag::all();

        if ($categories->isEmpty() || $tags->isEmpty()) {
            $this->command->error('No categories or tags found. Run TaxonomySeeder first.');

            return;
        }

        $this->command->info('Creating CMS workflow demo data...');

        foreach ($languages as $language) {
            $this->command->info("Processing language: {$language->name}");

            // 1. Create fully published posts with complete workflow history
            $this->createPublishedPostsWithHistory($language, $writers, $editorialTeam, $categories, $tags);

            // 2. Create posts in various workflow states
            $this->createPostsInReview($language, $writers, $editorialTeam, $categories, $tags);
            $this->createPostsInCopydesk($language, $writers, $editorialTeam, $categories, $tags);
            $this->createApprovedPosts($language, $writers, $editorialTeam, $categories, $tags);
            $this->createRejectedPosts($language, $writers, $editorialTeam, $categories, $tags);
            $this->createDraftPosts($language, $writers, $categories, $tags);
        }

        $this->command->info('CMS workflow demo data created successfully!');
        $this->printStats();
    }

    protected function createPublishedPostsWithHistory(
        Language $language,
        $writers,
        $editorialTeam,
        $categories,
        $tags
    ): void {
        // Create 5 published articles with full workflow history
        for ($i = 0; $i < 5; $i++) {
            $writer = $writers->random();
            $editor = $editorialTeam->random();
            $post = $this->createPostWithWorkflow(
                $language,
                $writer,
                $categories->random(),
                $tags->random(min(rand(2, 4), $tags->count())),
                'article',
                'published'
            );

            $version = $post->draftVersion;
            if (! $version) {
                continue;
            }

            // Simulate realistic workflow progression
            $this->simulateWorkflowProgression($version, $writer, $editorialTeam, [
                'draft' => ['comment' => 'Initial draft submitted', 'by' => $writer],
                'review' => ['comment' => 'Submitted for editorial review', 'by' => $writer],
                'copydesk' => ['comment' => 'Content approved, sending to copy desk', 'by' => $editor],
                'approved' => ['comment' => 'All edits complete, ready to publish', 'by' => $editorialTeam->random()],
                'published' => ['comment' => null, 'by' => $editorialTeam->random()],
            ]);

            // Add editorial comments
            $this->addEditorialComments($version, $writer, $editorialTeam);

            // Publish the post
            $version->update([
                'is_active' => true,
                'workflow_status' => 'published',
            ]);

            $post->update([
                'status' => 'published',
                'workflow_status' => 'published',
                'active_version_id' => $version->id,
                'published_at' => now()->subDays(rand(1, 30)),
            ]);
        }

        $this->command->info('  - Created 5 fully published posts with workflow history');
    }

    protected function createPostsInReview(
        Language $language,
        $writers,
        $editorialTeam,
        $categories,
        $tags
    ): void {
        for ($i = 0; $i < 3; $i++) {
            $writer = $writers->random();
            $post = $this->createPostWithWorkflow(
                $language,
                $writer,
                $categories->random(),
                $tags->random(min(rand(2, 4), $tags->count())),
                'article',
                'review'
            );

            $version = $post->draftVersion;
            if (! $version) {
                continue;
            }

            // Create initial transition
            $this->createTransition($version, null, 'draft', $writer, 'Initial draft created');
            $this->createTransition($version, 'draft', 'review', $writer, 'Ready for review');

            // Add some pending editorial comments
            $this->addReviewComments($version, $editorialTeam);

            $version->update(['workflow_status' => 'review']);
            $post->update(['workflow_status' => 'review', 'status' => 'draft']);
        }

        $this->command->info('  - Created 3 posts awaiting review');
    }

    protected function createPostsInCopydesk(
        Language $language,
        $writers,
        $editorialTeam,
        $categories,
        $tags
    ): void {
        for ($i = 0; $i < 2; $i++) {
            $writer = $writers->random();
            $editor = $editorialTeam->random();
            $post = $this->createPostWithWorkflow(
                $language,
                $writer,
                $categories->random(),
                $tags->random(min(rand(2, 4), $tags->count())),
                'article',
                'copydesk'
            );

            $version = $post->draftVersion;
            if (! $version) {
                continue;
            }

            $this->createTransition($version, null, 'draft', $writer, 'Initial draft');
            $this->createTransition($version, 'draft', 'review', $writer, 'Submitted for review');
            $this->createTransition($version, 'review', 'copydesk', $editor, 'Content is great, sending to copy desk for final review');

            // Add copy desk comments
            $this->addCopydeskComments($version, $editorialTeam);

            $version->update(['workflow_status' => 'copydesk']);
            $post->update(['workflow_status' => 'copydesk', 'status' => 'draft']);
        }

        $this->command->info('  - Created 2 posts in copy desk');
    }

    protected function createApprovedPosts(
        Language $language,
        $writers,
        $editorialTeam,
        $categories,
        $tags
    ): void {
        for ($i = 0; $i < 2; $i++) {
            $writer = $writers->random();
            $editor = $editorialTeam->random();
            $post = $this->createPostWithWorkflow(
                $language,
                $writer,
                $categories->random(),
                $tags->random(min(rand(2, 4), $tags->count())),
                'article',
                'approved'
            );

            $version = $post->draftVersion;
            if (! $version) {
                continue;
            }

            $this->createTransition($version, null, 'draft', $writer, 'Draft created');
            $this->createTransition($version, 'draft', 'review', $writer, 'Submitted for review');
            $this->createTransition($version, 'review', 'copydesk', $editorialTeam->random(), 'Approved, moving to copy desk');
            $this->createTransition($version, 'copydesk', 'approved', $editor, 'All checks passed, approved for publication');

            // Add approval comment
            EditorialComment::create([
                'uuid' => (string) Str::uuid(),
                'content_version_id' => $version->id,
                'user_id' => $editor->id,
                'content' => 'Excellent work! This article is ready for publication.',
                'type' => EditorialComment::TYPE_APPROVAL,
                'is_resolved' => true,
                'resolved_by' => $editor->id,
                'resolved_at' => now(),
            ]);

            $version->update(['workflow_status' => 'approved']);
            $post->update(['workflow_status' => 'approved', 'status' => 'draft']);
        }

        $this->command->info('  - Created 2 approved posts ready to publish');
    }

    protected function createRejectedPosts(
        Language $language,
        $writers,
        $editorialTeam,
        $categories,
        $tags
    ): void {
        for ($i = 0; $i < 2; $i++) {
            $writer = $writers->random();
            $editor = $editorialTeam->random();
            $post = $this->createPostWithWorkflow(
                $language,
                $writer,
                $categories->random(),
                $tags->random(min(rand(2, 4), $tags->count())),
                'article',
                'rejected'
            );

            $version = $post->draftVersion;
            if (! $version) {
                continue;
            }

            $this->createTransition($version, null, 'draft', $writer, 'Initial submission');
            $this->createTransition($version, 'draft', 'review', $writer, 'Ready for review');
            $this->createTransition($version, 'review', 'rejected', $editor, 'Needs revisions - see comments');

            // Add revision request comments
            $revisionRequests = [
                'The introduction needs more context about the topic.',
                'Please add sources for the statistics mentioned.',
                'The conclusion feels abrupt - could you expand on the key takeaways?',
                'Some sections are too long. Consider breaking them up.',
            ];

            foreach (array_slice($revisionRequests, 0, rand(1, 3)) as $comment) {
                EditorialComment::create([
                    'uuid' => (string) Str::uuid(),
                    'content_version_id' => $version->id,
                    'user_id' => $editor->id,
                    'content' => $comment,
                    'type' => EditorialComment::TYPE_REVISION_REQUEST,
                    'is_resolved' => false,
                ]);
            }

            $version->update(['workflow_status' => 'rejected']);
            $post->update(['workflow_status' => 'rejected', 'status' => 'draft']);
        }

        $this->command->info('  - Created 2 rejected posts needing revision');
    }

    protected function createDraftPosts(
        Language $language,
        $writers,
        $categories,
        $tags
    ): void {
        for ($i = 0; $i < 3; $i++) {
            $writer = $writers->random();
            $post = $this->createPostWithWorkflow(
                $language,
                $writer,
                $categories->random(),
                $tags->random(min(rand(2, 4), $tags->count())),
                'article',
                'draft'
            );

            $version = $post->draftVersion;
            if (! $version) {
                continue;
            }

            $this->createTransition($version, null, 'draft', $writer, 'Started writing');

            $version->update(['workflow_status' => 'draft']);
            $post->update(['workflow_status' => 'draft', 'status' => 'draft']);
        }

        $this->command->info('  - Created 3 draft posts');
    }

    protected function createPostWithWorkflow(
        Language $language,
        User $author,
        Category $category,
        $tags,
        string $postType,
        string $workflowStatus
    ): Post {
        $title = fake()->sentence(rand(5, 10));
        $slug = Str::slug($title).'-'.Str::random(5);

        $post = Post::create([
            'uuid' => (string) Str::uuid(),
            'author_id' => $author->id,
            'language_code' => $language->code,
            'title' => $title,
            'slug' => $slug,
            'subtitle' => fake()->boolean(70) ? fake()->sentence() : null,
            'excerpt' => fake()->paragraph(),
            'content' => $this->generateEditorContent(),
            'status' => $workflowStatus === 'published' ? 'published' : 'draft',
            'workflow_status' => $workflowStatus,
            'post_type' => $postType,
            'allow_comments' => true,
            'meta_title' => $title,
            'meta_description' => fake()->paragraph(),
        ]);

        // Attach category via pivot table
        $post->categories()->attach($category->id);

        // Attach tags
        $post->tags()->attach($tags->pluck('id'));

        // Create initial version
        $version = ContentVersion::create([
            'uuid' => (string) Str::uuid(),
            'versionable_type' => Post::class,
            'versionable_id' => $post->id,
            'version_number' => 1,
            'content_snapshot' => [
                'title' => $post->title,
                'subtitle' => $post->subtitle,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'category_ids' => [$category->id],
                'tag_ids' => $tags->pluck('id')->toArray(),
            ],
            'workflow_status' => 'draft',
            'is_active' => false,
            'created_by' => $author->id,
            'version_note' => 'Initial version',
        ]);

        $post->update(['draft_version_id' => $version->id]);

        return $post;
    }

    protected function createTransition(
        ContentVersion $version,
        ?string $from,
        string $to,
        User $user,
        ?string $comment = null
    ): void {
        WorkflowTransition::create([
            'uuid' => (string) Str::uuid(),
            'content_version_id' => $version->id,
            'from_status' => $from,
            'to_status' => $to,
            'performed_by' => $user->id,
            'comment' => $comment,
            'created_at' => now()->subHours(rand(1, 72)),
        ]);
    }

    protected function simulateWorkflowProgression(
        ContentVersion $version,
        User $writer,
        $editorialTeam,
        array $stages
    ): void {
        $previousStatus = null;
        $hoursAgo = rand(100, 200);

        foreach ($stages as $status => $config) {
            if ($status === 'draft' && $previousStatus === null) {
                // Initial creation
                WorkflowTransition::create([
                    'uuid' => (string) Str::uuid(),
                    'content_version_id' => $version->id,
                    'from_status' => null,
                    'to_status' => 'draft',
                    'performed_by' => $config['by']->id,
                    'comment' => $config['comment'],
                    'created_at' => now()->subHours($hoursAgo),
                ]);
            } else {
                WorkflowTransition::create([
                    'uuid' => (string) Str::uuid(),
                    'content_version_id' => $version->id,
                    'from_status' => $previousStatus,
                    'to_status' => $status,
                    'performed_by' => $config['by']->id,
                    'comment' => $config['comment'],
                    'created_at' => now()->subHours($hoursAgo),
                ]);
            }

            $previousStatus = $status;
            $hoursAgo -= rand(12, 36);
            if ($hoursAgo < 0) {
                $hoursAgo = 1;
            }
        }
    }

    protected function addEditorialComments(
        ContentVersion $version,
        User $writer,
        $editorialTeam
    ): void {
        $comments = [
            ['content' => 'Great introduction! Really hooks the reader.', 'type' => 'general', 'resolved' => true],
            ['content' => 'Can you add a bit more context here?', 'type' => 'revision_request', 'resolved' => true],
            ['content' => 'I expanded on that section as requested.', 'type' => 'general', 'resolved' => true, 'by_writer' => true],
            ['content' => 'Perfect, thank you!', 'type' => 'general', 'resolved' => true],
            ['content' => 'Minor typo fixed in paragraph 3.', 'type' => 'general', 'resolved' => true],
        ];

        $hoursAgo = rand(50, 80);

        foreach ($comments as $comment) {
            $user = ($comment['by_writer'] ?? false) ? $writer : $editorialTeam->random();

            EditorialComment::create([
                'uuid' => (string) Str::uuid(),
                'content_version_id' => $version->id,
                'user_id' => $user->id,
                'content' => $comment['content'],
                'type' => $comment['type'],
                'is_resolved' => $comment['resolved'],
                'resolved_by' => $comment['resolved'] ? $editorialTeam->random()->id : null,
                'resolved_at' => $comment['resolved'] ? now()->subHours($hoursAgo - rand(5, 20)) : null,
                'created_at' => now()->subHours($hoursAgo),
            ]);

            $hoursAgo -= rand(5, 15);
        }
    }

    protected function addReviewComments(ContentVersion $version, $editorialTeam): void
    {
        $comments = [
            'I\'ll review this shortly.',
            'Looking good so far, will provide detailed feedback soon.',
        ];

        foreach ($comments as $comment) {
            EditorialComment::create([
                'uuid' => (string) Str::uuid(),
                'content_version_id' => $version->id,
                'user_id' => $editorialTeam->random()->id,
                'content' => $comment,
                'type' => EditorialComment::TYPE_GENERAL,
                'is_resolved' => false,
                'created_at' => now()->subHours(rand(1, 24)),
            ]);
        }
    }

    protected function addCopydeskComments(ContentVersion $version, $editorialTeam): void
    {
        $comments = [
            'Checking for grammar and style consistency.',
            'Minor edits made to paragraph flow.',
        ];

        foreach ($comments as $comment) {
            EditorialComment::create([
                'uuid' => (string) Str::uuid(),
                'content_version_id' => $version->id,
                'user_id' => $editorialTeam->random()->id,
                'content' => $comment,
                'type' => EditorialComment::TYPE_GENERAL,
                'is_resolved' => true,
                'resolved_by' => $editorialTeam->random()->id,
                'resolved_at' => now()->subHours(rand(1, 12)),
                'created_at' => now()->subHours(rand(12, 36)),
            ]);
        }
    }

    protected function generateEditorContent(): array
    {
        $paragraphs = fake()->paragraphs(rand(4, 8));
        $blocks = [];

        foreach ($paragraphs as $index => $paragraph) {
            // Add a header for some blocks
            if ($index > 0 && $index % 2 === 0) {
                $blocks[] = [
                    'id' => Str::random(10),
                    'type' => 'header',
                    'data' => [
                        'text' => fake()->sentence(rand(3, 6)),
                        'level' => rand(2, 3),
                    ],
                ];
            }

            $blocks[] = [
                'id' => Str::random(10),
                'type' => 'paragraph',
                'data' => [
                    'text' => $paragraph,
                ],
            ];
        }

        return [
            'time' => now()->timestamp * 1000,
            'blocks' => $blocks,
            'version' => '2.29.1',
        ];
    }

    protected function printStats(): void
    {
        $this->command->newLine();
        $this->command->info('Summary:');
        $this->command->info('  - Total Posts: '.Post::count());
        $this->command->info('  - Content Versions: '.ContentVersion::count());
        $this->command->info('  - Workflow Transitions: '.WorkflowTransition::count());
        $this->command->info('  - Editorial Comments: '.EditorialComment::count());
        $this->command->newLine();
        $this->command->info('Workflow status breakdown:');
        $this->command->info('  - Draft: '.Post::where('workflow_status', 'draft')->count());
        $this->command->info('  - In Review: '.Post::where('workflow_status', 'review')->count());
        $this->command->info('  - Copy Desk: '.Post::where('workflow_status', 'copydesk')->count());
        $this->command->info('  - Approved: '.Post::where('workflow_status', 'approved')->count());
        $this->command->info('  - Rejected: '.Post::where('workflow_status', 'rejected')->count());
        $this->command->info('  - Published: '.Post::where('workflow_status', 'published')->count());
    }
}
