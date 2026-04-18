<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\OgImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class OgImageController extends Controller
{
    public function preview(Post $post, OgImageService $ogImageService): RedirectResponse
    {
        $ogImageService->deleteForPost($post);

        $url = $ogImageService->generateForPost($post);

        if (! $url) {
            abort(404, 'Could not generate OG image for this post');
        }

        return redirect()->away($url);
    }

    public function testPage(): View
    {
        $posts = Post::query()
            ->whereNotNull('featured_media_id')
            ->with(['categories', 'featuredMedia'])
            ->latest('published_at')
            ->take(12)
            ->get();

        return view('og-test', compact('posts'));
    }

    /**
     * Render OG image HTML template for a post.
     * This can be used with screenshot services to generate the actual image.
     */
    public function renderHtml(Post $post): View
    {
        return view('og-image.template', [
            'post' => $post,
        ]);
    }
}
