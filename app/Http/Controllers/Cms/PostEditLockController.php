<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostEditLock;
use Illuminate\Http\JsonResponse;

class PostEditLockController extends Controller
{
    /**
     * Get lock status for a post.
     */
    public function status(Post $post): JsonResponse
    {
        $lockInfo = PostEditLock::getLockInfo($post);
        $user = auth()->user();

        return response()->json([
            'locked' => $lockInfo !== null,
            'lock' => $lockInfo,
            'is_mine' => $lockInfo && $lockInfo['user_id'] === $user->id,
            'can_edit' => $lockInfo === null || $lockInfo['user_id'] === $user->id || $lockInfo['is_stale'],
        ]);
    }

    /**
     * Acquire a lock for editing.
     */
    public function acquire(Post $post): JsonResponse
    {
        $user = auth()->user();
        $lock = PostEditLock::tryAcquire($post, $user);

        if (! $lock) {
            $lockInfo = PostEditLock::getLockInfo($post);

            return response()->json([
                'success' => false,
                'message' => "This post is being edited by {$lockInfo['user_name']}",
                'lock' => $lockInfo,
            ], 423); // 423 Locked
        }

        return response()->json([
            'success' => true,
            'lock' => PostEditLock::getLockInfo($post),
        ]);
    }

    /**
     * Send heartbeat to keep lock alive.
     */
    public function heartbeat(Post $post): JsonResponse
    {
        $user = auth()->user();
        $lock = PostEditLock::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $lock) {
            return response()->json([
                'success' => false,
                'message' => 'You do not hold the lock for this post',
            ], 403);
        }

        $lock->heartbeat();

        return response()->json([
            'success' => true,
            'last_heartbeat_at' => $lock->last_heartbeat_at->toIso8601String(),
        ]);
    }

    /**
     * Release a lock.
     */
    public function release(Post $post): JsonResponse
    {
        $user = auth()->user();
        $released = PostEditLock::release($post, $user);

        return response()->json([
            'success' => $released,
        ]);
    }

    /**
     * Force acquire a lock (take over from another user).
     */
    public function forceAcquire(Post $post): JsonResponse
    {
        $user = auth()->user();
        $lockInfo = PostEditLock::getLockInfo($post);

        // Allow force acquire if:
        // 1. Lock is stale
        // 2. User has posts.force_unlock permission
        $canForce = false;

        if ($lockInfo) {
            if ($lockInfo['is_stale']) {
                $canForce = true;
            } elseif ($user->hasPermission('posts.force_unlock')) {
                $canForce = true;
            }
        } else {
            // No lock exists, just acquire normally
            $canForce = true;
        }

        if (! $canForce) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot force acquire lock. Lock is not stale and you do not have permission.',
            ], 403);
        }

        PostEditLock::forceAcquire($post, $user);

        return response()->json([
            'success' => true,
            'lock' => PostEditLock::getLockInfo($post),
        ]);
    }
}
