<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Comment::query()
            ->with(['post:id,uuid,title,slug,language_code', 'user:id,name,email', 'parent:id,uuid,content,author_name,user_id', 'parent.user:id,name'])
            ->withCount('replies');

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status === 'trashed') {
                $query->trashed();
            } else {
                $query->where('status', $status);
            }
        }

        // Filter by post
        if ($request->filled('post_id')) {
            $query->where('post_id', $request->get('post_id'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('content', 'ilike', "%{$search}%")
                    ->orWhere('author_name', 'ilike', "%{$search}%")
                    ->orWhere('author_email', 'ilike', "%{$search}%");
            });
        }

        // Sort
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $comments = $query->paginate(20)->withQueryString();

        // Get counts for status tabs
        $counts = [
            'all' => Comment::count(),
            'pending' => Comment::pending()->count(),
            'approved' => Comment::approved()->count(),
            'spam' => Comment::spam()->count(),
            'trashed' => Comment::where('status', Comment::STATUS_TRASHED)->count(),
        ];

        // Get selected post if filtering by post_id
        $selectedPost = null;
        if ($request->filled('post_id')) {
            $selectedPost = Post::select('id', 'title')->find($request->get('post_id'));
        }

        return Inertia::render('Comments/Index', [
            'comments' => $comments->through(fn (Comment $comment) => [
                'id' => $comment->id,
                'uuid' => $comment->uuid,
                'content' => $comment->content,
                'content_excerpt' => \Illuminate\Support\Str::limit($comment->content, 100),
                'status' => $comment->status,
                'author_display_name' => $comment->author_display_name,
                'author_display_email' => $comment->author_display_email,
                'author_ip' => $comment->author_ip,
                'gravatar_url' => $comment->gravatar_url,
                'is_registered_user' => $comment->is_registered_user,
                'is_edited' => $comment->is_edited,
                'replies_count' => $comment->replies_count,
                'post' => $comment->post ? [
                    'id' => $comment->post->id,
                    'uuid' => $comment->post->uuid,
                    'title' => $comment->post->title,
                    'slug' => $comment->post->slug,
                    'language_code' => $comment->post->language_code,
                ] : null,
                'parent' => $comment->parent ? [
                    'id' => $comment->parent->id,
                    'uuid' => $comment->parent->uuid,
                    'author_name' => $comment->parent->author_display_name,
                    'content_excerpt' => \Illuminate\Support\Str::limit($comment->parent->content, 80),
                ] : null,
                'created_at' => $comment->created_at,
                'edited_at' => $comment->edited_at,
            ]),
            'counts' => $counts,
            'selectedPost' => $selectedPost,
            'filters' => $request->only(['status', 'post_id', 'search', 'sort', 'direction']),
        ]);
    }

    public function queue(Request $request): Response
    {
        $query = Comment::query()
            ->pending()
            ->with(['post:id,uuid,title,slug,language_code', 'user:id,name,email', 'parent:id,uuid,content,author_name,user_id', 'parent.user:id,name'])
            ->orderBy('created_at', 'asc');

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('content', 'ilike', "%{$search}%")
                    ->orWhere('author_name', 'ilike', "%{$search}%")
                    ->orWhere('author_email', 'ilike', "%{$search}%");
            });
        }

        $comments = $query->paginate(20)->withQueryString();

        $pendingCount = Comment::pending()->count();

        return Inertia::render('Comments/Queue', [
            'comments' => $comments->through(fn (Comment $comment) => [
                'id' => $comment->id,
                'uuid' => $comment->uuid,
                'content' => $comment->content,
                'content_excerpt' => \Illuminate\Support\Str::limit($comment->content, 100),
                'status' => $comment->status,
                'author_display_name' => $comment->author_display_name,
                'author_display_email' => $comment->author_display_email,
                'author_ip' => $comment->author_ip,
                'gravatar_url' => $comment->gravatar_url,
                'is_registered_user' => $comment->is_registered_user,
                'is_edited' => $comment->is_edited,
                'post' => $comment->post ? [
                    'id' => $comment->post->id,
                    'uuid' => $comment->post->uuid,
                    'title' => $comment->post->title,
                    'slug' => $comment->post->slug,
                    'language_code' => $comment->post->language_code,
                ] : null,
                'parent' => $comment->parent ? [
                    'id' => $comment->parent->id,
                    'uuid' => $comment->parent->uuid,
                    'author_name' => $comment->parent->author_display_name,
                    'content_excerpt' => \Illuminate\Support\Str::limit($comment->parent->content, 80),
                ] : null,
                'created_at' => $comment->created_at,
                'edited_at' => $comment->edited_at,
            ]),
            'pendingCount' => $pendingCount,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Comment $comment): Response
    {
        $comment->load(['post:id,uuid,title,slug,language_code', 'user:id,name,email', 'editor:id,name', 'parent', 'replies']);

        return Inertia::render('Comments/Show', [
            'comment' => [
                'id' => $comment->id,
                'uuid' => $comment->uuid,
                'content' => $comment->content,
                'status' => $comment->status,
                'author_display_name' => $comment->author_display_name,
                'author_display_email' => $comment->author_display_email,
                'author_website' => $comment->author_website,
                'author_ip' => $comment->author_ip,
                'user_agent' => $comment->user_agent,
                'gravatar_url' => $comment->gravatar_url,
                'is_registered_user' => $comment->is_registered_user,
                'is_edited' => $comment->is_edited,
                'edited_at' => $comment->edited_at,
                'editor' => $comment->editor ? [
                    'id' => $comment->editor->id,
                    'name' => $comment->editor->name,
                ] : null,
                'post' => $comment->post ? [
                    'id' => $comment->post->id,
                    'uuid' => $comment->post->uuid,
                    'title' => $comment->post->title,
                    'slug' => $comment->post->slug,
                    'language_code' => $comment->post->language_code,
                ] : null,
                'parent' => $comment->parent ? [
                    'id' => $comment->parent->id,
                    'uuid' => $comment->parent->uuid,
                    'content_excerpt' => \Illuminate\Support\Str::limit($comment->parent->content, 100),
                ] : null,
                'replies_count' => $comment->replies->count(),
                'created_at' => $comment->created_at,
            ],
        ]);
    }

    public function update(Request $request, Comment $comment): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        $comment->update([
            'content' => $validated['content'],
        ]);

        $comment->markAsEdited(auth()->id());

        return redirect()->back()
            ->with('success', 'Comment updated successfully.');
    }

    public function approve(Comment $comment): RedirectResponse
    {
        $comment->approve();

        return redirect()->back()
            ->with('success', 'Comment approved.');
    }

    public function spam(Comment $comment): RedirectResponse
    {
        $comment->markAsSpam();

        return redirect()->back()
            ->with('success', 'Comment marked as spam.');
    }

    public function trash(Comment $comment): RedirectResponse
    {
        $comment->trash();

        return redirect()->back()
            ->with('success', 'Comment trashed.');
    }

    public function restore(Comment $comment): RedirectResponse
    {
        $comment->restoreFromTrash();

        return redirect()->back()
            ->with('success', 'Comment restored.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->forceDelete();

        return redirect()->route('cms.comments.index')
            ->with('success', 'Comment permanently deleted.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:approve,spam,trash,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'exists:comments,uuid'],
        ]);

        $comments = Comment::whereIn('uuid', $validated['ids'])->get();
        $count = $comments->count();

        foreach ($comments as $comment) {
            match ($validated['action']) {
                'approve' => $comment->approve(),
                'spam' => $comment->markAsSpam(),
                'trash' => $comment->trash(),
                'delete' => $comment->forceDelete(),
            };
        }

        $actionLabels = [
            'approve' => 'approved',
            'spam' => 'marked as spam',
            'trash' => 'trashed',
            'delete' => 'permanently deleted',
        ];

        return redirect()->back()
            ->with('success', "{$count} comments {$actionLabels[$validated['action']]}.");
    }

    public function statistics(): Response
    {
        $counts = [
            'total' => Comment::count(),
            'pending' => Comment::pending()->count(),
            'approved' => Comment::approved()->count(),
            'spam' => Comment::spam()->count(),
            'trashed' => Comment::where('status', Comment::STATUS_TRASHED)->count(),
        ];

        // Comments this month
        $thisMonth = Comment::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Most commented posts
        $mostCommentedPosts = Post::withCount('comments')
            ->having('comments_count', '>', 0)
            ->orderByDesc('comments_count')
            ->limit(10)
            ->get()
            ->map(fn ($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'comments_count' => $post->comments_count,
            ]);

        // Most active commenters (registered users)
        $mostActiveCommenters = Comment::query()
            ->select('user_id')
            ->selectRaw('count(*) as comments_count')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('comments_count')
            ->limit(10)
            ->with('user:id,name,email')
            ->get()
            ->map(fn ($item) => [
                'user' => $item->user ? [
                    'id' => $item->user->id,
                    'name' => $item->user->name,
                    'email' => $item->user->email,
                ] : null,
                'comments_count' => $item->comments_count,
            ]);

        return Inertia::render('Comments/Statistics', [
            'counts' => $counts,
            'thisMonth' => $thisMonth,
            'mostCommentedPosts' => $mostCommentedPosts,
            'mostActiveCommenters' => $mostActiveCommenters,
        ]);
    }

    public function searchPosts(Request $request): \Illuminate\Http\JsonResponse
    {
        $search = $request->get('q', '');

        $posts = Post::query()
            ->select('id', 'title')
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'ilike', "%{$search}%");
            })
            ->whereHas('comments')
            ->orderBy('title')
            ->limit(20)
            ->get()
            ->map(fn ($post) => [
                'value' => (string) $post->id,
                'label' => $post->title,
            ]);

        return response()->json($posts);
    }
}
