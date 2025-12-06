<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CmsNotification extends Model
{
    use HasFactory;
    use HasUuid;

    // Notification types
    public const TYPE_COMMENT = 'comment';

    public const TYPE_COMMENT_RESOLVED = 'comment_resolved';

    public const TYPE_WORKFLOW_SUBMITTED = 'workflow_submitted';

    public const TYPE_WORKFLOW_APPROVED = 'workflow_approved';

    public const TYPE_WORKFLOW_REJECTED = 'workflow_rejected';

    public const TYPE_WORKFLOW_PUBLISHED = 'workflow_published';

    public const TYPE_MENTION = 'mention';

    public const TYPE_ASSIGNMENT = 'assignment';

    public const TYPE_SYSTEM = 'system';

    // Color themes
    public const COLOR_INFO = 'info';

    public const COLOR_SUCCESS = 'success';

    public const COLOR_WARNING = 'warning';

    public const COLOR_ERROR = 'error';

    public const COLOR_NEUTRAL = 'neutral';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'icon',
        'color',
        'notifiable_type',
        'notifiable_id',
        'action_url',
        'action_label',
        'triggered_by',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * The user who receives this notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The user who triggered this notification.
     */
    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * The related model (Post, ContentVersion, EditorialComment, etc.)
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to unread notifications.
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to read notifications.
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    /**
     * Check if notification is read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Check if notification is unread.
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Get default icon for notification type.
     */
    public static function getDefaultIcon(string $type): string
    {
        return match ($type) {
            self::TYPE_COMMENT => 'i-lucide-message-circle',
            self::TYPE_COMMENT_RESOLVED => 'i-lucide-check-circle',
            self::TYPE_WORKFLOW_SUBMITTED => 'i-lucide-send',
            self::TYPE_WORKFLOW_APPROVED => 'i-lucide-thumbs-up',
            self::TYPE_WORKFLOW_REJECTED => 'i-lucide-alert-circle',
            self::TYPE_WORKFLOW_PUBLISHED => 'i-lucide-globe',
            self::TYPE_MENTION => 'i-lucide-at-sign',
            self::TYPE_ASSIGNMENT => 'i-lucide-user-plus',
            self::TYPE_SYSTEM => 'i-lucide-bell',
            default => 'i-lucide-bell',
        };
    }

    /**
     * Get default color for notification type.
     */
    public static function getDefaultColor(string $type): string
    {
        return match ($type) {
            self::TYPE_COMMENT, self::TYPE_MENTION => self::COLOR_INFO,
            self::TYPE_COMMENT_RESOLVED, self::TYPE_WORKFLOW_APPROVED, self::TYPE_WORKFLOW_PUBLISHED => self::COLOR_SUCCESS,
            self::TYPE_WORKFLOW_SUBMITTED, self::TYPE_ASSIGNMENT => self::COLOR_WARNING,
            self::TYPE_WORKFLOW_REJECTED => self::COLOR_ERROR,
            self::TYPE_SYSTEM => self::COLOR_NEUTRAL,
            default => self::COLOR_INFO,
        };
    }
}
