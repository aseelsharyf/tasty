<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\OgImageService;
use Illuminate\Http\Response;

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

        // Read the generated image and return it directly
        $filename = 'og-images/posts/'.$post->slug.'.png';
        $path = storage_path('app/public/'.$filename);

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
