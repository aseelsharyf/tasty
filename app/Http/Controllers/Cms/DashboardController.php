<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'users' => User::count(),
                'posts' => Post::withoutTrashed()->count(),
                'published' => Post::where('status', Post::STATUS_PUBLISHED)->count(),
                'drafts' => Post::draft()->count(),
                'pending' => Post::pending()->count(),
                'scheduled' => Post::where('status', Post::STATUS_SCHEDULED)->count(),
                'articles' => Post::articles()->withoutTrashed()->count(),
                'recipes' => Post::recipes()->withoutTrashed()->count(),
                'categories' => Category::count(),
                'tags' => Tag::count(),
            ],
            'recentPosts' => Post::with(['author' => fn ($q) => $q->select('id', 'name')->with('media')])
                ->withoutTrashed()
                ->orderByDesc('created_at')
                ->take(5)
                ->get(['id', 'uuid', 'title', 'status', 'post_type', 'author_id', 'created_at'])
                ->map(fn (Post $post) => [
                    'id' => $post->id,
                    'uuid' => $post->uuid,
                    'title' => $post->title,
                    'status' => $post->status,
                    'post_type' => $post->post_type,
                    'author' => $post->author ? [
                        'name' => $post->author->name,
                        'avatar_url' => $post->author->avatar_url,
                    ] : null,
                    'created_at' => $post->created_at,
                ]),
        ]);
    }
}
