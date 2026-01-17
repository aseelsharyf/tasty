<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\ContentVersion;
use App\Models\EditorialComment;
use App\Models\Post;
use App\Services\NotificationService;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Transition a version to a new status.
     */
    public function transition(Request $request, ContentVersion $version): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'to_status' => ['required', 'string'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $transition = $this->workflowService->transition(
                $version,
                $validated['to_status'],
                $validated['comment'] ?? null
            );

            $message = "Transitioned to {$transition->to_status}";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'transition' => $transition,
                    'version' => $version->fresh(),
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Add an editorial comment to a version.
     */
    public function addComment(Request $request, ContentVersion $version): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'block_id' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'in:general,revision_request,approval'],
        ]);

        $comment = $version->editorialComments()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'block_id' => $validated['block_id'] ?? null,
            'type' => $validated['type'] ?? EditorialComment::TYPE_GENERAL,
        ]);

        $comment->load('user');

        // Send notifications
        $this->notificationService->commentAdded($comment);
        $this->notificationService->parseAndNotifyMentions($validated['content'], $comment);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'comment' => $comment,
            ]);
        }

        return back()->with('success', 'Comment added.');
    }

    /**
     * Resolve an editorial comment.
     */
    public function resolveComment(Request $request, EditorialComment $comment): RedirectResponse|JsonResponse
    {
        $comment->resolve();

        // Send notification to comment author
        $this->notificationService->commentResolved($comment, auth()->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'comment' => $comment->fresh(),
            ]);
        }

        return back()->with('success', 'Comment resolved.');
    }

    /**
     * Unresolve an editorial comment.
     */
    public function unresolveComment(Request $request, EditorialComment $comment): RedirectResponse|JsonResponse
    {
        $comment->unresolve();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'comment' => $comment->fresh(),
            ]);
        }

        return back()->with('success', 'Comment marked as unresolved.');
    }

    /**
     * Get version history for a content item.
     */
    public function history(Request $request, string $type, string $uuid): JsonResponse
    {
        $content = $this->resolveContent($type, $uuid);

        $versions = $this->workflowService->getVersionHistory($content);

        return response()->json([
            'versions' => $versions->map(fn ($version) => [
                'id' => $version->id,
                'uuid' => $version->uuid,
                'version_number' => $version->version_number,
                'workflow_status' => $version->workflow_status,
                'is_active' => $version->is_active,
                'version_note' => $version->version_note,
                'content_snapshot' => $version->content_snapshot,
                'created_by' => $version->createdBy ? [
                    'id' => $version->createdBy->id,
                    'name' => $version->createdBy->name,
                    'avatar_url' => $version->createdBy->avatar_url,
                ] : null,
                'created_at' => $version->created_at,
                'transitions' => $version->transitions->map(fn ($t) => [
                    'id' => $t->id,
                    'from_status' => $t->from_status,
                    'to_status' => $t->to_status,
                    'comment' => $t->comment,
                    'performed_by' => $t->performedBy ? [
                        'id' => $t->performedBy->id,
                        'name' => $t->performedBy->name,
                    ] : null,
                    'created_at' => $t->created_at,
                ]),
            ]),
        ]);
    }

    /**
     * Compare two versions.
     */
    public function compare(ContentVersion $versionA, ContentVersion $versionB): JsonResponse
    {
        $diff = $this->workflowService->compareVersions($versionA, $versionB);

        return response()->json([
            'version_a' => [
                'id' => $versionA->id,
                'version_number' => $versionA->version_number,
                'created_at' => $versionA->created_at,
            ],
            'version_b' => [
                'id' => $versionB->id,
                'version_number' => $versionB->version_number,
                'created_at' => $versionB->created_at,
            ],
            'diff' => $diff,
        ]);
    }

    /**
     * Revert to a previous version (creates a new draft).
     */
    public function revert(Request $request, ContentVersion $version): RedirectResponse|JsonResponse
    {
        try {
            $newVersion = $this->workflowService->revertToVersion($version);

            $message = "Reverted to version {$version->version_number}";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'version' => $newVersion,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Make a version the live (active) version for published content.
     */
    public function makeLive(Request $request, ContentVersion $version): RedirectResponse|JsonResponse
    {
        try {
            $newVersion = $this->workflowService->makeVersionLive($version);

            $message = "Version {$version->version_number} is now live";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'version' => $newVersion,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Publish an approved version.
     */
    public function publish(Request $request, ContentVersion $version): RedirectResponse|JsonResponse
    {
        try {
            $this->workflowService->publishVersion($version);

            $message = 'Content published successfully.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'version' => $version->fresh(),
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Unpublish content.
     */
    public function unpublish(Request $request, string $type, string $uuid): RedirectResponse|JsonResponse
    {
        $content = $this->resolveContent($type, $uuid);

        try {
            $this->workflowService->unpublish($content);

            $message = 'Content unpublished.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get available transitions for a version.
     */
    public function availableTransitions(ContentVersion $version): JsonResponse
    {
        $transitions = $this->workflowService->getAvailableTransitions(
            auth()->user(),
            $version
        );

        return response()->json([
            'current_status' => $version->workflow_status,
            'transitions' => $transitions,
        ]);
    }

    /**
     * Get editorial comments for a version.
     */
    public function comments(ContentVersion $version): JsonResponse
    {
        $comments = $version->editorialComments()
            ->with('user', 'resolvedBy')
            ->latest()
            ->get();

        return response()->json([
            'comments' => $comments->map(fn ($comment) => [
                'id' => $comment->id,
                'uuid' => $comment->uuid,
                'content' => $comment->content,
                'block_id' => $comment->block_id,
                'type' => $comment->type,
                'is_resolved' => $comment->is_resolved,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'avatar_url' => $comment->user->avatar_url,
                ],
                'resolved_by' => $comment->resolvedBy ? [
                    'id' => $comment->resolvedBy->id,
                    'name' => $comment->resolvedBy->name,
                ] : null,
                'resolved_at' => $comment->resolved_at,
                'created_at' => $comment->created_at,
            ]),
            'unresolved_count' => $comments->where('is_resolved', false)->count(),
        ]);
    }

    /**
     * Resolve content type and UUID to a model.
     */
    protected function resolveContent(string $type, string $uuid): Post
    {
        return match ($type) {
            'post', 'posts' => Post::where('uuid', $uuid)->firstOrFail(),
            default => throw new \InvalidArgumentException("Unknown content type: {$type}"),
        };
    }
}
