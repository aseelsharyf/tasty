<?php

namespace App\Services;

use App\Models\ContentVersion;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkflowTransition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    public function __construct(
        protected ?NotificationService $notificationService = null
    ) {
        $this->notificationService ??= app(NotificationService::class);
    }

    /**
     * Get the workflow configuration for a content item.
     *
     * @return array<string, mixed>
     */
    public function getWorkflowFor(Model $content): array
    {
        // Check for content-type specific workflow first
        if (method_exists($content, 'getPostType') && $content->getPostType()) {
            $postType = $content->getPostType();
            $typeConfig = Setting::get("workflow.post_type.{$postType}");
            if ($typeConfig) {
                return $typeConfig;
            }
        }

        // Fall back to default workflow
        return Setting::get('workflow.default', $this->getDefaultWorkflowConfig());
    }

    /**
     * Get the default workflow configuration.
     *
     * Simplified workflow: Draft → Copy Desk → Published, with Parked status.
     * - Writer: Draft → Copy Desk (submit for review)
     * - Editor: Copy Desk → Reject (back to draft), Park (approved for later), or Publish
     * - Editor: Can publish directly from draft, can always edit even published posts
     *
     * @return array<string, mixed>
     */
    public function getDefaultWorkflowConfig(): array
    {
        return [
            'name' => 'Default Editorial Workflow',
            'states' => [
                ['key' => 'draft', 'label' => 'Draft', 'color' => 'gray', 'icon' => 'i-lucide-file-edit'],
                ['key' => 'copydesk', 'label' => 'Copy Desk', 'color' => 'blue', 'icon' => 'i-lucide-spell-check'],
                ['key' => 'parked', 'label' => 'Parked', 'color' => 'emerald', 'icon' => 'i-lucide-archive'],
                ['key' => 'scheduled', 'label' => 'Scheduled', 'color' => 'yellow', 'icon' => 'i-lucide-calendar-clock'],
                ['key' => 'published', 'label' => 'Published', 'color' => 'green', 'icon' => 'i-lucide-globe'],
            ],
            'transitions' => [
                // Writer submits draft for review
                ['from' => 'draft', 'to' => 'copydesk', 'roles' => ['Writer', 'Editor', 'Admin'], 'label' => 'Send to Copy Desk'],
                // ['from' => 'copydesk', 'to' => 'draft', 'roles' => ['Writer'], 'label' => 'Withdraw'],
                // Editor rejects back to draft (sends notification to writer)
                ['from' => 'copydesk', 'to' => 'draft', 'roles' => ['Editor', 'Admin'], 'label' => 'Reject'],
                // Editor parks (approved, banked for later)
                ['from' => 'copydesk', 'to' => 'parked', 'roles' => ['Editor', 'Admin'], 'label' => 'Park'],
                // Editor publishes from copydesk directly
                ['from' => 'copydesk', 'to' => 'published', 'roles' => ['Editor', 'Admin'], 'label' => 'Publish'],
                // Editor schedules from copydesk
                ['from' => 'copydesk', 'to' => 'scheduled', 'roles' => ['Editor', 'Admin'], 'label' => 'Schedule'],
                // Editor publishes a parked post
                ['from' => 'parked', 'to' => 'published', 'roles' => ['Editor', 'Admin'], 'label' => 'Publish'],
                // Editor sends parked post back to draft
                ['from' => 'parked', 'to' => 'draft', 'roles' => ['Editor', 'Admin'], 'label' => 'Send Back'],
                // ['from' => 'draft', 'to' => 'published', 'roles' => ['Editor', 'Admin'], 'label' => 'Publish'],
                // Unpublish goes to copydesk (not draft)
                ['from' => 'published', 'to' => 'copydesk', 'roles' => ['Editor', 'Admin'], 'label' => 'Unpublish'],
                // Scheduled post actions
                ['from' => 'scheduled', 'to' => 'copydesk', 'roles' => ['Editor', 'Admin'], 'label' => 'Unschedule'],
                ['from' => 'scheduled', 'to' => 'published', 'roles' => ['Editor', 'Admin'], 'label' => 'Publish Now'],
                // Legacy: handle old 'review' status
                ['from' => 'review', 'to' => 'copydesk', 'roles' => ['Editor', 'Admin'], 'label' => 'Send to Copy Desk'],
            ],
            'publish_roles' => ['Editor', 'Admin'],
            'edit_published_roles' => ['Editor', 'Admin'],
        ];
    }

    /**
     * Get the available transitions for a user on a version.
     *
     * @return array<int, array{from: string, to: string, roles: array, label: string}>
     */
    public function getAvailableTransitions(User $user, ContentVersion $version): array
    {
        $content = $version->versionable;
        $workflow = $this->getWorkflowFor($content);
        $currentStatus = $version->workflow_status;
        $userRoles = $user->getRoleNames()->toArray();

        $available = [];

        foreach ($workflow['transitions'] ?? [] as $transition) {
            if ($transition['from'] !== $currentStatus) {
                continue;
            }

            // Check if user has any of the required roles
            $allowedRoles = $transition['roles'] ?? [];
            $hasRole = ! empty(array_intersect($userRoles, $allowedRoles));

            if ($hasRole) {
                $available[] = $transition;
            }
        }

        return $available;
    }

    /**
     * Check if a user can perform a specific transition.
     */
    public function canTransition(User $user, ContentVersion $version, string $toStatus): bool
    {
        $availableTransitions = $this->getAvailableTransitions($user, $version);

        foreach ($availableTransitions as $transition) {
            if ($transition['to'] === $toStatus) {
                return true;
            }
        }

        return false;
    }

    /**
     * Perform a workflow transition.
     */
    public function transition(
        ContentVersion $version,
        string $toStatus,
        ?string $comment = null,
        ?User $user = null
    ): WorkflowTransition {
        $user = $user ?? auth()->user();

        if (! $this->canTransition($user, $version, $toStatus)) {
            throw new \Exception("User is not allowed to transition to '{$toStatus}'");
        }

        $fromStatus = $version->workflow_status;
        $content = $version->versionable;

        // Writer withdraw validation: can't withdraw if post is published or scheduled
        if ($fromStatus === 'copydesk' && $toStatus === ContentVersion::STATUS_DRAFT && $this->isWriterAction($user)) {
            if ($content && in_array($content->status, [Post::STATUS_PUBLISHED, Post::STATUS_SCHEDULED])) {
                throw new \Exception('Cannot withdraw a post that is already published or scheduled');
            }
        }

        // Validate requirements before parking - category and tags must be set
        if ($toStatus === ContentVersion::STATUS_PARKED) {
            if ($content) {
                $this->validateParkRequirements($content, $version);
            }
        }

        // Scheduling requires a scheduled_at date on the post
        if ($toStatus === 'scheduled') {
            if ($content && empty($content->scheduled_at)) {
                throw new \Exception('A scheduled date must be set before scheduling');
            }
        }

        return DB::transaction(function () use ($version, $toStatus, $comment, $user, $fromStatus, $content) {
            // Create the transition record
            $transition = $version->transitions()->create([
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'performed_by' => $user->id,
                'comment' => $comment,
            ]);

            // Update version status
            $version->update(['workflow_status' => $toStatus]);

            if ($content) {
                $content->update(['workflow_status' => $toStatus]);

                // If publishing, also update the content's publish status
                if ($toStatus === ContentVersion::STATUS_PUBLISHED) {
                    $this->publishVersion($version);
                }

                // If scheduling, update the content's status to scheduled
                if ($toStatus === 'scheduled') {
                    $content->update([
                        'status' => Post::STATUS_SCHEDULED,
                    ]);
                }

                // Unpublish: published → copydesk
                if ($fromStatus === ContentVersion::STATUS_PUBLISHED && $toStatus === 'copydesk') {
                    $version->deactivate();
                    $content->update([
                        'status' => Post::STATUS_DRAFT,
                        'published_at' => null,
                        'active_version_id' => null,
                        'draft_version_id' => $version->id,
                    ]);
                }

                // Unschedule: scheduled → copydesk
                if ($fromStatus === 'scheduled' && $toStatus === 'copydesk') {
                    $content->update([
                        'status' => Post::STATUS_DRAFT,
                        'scheduled_at' => null,
                    ]);
                }

                // Send workflow notifications
                $this->notificationService->workflowTransition(
                    $version,
                    $fromStatus,
                    $toStatus,
                    $user,
                    $comment
                );
            }

            return $transition;
        });
    }

    /**
     * Check if the user is acting as a Writer (not an Editor/Admin).
     */
    protected function isWriterAction(User $user): bool
    {
        $roles = $user->getRoleNames()->toArray();

        return in_array('Writer', $roles) && empty(array_intersect($roles, ['Editor', 'Admin', 'Developer']));
    }

    /**
     * Publish a version.
     */
    public function publishVersion(ContentVersion $version): void
    {
        $allowedStatuses = [ContentVersion::STATUS_PUBLISHED, ContentVersion::STATUS_PARKED, ContentVersion::STATUS_COPYDESK, 'scheduled'];
        if (! in_array($version->workflow_status, $allowedStatuses)) {
            throw new \Exception('Version cannot be published from its current status');
        }

        // Validate that content has required fields before publishing
        $content = $version->versionable;
        if ($content) {
            $this->validatePublishRequirements($content);
        }

        DB::transaction(function () use ($version) {
            // Activate this version and mark as published
            $version->activate();
            $version->update(['workflow_status' => ContentVersion::STATUS_PUBLISHED]);

            // Update the parent content
            $content = $version->versionable;
            if ($content) {
                // Apply the version snapshot to the content
                $this->applySnapshotToContent($content, $version->content_snapshot);

                // Update publishing status
                $content->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'workflow_status' => ContentVersion::STATUS_PUBLISHED,
                ]);
            }
        });
    }

    /**
     * Unpublish content — moves to copydesk, not draft.
     */
    public function unpublish(Model $content): void
    {
        DB::transaction(function () use ($content) {
            // Deactivate the current active version
            $activeVersion = $content->activeVersion;
            if ($activeVersion) {
                $activeVersion->deactivate();
                $activeVersion->update(['workflow_status' => 'copydesk']);
            }

            // Update content status — goes to copydesk for editorial review
            $content->update([
                'status' => Post::STATUS_DRAFT,
                'published_at' => null,
                'workflow_status' => 'copydesk',
                'active_version_id' => null,
                'draft_version_id' => $activeVersion?->id ?? $content->draft_version_id,
            ]);
        });
    }

    /**
     * Create a new version of the content.
     *
     * @param  array<string, mixed>  $data
     */
    public function createVersion(
        Model $content,
        array $data,
        ?string $note = null,
        ?User $user = null
    ): ContentVersion {
        $user = $user ?? auth()->user();

        return DB::transaction(function () use ($content, $data, $note, $user) {
            $nextVersionNumber = $content->versions()->max('version_number') + 1;

            $version = $content->versions()->create([
                'version_number' => $nextVersionNumber,
                'content_snapshot' => $data,
                'workflow_status' => ContentVersion::STATUS_DRAFT,
                'is_active' => false,
                'created_by' => $user->id,
                'version_note' => $note,
            ]);

            // Record initial transition
            $version->transitions()->create([
                'from_status' => null,
                'to_status' => ContentVersion::STATUS_DRAFT,
                'performed_by' => $user->id,
                'comment' => $note,
            ]);

            // Update draft version reference
            $content->update([
                'draft_version_id' => $version->id,
                'workflow_status' => ContentVersion::STATUS_DRAFT,
            ]);

            return $version;
        });
    }

    /**
     * Update the current draft version.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateDraftVersion(
        Model $content,
        array $data
    ): ContentVersion {
        $draftVersion = $content->draftVersion;

        // Only update if it's still in draft status
        if ($draftVersion && $draftVersion->isDraft()) {
            $draftVersion->update([
                'content_snapshot' => $data,
            ]);

            return $draftVersion->fresh();
        }

        // Otherwise, create a new version
        return $this->createVersion($content, $data, 'Updated from previous version');
    }

    /**
     * Revert to a previous version (creates a new draft).
     */
    public function revertToVersion(ContentVersion $version): ContentVersion
    {
        $content = $version->versionable;

        return $this->createVersion(
            $content,
            $version->content_snapshot,
            "Reverted from version {$version->version_number}"
        );
    }

    /**
     * Make a version the live (active) version for published content.
     * Simply switches which version is active without creating duplicates.
     */
    public function makeVersionLive(ContentVersion $version): ContentVersion
    {
        $content = $version->versionable;

        // Only allow this for published content
        if ($content->status !== 'published') {
            throw new \Exception('This operation is only available for published content');
        }

        return DB::transaction(function () use ($content, $version) {
            // Deactivate ALL existing versions
            ContentVersion::where('versionable_type', get_class($content))
                ->where('versionable_id', $content->id)
                ->update(['is_active' => false]);

            // Activate this version and mark as published
            $version->update([
                'is_active' => true,
                'workflow_status' => ContentVersion::STATUS_PUBLISHED,
            ]);

            // Apply the snapshot to the content
            $this->applySnapshotToContent($content, $version->content_snapshot);

            // Update content's version references
            $content->update([
                'active_version_id' => $version->id,
                'draft_version_id' => $version->id,
                'workflow_status' => ContentVersion::STATUS_PUBLISHED,
            ]);

            return $version->fresh();
        });
    }

    /**
     * Get the version history for content.
     */
    public function getVersionHistory(Model $content): Collection
    {
        return $content->versions()
            ->with(['createdBy', 'transitions.performedBy'])
            ->orderBy('version_number', 'desc')
            ->get();
    }

    /**
     * Compare two versions.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function compareVersions(ContentVersion $versionA, ContentVersion $versionB): array
    {
        $snapshotA = $versionA->content_snapshot;
        $snapshotB = $versionB->content_snapshot;

        $allKeys = array_unique(array_merge(
            array_keys($snapshotA ?? []),
            array_keys($snapshotB ?? [])
        ));

        $diff = [];
        foreach ($allKeys as $key) {
            $oldValue = $snapshotA[$key] ?? null;
            $newValue = $snapshotB[$key] ?? null;

            if ($oldValue !== $newValue) {
                $diff[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $diff;
    }

    /**
     * Get the workflow state configuration for a status.
     *
     * @return array<string, mixed>|null
     */
    public function getStateConfig(Model $content, string $status): ?array
    {
        $workflow = $this->getWorkflowFor($content);
        $states = $workflow['states'] ?? [];

        foreach ($states as $state) {
            if ($state['key'] === $status) {
                return $state;
            }
        }

        return null;
    }

    /**
     * Check if a user can publish content.
     */
    public function canPublish(User $user, Model $content): bool
    {
        $workflow = $this->getWorkflowFor($content);
        $publishRoles = $workflow['publish_roles'] ?? ['Editor', 'Admin'];
        $userRoles = $user->getRoleNames()->toArray();

        $hasRole = ! empty(array_intersect($userRoles, $publishRoles));
        $hasParkedVersion = $content->draftVersion && $content->draftVersion->isParked();

        return $hasRole && $hasParkedVersion;
    }

    /**
     * Apply a version snapshot to the content model.
     *
     * @param  array<string, mixed>  $snapshot
     */
    protected function applySnapshotToContent(Model $content, array $snapshot): void
    {
        // Get versionable fields from the content
        $fields = method_exists($content, 'getVersionableFields')
            ? $content->getVersionableFields()
            : array_keys($snapshot);

        $updateData = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $snapshot)) {
                $updateData[$field] = $snapshot[$field];
            }
        }

        $content->update($updateData);

        // Sync categories if present
        if (isset($snapshot['category_ids']) && method_exists($content, 'categories')) {
            $content->categories()->sync($snapshot['category_ids'] ?? []);
        }

        // Sync tags if present
        if (isset($snapshot['tag_ids']) && method_exists($content, 'tags')) {
            $content->tags()->sync($snapshot['tag_ids'] ?? []);
        }
    }

    /**
     * Validate that content meets parking requirements.
     * Category and tags must be set before a post can be parked.
     *
     * @throws \Exception
     */
    protected function validateParkRequirements(Model $content, ContentVersion $version): void
    {
        $errors = [];

        $snapshot = $version->content_snapshot ?? [];

        // Check for category
        if (method_exists($content, 'categories')) {
            $categoryIds = $snapshot['category_ids'] ?? [];
            if (empty($categoryIds) && method_exists($content, 'categories')) {
                $categoryIds = $content->categories()->pluck('categories.id')->toArray();
            }
            if (empty($categoryIds)) {
                $errors[] = 'A category must be assigned before parking';
            }
        }

        // Check for tags
        if (method_exists($content, 'tags')) {
            $tagIds = $snapshot['tag_ids'] ?? [];
            if (empty($tagIds)) {
                $tagIds = $content->tags()->pluck('tags.id')->toArray();
            }
            if (empty($tagIds)) {
                $errors[] = 'At least one tag must be assigned before parking';
            }
        }

        if (! empty($errors)) {
            throw new \Exception(implode('. ', $errors));
        }
    }

    /**
     * Validate that content meets publishing requirements.
     *
     * @throws \Exception
     */
    protected function validatePublishRequirements(Model $content): void
    {
        $errors = [];

        // Check for category (if the content has categories)
        if (method_exists($content, 'category')) {
            if (empty($content->category_id)) {
                $errors[] = 'A category must be assigned before publishing';
            }
        }

        // Check for tags (if the content has tags relationship)
        if (method_exists($content, 'tags')) {
            if ($content->tags()->count() === 0) {
                $errors[] = 'At least one tag must be assigned before publishing';
            }
        }

        if (! empty($errors)) {
            throw new \Exception(implode('. ', $errors));
        }
    }
}
