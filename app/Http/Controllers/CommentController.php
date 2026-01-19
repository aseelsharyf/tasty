<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CommentController extends Controller
{
    /**
     * Store a newly created comment.
     */
    public function store(Request $request): JsonResponse
    {
        // Rate limiting: 5 comments per minute per IP
        $key = 'comment-submit:'.($request->ip() ?? 'unknown');

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => "Too many comments. Please wait {$seconds} seconds before trying again.",
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $validated = $request->validate([
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            'content' => ['required', 'string', 'min:3', 'max:5000'],
            'author_name' => ['required_without:user_id', 'nullable', 'string', 'max:100'],
            'author_email' => ['nullable', 'email', 'max:255'],
        ]);

        // Verify the post exists and allows comments
        $post = Post::findOrFail($validated['post_id']);

        if (! ($post->allow_comments ?? true)) {
            return response()->json([
                'message' => 'Comments are disabled for this post.',
            ], 403);
        }

        // Verify parent comment belongs to the same post (if replying)
        if (! empty($validated['parent_id'])) {
            $parentComment = Comment::find($validated['parent_id']);

            if (! $parentComment || $parentComment->post_id !== $post->id) {
                return response()->json([
                    'message' => 'Invalid parent comment.',
                ], 422);
            }

            // Limit reply depth to 2 levels
            if ($parentComment->parent_id !== null) {
                // Parent is already a reply, so this would be level 3
                // Instead, make this a reply to the root comment
                $validated['parent_id'] = $parentComment->parent_id;
            }
        }

        // Build comment data
        $commentData = [
            'post_id' => $post->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => $validated['content'],
            'status' => Comment::STATUS_PENDING,
            'author_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Handle authenticated vs guest user
        if ($user = $request->user()) {
            $commentData['user_id'] = $user->id;
        } else {
            $commentData['author_name'] = $validated['author_name'];
            $commentData['author_email'] = $validated['author_email'];
        }

        $comment = Comment::create($commentData);

        return response()->json([
            'message' => 'Your comment has been submitted and is awaiting moderation.',
            'comment' => [
                'id' => $comment->id,
                'uuid' => $comment->uuid,
            ],
        ], 201);
    }
}
