<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentBan extends Model
{
    public const TYPE_EMAIL = 'email';

    public const TYPE_IP = 'ip';

    public const TYPE_USER = 'user';

    protected $fillable = [
        'type',
        'value',
        'reason',
        'banned_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    // Relationships

    public function bannedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // Static helpers

    public static function isEmailBanned(string $email): bool
    {
        return static::active()
            ->ofType(self::TYPE_EMAIL)
            ->where('value', strtolower($email))
            ->exists();
    }

    public static function isIpBanned(string $ip): bool
    {
        return static::active()
            ->ofType(self::TYPE_IP)
            ->where('value', $ip)
            ->exists();
    }

    public static function isUserBanned(int $userId): bool
    {
        return static::active()
            ->ofType(self::TYPE_USER)
            ->where('value', (string) $userId)
            ->exists();
    }

    public static function isBanned(?string $email = null, ?string $ip = null, ?int $userId = null): bool
    {
        if ($userId && static::isUserBanned($userId)) {
            return true;
        }

        if ($email && static::isEmailBanned($email)) {
            return true;
        }

        if ($ip && static::isIpBanned($ip)) {
            return true;
        }

        return false;
    }

    // Instance methods

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPermanent(): bool
    {
        return $this->expires_at === null;
    }
}
