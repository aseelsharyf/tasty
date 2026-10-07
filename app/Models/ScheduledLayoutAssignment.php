<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledLayoutAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\ScheduledLayoutAssignmentFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'post_id',
        'content_version_id',
        'layout_type',
        'page_layout_id',
        'section_id',
        'slot_index',
        'scheduled_at',
        'status',
        'processed_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'slot_index' => 'integer',
            'scheduled_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function contentVersion(): BelongsTo
    {
        return $this->belongsTo(ContentVersion::class);
    }

    public function pageLayout(): BelongsTo
    {
        return $this->belongsTo(PageLayout::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
