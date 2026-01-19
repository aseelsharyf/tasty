<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\OgImageService;
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
}
