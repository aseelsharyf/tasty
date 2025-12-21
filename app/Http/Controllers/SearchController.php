<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Display search results page.
     */
    public function index(Request $request): View
    {
        $query = $request->input('q', '');
        $type = $request->input('type', 'all');

        $results = [
            'posts' => collect(),
            'categories' => collect(),
            'tags' => collect(),
            'authors' => collect(),
        ];

        if (strlen($query) >= 2) {
            $results = $this->performSearch($query, $type);
        }

        return view('search.index', [
            'query' => $query,
            'type' => $type,
            'results' => $results,
            'totalCount' => $results['posts']->count()
                + $results['categories']->count()
                + $results['tags']->count()
                + $results['authors']->count(),
        ]);
    }

    /**
     * API endpoint for live search suggestions.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = $this->performSearch($query, 'all', 5);

        // Format results for dropdown
        $formatted = [];

        foreach ($results['posts'] as $post) {
            $formatted[] = [
                'type' => 'post',
                'title' => $post->title,
                'subtitle' => $post->categories->first()?->name ?? 'Article',
                'url' => $post->url,
                'image' => $post->featured_image_thumb,
            ];
        }

        foreach ($results['categories'] as $category) {
            $formatted[] = [
                'type' => 'category',
                'title' => $category->name,
                'subtitle' => $category->posts_count.' posts',
                'url' => route('category.show', $category->slug),
                'image' => null,
            ];
        }

        foreach ($results['tags'] as $tag) {
            $formatted[] = [
                'type' => 'tag',
                'title' => $tag->name,
                'subtitle' => $tag->posts_count.' posts',
                'url' => route('tag.show', $tag->slug),
                'image' => null,
            ];
        }

        foreach ($results['authors'] as $author) {
            $formatted[] = [
                'type' => 'author',
                'title' => $author->name,
                'subtitle' => 'Author',
                'url' => route('author.show', $author->username ?? $author->id),
                'image' => $author->avatar_url ?? null,
            ];
        }

        return response()->json([
            'results' => $formatted,
            'query' => $query,
        ]);
    }

    /**
     * Perform search across all content types.
     */
    private function performSearch(string $query, string $type = 'all', int $limit = 12): array
    {
        $results = [
            'posts' => collect(),
            'categories' => collect(),
            'tags' => collect(),
            'authors' => collect(),
        ];

        // Search Posts
        if ($type === 'all' || $type === 'posts') {
            $results['posts'] = Post::query()
                ->published()
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                        ->orWhere('subtitle', 'LIKE', "%{$query}%")
                        ->orWhere('excerpt', 'LIKE', "%{$query}%");
                })
                ->with(['categories', 'author', 'featuredMedia'])
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->get();
        }

        // Search Categories
        if ($type === 'all' || $type === 'categories') {
            $results['categories'] = Category::query()
                ->where('name', 'LIKE', "%{$query}%")
                ->orWhere('description', 'LIKE', "%{$query}%")
                ->withCount('posts')
                ->limit($limit)
                ->get();
        }

        // Search Tags
        if ($type === 'all' || $type === 'tags') {
            $results['tags'] = Tag::query()
                ->where('name', 'LIKE', "%{$query}%")
                ->withCount('posts')
                ->limit($limit)
                ->get();
        }

        // Search Authors
        if ($type === 'all' || $type === 'authors') {
            $results['authors'] = User::query()
                ->whereHas('posts', function ($q) {
                    $q->published();
                })
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%");
                })
                ->limit($limit)
                ->get();
        }

        return $results;
    }
}
