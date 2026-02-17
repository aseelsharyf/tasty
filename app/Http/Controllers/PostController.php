<?php

namespace App\Http\Controllers;

use App\Jobs\RecordViewJob;
use App\Models\Category;
use App\Models\Post;
use App\Services\SeoService;
use App\Support\BotDetector;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PostController extends Controller
{
    public function __construct(
        protected SeoService $seoService,
    ) {}

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
            ->with(['author', 'categories', 'tags', 'featuredMedia', 'sponsor.featuredMedia'])
            ->first();

        if (! $post) {
            throw new NotFoundHttpException("Post not found: {$postSlug}");
        }

        // Track view (non-blocking, bot-filtered)
        if (! BotDetector::isBot(request()->userAgent())) {
            RecordViewJob::dispatch([
                'type' => 'post',
                'model_id' => $post->id,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referrer' => request()->header('referer'),
                'session_id' => session()->getId(),
            ]);
        }

        // Set SEO
        $this->seoService->setPost($post);

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
