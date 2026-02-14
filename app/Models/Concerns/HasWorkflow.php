<?php

namespace App\Models\Concerns;

use App\Models\ContentVersion;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasWorkflow
{
    /**
     * Boot the trait.
     */
    public static function bootHasWorkflow(): void
    {
        // When creating new content, ensure workflow_status is set
        static::creating(function ($model) {
            if (empty($model->workflow_status)) {
                $model->workflow_status = ContentVersion::STATUS_DRAFT;
            }
        });
    }

    /**
     * Get all versions for this content.
     */
    public function versions(): MorphMany
    {
        return $this->morphMany(ContentVersion::class, 'versionable')
            ->orderBy('version_number', 'desc');
    }

    /**
     * Get the currently active (published) version.
     */
    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(ContentVersion::class, 'active_version_id');
    }

    /**
     * Get the current draft version.
     */
    public function draftVersion(): BelongsTo
    {
        return $this->belongsTo(ContentVersion::class, 'draft_version_id');
    }

    /**
     * Get the latest version regardless of status.
     */
    public function latestVersion(): MorphOne
    {
        return $this->morphOne(ContentVersion::class, 'versionable')
            ->latestOfMany('version_number');
    }

    /**
     * Get the workflow configuration for this content type.
     */
    public function getWorkflowConfig(): array
    {
        // Check for post-type specific workflow first
        if (method_exists($this, 'getPostType')) {
            $postType = $this->getPostType();
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
     */
    protected function getDefaultWorkflowConfig(): array
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
                ['from' => 'draft', 'to' => 'copydesk', 'roles' => ['Writer', 'Editor', 'Admin'], 'label' => 'Send to Copy Desk'],
                ['from' => 'copydesk', 'to' => 'draft', 'roles' => ['Writer'], 'label' => 'Withdraw'],
                ['from' => 'copydesk', 'to' => 'draft', 'roles' => ['Editor', 'Admin'], 'label' => 'Reject'],
                ['from' => 'copydesk', 'to' => 'parked', 'roles' => ['Editor', 'Admin'], 'label' => 'Park'],
                ['from' => 'copydesk', 'to' => 'published', 'roles' => ['Editor', 'Admin'], 'label' => 'Publish'],
                ['from' => 'copydesk', 'to' => 'scheduled', 'roles' => ['Editor', 'Admin'], 'label' => 'Schedule'],
                ['from' => 'parked', 'to' => 'published', 'roles' => ['Editor', 'Admin'], 'label' => 'Publish'],
                ['from' => 'parked', 'to' => 'draft', 'roles' => ['Editor', 'Admin'], 'label' => 'Send Back'],
                // ['from' => 'draft', 'to' => 'published', 'roles' => ['Editor', 'Admin'], 'label' => 'Publish'],
                ['from' => 'published', 'to' => 'copydesk', 'roles' => ['Editor', 'Admin'], 'label' => 'Unpublish'],
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
     * Create a new version from the current content state.
     *
     * @param  array<string, mixed>|null  $data  Override data (if not provided, uses current model state)
     * @param  int|null  $userId  Optional user ID to attribute the version to
     * @param  bool  $preserveWorkflowStatus  If true, preserve the current workflow status instead of resetting to draft
     */
    public function createVersion(?array $data = null, ?string $note = null, ?int $userId = null, bool $preserveWorkflowStatus = false): ContentVersion
    {
        // Determine the user ID: passed in, authenticated, or from author relationship
        $createdBy = $userId ?? auth()->id() ?? $this->author_id ?? null;

        $nextVersionNumber = $this->versions()->max('version_number') + 1;

        // Build content snapshot from model or provided data
        $snapshot = $data ?? $this->buildContentSnapshot();

        // Determine workflow status: preserve current if requested, otherwise default to draft
        $currentStatus = $this->draftVersion?->workflow_status ?? $this->workflow_status ?? ContentVersion::STATUS_DRAFT;
        $workflowStatus = $preserveWorkflowStatus ? $currentStatus : ContentVersion::STATUS_DRAFT;

        $version = $this->versions()->create([
            'version_number' => $nextVersionNumber,
            'content_snapshot' => $snapshot,
            'workflow_status' => $workflowStatus,
            'is_active' => false,
            'created_by' => $createdBy,
            'version_note' => $note,
        ]);

        // Record initial transition (only if status changed or is initial)
        $previousStatus = $preserveWorkflowStatus ? $currentStatus : null;
        $version->transitions()->create([
            'from_status' => $previousStatus,
            'to_status' => $workflowStatus,
            'performed_by' => $createdBy,
            'comment' => $note,
        ]);

        // Update draft version reference and workflow status
        $this->update([
            'draft_version_id' => $version->id,
            'workflow_status' => $workflowStatus,
        ]);

        return $version;
    }

    /**
     * Update the current draft version instead of creating a new one.
     *
     * @param  array<string, mixed>  $data
     * @param  bool  $preserveWorkflowStatus  If true, preserve the current workflow status when creating a new version
     */
    public function updateDraftVersion(array $data, bool $preserveWorkflowStatus = true): ?ContentVersion
    {
        $draftVersion = $this->draftVersion;

        // Check if the draft version is editable (any workflow status — versioning is opt-in)
        $editableStatuses = [ContentVersion::STATUS_DRAFT, 'copydesk', 'parked', 'rejected', 'published', 'scheduled'];
        $isEditable = $draftVersion
            && in_array($draftVersion->workflow_status, $editableStatuses)
            && $draftVersion->versionable_type === static::class
            && $draftVersion->versionable_id === $this->id;

        if (! $isEditable) {
            // No valid editable version - create a new one, preserving workflow status if requested
            return $this->createVersion($data, $draftVersion ? 'Updated from previous version' : 'Initial version', null, $preserveWorkflowStatus);
        }

        // Update the existing version's content
        $draftVersion->update([
            'content_snapshot' => $data,
        ]);

        return $draftVersion;
    }

    /**
     * Build a content snapshot from the model's current state.
     *
     * @return array<string, mixed>
     */
    public function buildContentSnapshot(): array
    {
        // Default implementation - override in model for custom fields
        $fields = $this->getVersionableFields();

        $snapshot = [];
        foreach ($fields as $field) {
            $snapshot[$field] = $this->{$field};
        }

        // Include relationship IDs if applicable
        if (method_exists($this, 'categories')) {
            $snapshot['category_ids'] = $this->categories()->pluck('id')->toArray();
        }

        if (method_exists($this, 'tags')) {
            $snapshot['tag_ids'] = $this->tags()->pluck('id')->toArray();
        }

        return $snapshot;
    }

    /**
     * Get the fields that should be included in version snapshots.
     *
     * @return array<string>
     */
    public function getVersionableFields(): array
    {
        // Override this in your model to specify which fields to version
        return [
            'title',
            'subtitle',
            'excerpt',
            'content',
            'meta_title',
            'meta_description',
            'featured_media_id',
        ];
    }

    /**
     * Restore content from a specific version.
     */
    public function restoreFromVersion(ContentVersion $version): ContentVersion
    {
        $snapshot = $version->content_snapshot;

        // Create a new version with the restored content
        return $this->createVersion(
            $snapshot,
            "Restored from version {$version->version_number}"
        );
    }

    /**
     * Get the current workflow status.
     */
    public function getCurrentWorkflowStatus(): string
    {
        return $this->workflow_status ?? ContentVersion::STATUS_DRAFT;
    }

    /**
     * Get the workflow state configuration for a given status.
     *
     * @return array<string, mixed>|null
     */
    public function getWorkflowState(string $status): ?array
    {
        $config = $this->getWorkflowConfig();
        $states = $config['states'] ?? [];

        foreach ($states as $state) {
            if ($state['key'] === $status) {
                return $state;
            }
        }

        return null;
    }

    /**
     * Check if the content has a published version.
     */
    public function hasPublishedVersion(): bool
    {
        return $this->active_version_id !== null;
    }

    /**
     * Check if the content is currently published.
     */
    public function isWorkflowPublished(): bool
    {
        return $this->workflow_status === ContentVersion::STATUS_PUBLISHED;
    }

    /**
     * Check if the current draft can be published.
     */
    public function canPublish(): bool
    {
        $draftVersion = $this->draftVersion;

        return $draftVersion && $draftVersion->canBePublished();
    }

    /**
     * Get the post type (for post-type specific workflows).
     */
    public function getPostType(): ?string
    {
        return $this->post_type ?? null;
    }
}
