<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SPAM = 'spam';

    public const STATUS_TRASHED = 'trashed';

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'content',
        'status',
        'author_name',
        'author_email',
        'author_website',
        'author_ip',
        'user_agent',
        'is_edited',
        'edited_by',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'is_edited' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    // Relationships

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    // Scopes

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeSpam(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SPAM);
    }

    public function scopeTrashed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_TRASHED);
    }

    public function scopeRootComments(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    // Accessors

    public function getAuthorDisplayNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        return $this->author_name ?? 'Anonymous';
    }

    public function getAuthorDisplayEmailAttribute(): ?string
    {
        if ($this->user) {
            return $this->user->email;
        }

        return $this->author_email;
    }

    public function getGravatarUrlAttribute(): string
    {
        $email = $this->author_display_email ?? '';
        $hash = md5(strtolower(trim($email)));

        return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=80";
    }

    public function getIsRegisteredUserAttribute(): bool
    {
        return $this->user_id !== null;
    }

    public function getRepliesCountAttribute(): int
    {
        return $this->replies()->count();
    }

    // Status helpers

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isSpam(): bool
    {
        return $this->status === self::STATUS_SPAM;
    }

    public function isTrashed(): bool
    {
        return $this->status === self::STATUS_TRASHED;
    }

    // Actions

    public function approve(): void
    {
        $this->update(['status' => self::STATUS_APPROVED]);
    }

    public function markAsSpam(): void
    {
        $this->update(['status' => self::STATUS_SPAM]);
    }

    public function trash(): void
    {
        $this->update(['status' => self::STATUS_TRASHED]);
    }

    public function restoreFromTrash(): void
    {
        $this->update(['status' => self::STATUS_PENDING]);
    }

    public function markAsEdited(int $editorId): void
    {
        $this->update([
            'is_edited' => true,
            'edited_by' => $editorId,
            'edited_at' => now(),
        ]);
    }
}
