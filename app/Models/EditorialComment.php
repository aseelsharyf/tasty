<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorialComment extends Model
{
    use HasFactory, HasUuid;

    public const TYPE_GENERAL = 'general';

    public const TYPE_REVISION_REQUEST = 'revision_request';

    public const TYPE_APPROVAL = 'approval';

    protected $fillable = [
        'content_version_id',
        'user_id',
        'content',
        'block_id',
        'type',
        'is_resolved',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    // Relationships

    public function version(): BelongsTo
    {
        return $this->belongsTo(ContentVersion::class, 'content_version_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Methods

    /**
     * Mark the comment as resolved.
     */
    public function resolve(): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);
    }

    /**
     * Mark the comment as unresolved.
     */
    public function unresolve(): void
    {
        $this->update([
            'is_resolved' => false,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);
    }

    // Scopes

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRevisionRequests($query)
    {
        return $query->ofType(self::TYPE_REVISION_REQUEST);
    }

    // Helpers

    public function isRevisionRequest(): bool
    {
        return $this->type === self::TYPE_REVISION_REQUEST;
    }

    public function isApproval(): bool
    {
        return $this->type === self::TYPE_APPROVAL;
    }

    public function isGeneral(): bool
    {
        return $this->type === self::TYPE_GENERAL;
    }

    public function hasBlockReference(): bool
    {
        return $this->block_id !== null;
    }
}
