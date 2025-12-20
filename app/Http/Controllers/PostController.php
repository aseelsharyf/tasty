<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PostController extends Controller
{
    /**
     * Display a single post.
     */
    public function show(string $categorySlug, string $postSlug): View
    {
        // Find the category (optional - for URL validation)
        $category = Category::where('slug', $categorySlug)->first();

        // Find the post by slug
        $post = Post::where('slug', $postSlug)
            ->published()
            ->with(['author', 'categories', 'tags', 'featuredMedia'])
            ->first();

        if (! $post) {
            throw new NotFoundHttpException("Post not found: {$postSlug}");
        }

        // Get related posts from the same category
        $relatedPosts = Post::published()
            ->whereHas('categories', function ($query) use ($post) {
                $query->whereIn('categories.id', $post->categories->pluck('id'));
            })
            ->where('id', '!=', $post->id)
            ->with(['author', 'categories', 'tags', 'featuredMedia'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('posts.show', [
            'post' => $post,
            'category' => $category,
            'relatedPosts' => $relatedPosts,
        ]);
    }
}
