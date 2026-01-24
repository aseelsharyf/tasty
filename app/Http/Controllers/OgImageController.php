<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\OgImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class OgImageController extends Controller
{
    public function preview(Post $post, OgImageService $ogImageService): Response
    {
        // Delete existing to force regeneration
        $ogImageService->deleteForPost($post);

        // Generate fresh
        $url = $ogImageService->generateForPost($post);

        if (! $url) {
            abort(404, 'Could not generate OG image for this post');
        }

        // Read the generated image from the configured disk
        $disk = config('media-library.disk_name', 'public');
        $filename = 'og-images/posts/'.$post->slug.'.png';
        $contents = Storage::disk($disk)->get($filename);

        return response($contents, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
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
        // Calculate title size based on length
        $titleLength = mb_strlen($post->title);
        $titleSize = match (true) {
            $titleLength > 100 => 32,
            $titleLength > 70 => 38,
            $titleLength > 50 => 44,
            default => 48,
        };

        return view('og-image.template', [
            'post' => $post,
            'titleSize' => $titleSize,
        ]);
    }
}
