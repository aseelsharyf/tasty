<?php

namespace App\Services;

use App\Models\ContentVersion;
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
     * Simplified workflow:
     * - Writer: Draft -> CopyDesk (submit for review)
     * - Editor: CopyDesk -> Reject (back to draft) OR Publish directly
     * - Editor: Can write and publish directly, can always edit even published posts
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
                ['key' => 'review', 'label' => 'Copy Desk', 'color' => 'blue', 'icon' => 'i-lucide-spell-check'], // Legacy alias
                ['key' => 'published', 'label' => 'Published', 'color' => 'green', 'icon' => 'i-lucide-globe'],
            ],
            'transitions' => [
                // Writer submits draft for review (goes directly to copydesk)
                ['from' => 'draft', 'to' => 'copydesk', 'roles' => ['Writer', 'Editor', 'Admin'], 'label' => 'Submit for Review'],
                // Editor rejects back to draft
                ['from' => 'copydesk', 'to' => 'draft', 'roles' => ['Editor', 'Admin'], 'label' => 'Request Revisions'],
                // Editor publishes from copydesk
                ['from' => 'copydesk', 'to' => 'published', 'roles' => ['Editor', 'Admin'], 'label' => 'Publish'],
                // Editor can publish directly from draft (skip copydesk)
                ['from' => 'draft', 'to' => 'published', 'roles' => ['Editor', 'Admin'], 'label' => 'Publish'],
                // Unpublish back to draft
                ['from' => 'published', 'to' => 'draft', 'roles' => ['Editor', 'Admin'], 'label' => 'Unpublish'],
                // Legacy: handle old 'review' status - treat same as copydesk
                ['from' => 'review', 'to' => 'copydesk', 'roles' => ['Editor', 'Admin'], 'label' => 'Send to Copy Desk'],
                ['from' => 'review', 'to' => 'draft', 'roles' => ['Editor', 'Admin'], 'label' => 'Request Revisions'],
                ['from' => 'review', 'to' => 'published', 'roles' => ['Editor', 'Admin'], 'label' => 'Publish'],
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

        // Validate requirements before approval - category and tags must be set
        if ($toStatus === ContentVersion::STATUS_APPROVED) {
            $content = $version->versionable;
            if ($content) {
                $this->validateApprovalRequirements($content, $version);
            }
        }

        return DB::transaction(function () use ($version, $toStatus, $comment, $user) {
            $fromStatus = $version->workflow_status;

            // Create the transition record
            $transition = $version->transitions()->create([
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'performed_by' => $user->id,
                'comment' => $comment,
            ]);

            // Update version status
            $version->update(['workflow_status' => $toStatus]);

            // Update the parent content's workflow status
            $content = $version->versionable;
            if ($content) {
                $content->update(['workflow_status' => $toStatus]);

                // If publishing, also update the content's publish status
                if ($toStatus === ContentVersion::STATUS_PUBLISHED) {
                    $this->publishVersion($version);
                }

                // If unpublishing (to draft), deactivate the version and set it as the draft version
                if ($fromStatus === ContentVersion::STATUS_PUBLISHED && $toStatus === ContentVersion::STATUS_DRAFT) {
                    $version->deactivate();
                    $content->update([
                        'status' => 'draft',
                        'published_at' => null,
                        'active_version_id' => null,
                        'draft_version_id' => $version->id, // Set this version as the draft version for editing
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
     * Publish an approved version.
     */
    public function publishVersion(ContentVersion $version): void
    {
        if (! $version->isApproved() && ! $version->isPublished()) {
            throw new \Exception('Only approved versions can be published');
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
     * Unpublish content.
     */
    public function unpublish(Model $content): void
    {
        DB::transaction(function () use ($content) {
            // Deactivate the current active version
            $activeVersion = $content->activeVersion;
            if ($activeVersion) {
                $activeVersion->deactivate();
            }

            // Update content status
            $content->update([
                'status' => 'draft',
                'published_at' => null,
                'workflow_status' => ContentVersion::STATUS_DRAFT,
                'active_version_id' => null,
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
        $hasApprovedVersion = $content->draftVersion && $content->draftVersion->isApproved();

        return $hasRole && $hasApprovedVersion;
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
     * Validate that content meets approval requirements.
     * Category and tags must be set before a post can be approved.
     *
     * @throws \Exception
     */
    protected function validateApprovalRequirements(Model $content, ContentVersion $version): void
    {
        $errors = [];

        // Get category and tags from the version snapshot first,
        // but fallback to the content model if snapshot is empty
        // (this handles cases where tags/categories were added after version submission)
        $snapshot = $version->content_snapshot ?? [];

        // Check for category
        if (method_exists($content, 'categories')) {
            $categoryIds = $snapshot['category_ids'] ?? [];
            // Fallback to content model if snapshot is empty
            if (empty($categoryIds) && method_exists($content, 'categories')) {
                $categoryIds = $content->categories()->pluck('categories.id')->toArray();
            }
            if (empty($categoryIds)) {
                $errors[] = 'A category must be assigned before approval';
            }
        }

        // Check for tags
        if (method_exists($content, 'tags')) {
            $tagIds = $snapshot['tag_ids'] ?? [];
            // Fallback to content model if snapshot is empty
            if (empty($tagIds)) {
                $tagIds = $content->tags()->pluck('tags.id')->toArray();
            }
            if (empty($tagIds)) {
                $errors[] = 'At least one tag must be assigned before approval';
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
