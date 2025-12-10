<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostEditLock extends Model
{
    // Lock is considered stale after this many minutes without heartbeat
    public const STALE_MINUTES = 2;

    // Heartbeat interval (frontend should send every X seconds)
    public const HEARTBEAT_INTERVAL_SECONDS = 30;

    protected $fillable = [
        'post_id',
        'user_id',
        'locked_at',
        'last_heartbeat_at',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this lock is stale (no heartbeat for too long).
     */
    public function isStale(): bool
    {
        return $this->last_heartbeat_at->diffInMinutes(now()) >= self::STALE_MINUTES;
    }

    /**
     * Check if the lock is held by the given user.
     */
    public function isHeldBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Update the heartbeat timestamp.
     */
    public function heartbeat(): void
    {
        $this->update(['last_heartbeat_at' => now()]);
    }

    /**
     * Try to acquire a lock for a post.
     * Returns the lock if successful, null if locked by someone else (and not stale).
     */
    public static function tryAcquire(Post $post, User $user): ?self
    {
        $existingLock = self::where('post_id', $post->id)->first();

        if ($existingLock) {
            // If it's our lock, refresh it
            if ($existingLock->isHeldBy($user)) {
                $existingLock->update([
                    'locked_at' => now(),
                    'last_heartbeat_at' => now(),
                ]);

                return $existingLock;
            }

            // If lock is stale, take it over
            if ($existingLock->isStale()) {
                $existingLock->update([
                    'user_id' => $user->id,
                    'locked_at' => now(),
                    'last_heartbeat_at' => now(),
                ]);

                return $existingLock;
            }

            // Lock is held by someone else and not stale
            return null;
        }

        // No existing lock, create one
        return self::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'locked_at' => now(),
            'last_heartbeat_at' => now(),
        ]);
    }

    /**
     * Force acquire a lock (for admins or takeover).
     */
    public static function forceAcquire(Post $post, User $user): self
    {
        return self::updateOrCreate(
            ['post_id' => $post->id],
            [
                'user_id' => $user->id,
                'locked_at' => now(),
                'last_heartbeat_at' => now(),
            ]
        );
    }

    /**
     * Release a lock for a post.
     */
    public static function release(Post $post, ?User $user = null): bool
    {
        $query = self::where('post_id', $post->id);

        // If user specified, only release if they hold it
        if ($user) {
            $query->where('user_id', $user->id);
        }

        return $query->delete() > 0;
    }

    /**
     * Get lock info for a post.
     */
    public static function getLockInfo(Post $post): ?array
    {
        $lock = self::with('user')->where('post_id', $post->id)->first();

        if (! $lock) {
            return null;
        }

        return [
            'id' => $lock->id,
            'user_id' => $lock->user_id,
            'user_name' => $lock->user->name,
            'locked_at' => $lock->locked_at->toIso8601String(),
            'last_heartbeat_at' => $lock->last_heartbeat_at->toIso8601String(),
            'is_stale' => $lock->isStale(),
        ];
    }

    /**
     * Clean up stale locks (can be run via scheduler).
     */
    public static function cleanupStale(): int
    {
        return self::where('last_heartbeat_at', '<', now()->subMinutes(self::STALE_MINUTES * 2))
            ->delete();
    }
}
