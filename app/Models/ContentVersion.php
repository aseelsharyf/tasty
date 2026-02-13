<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentVersion extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_COPYDESK = 'copydesk';

    public const STATUS_PARKED = 'parked';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHED = 'published';

    /** @deprecated Use STATUS_COPYDESK instead */
    public const STATUS_REVIEW = 'copydesk';

    /** @deprecated Use STATUS_PARKED instead */
    public const STATUS_APPROVED = 'parked';

    protected $fillable = [
        'versionable_type',
        'versionable_id',
        'version_number',
        'content_snapshot',
        'workflow_status',
        'is_active',
        'created_by',
        'version_note',
    ];

    protected function casts(): array
    {
        return [
            'content_snapshot' => 'array',
            'is_active' => 'boolean',
            'version_number' => 'integer',
        ];
    }

    // Relationships

    public function versionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class, 'content_version_id');
    }

    public function editorialComments(): HasMany
    {
        return $this->hasMany(EditorialComment::class, 'content_version_id');
    }

    public function unresolvedComments(): HasMany
    {
        return $this->editorialComments()->where('is_resolved', false);
    }

    // Methods

    /**
     * Get a specific field from the content snapshot.
     */
    public function getSnapshotField(string $key, mixed $default = null): mixed
    {
        return data_get($this->content_snapshot, $key, $default);
    }

    /**
     * Create a workflow transition to a new status.
     */
    public function transitionTo(string $toStatus, ?string $comment = null, ?int $performedBy = null): WorkflowTransition
    {
        $fromStatus = $this->workflow_status;

        $transition = $this->transitions()->create([
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'performed_by' => $performedBy ?? auth()->id(),
            'comment' => $comment,
        ]);

        $this->update(['workflow_status' => $toStatus]);

        // Update the parent content's workflow status
        if ($this->versionable) {
            $this->versionable->update(['workflow_status' => $toStatus]);
        }

        return $transition;
    }

    /**
     * Mark this version as the active (published) version.
     */
    public function activate(): void
    {
        // Deactivate any other active versions for this content
        static::where('versionable_type', $this->versionable_type)
            ->where('versionable_id', $this->versionable_id)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        $this->update(['is_active' => true]);

        // Update the parent content
        if ($this->versionable) {
            $this->versionable->update([
                'active_version_id' => $this->id,
            ]);
        }
    }

    /**
     * Deactivate this version.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);

        if ($this->versionable) {
            $this->versionable->update([
                'active_version_id' => null,
            ]);
        }
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfStatus($query, string $status)
    {
        return $query->where('workflow_status', $status);
    }

    public function scopeDraft($query)
    {
        return $query->ofStatus(self::STATUS_DRAFT);
    }

    public function scopeParked($query)
    {
        return $query->ofStatus(self::STATUS_PARKED);
    }

    // Helpers

    public function isDraft(): bool
    {
        return $this->workflow_status === self::STATUS_DRAFT;
    }

    public function isInReview(): bool
    {
        return $this->workflow_status === self::STATUS_REVIEW;
    }

    public function isInCopydesk(): bool
    {
        return $this->workflow_status === self::STATUS_COPYDESK;
    }

    public function isParked(): bool
    {
        return $this->workflow_status === self::STATUS_PARKED;
    }

    /** @deprecated Use isParked() instead */
    public function isApproved(): bool
    {
        return $this->isParked();
    }

    public function isRejected(): bool
    {
        return $this->workflow_status === self::STATUS_REJECTED;
    }

    public function isPublished(): bool
    {
        return $this->workflow_status === self::STATUS_PUBLISHED;
    }

    public function canBePublished(): bool
    {
        return $this->isParked();
    }
}
