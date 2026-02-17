<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PublicCacheService;
use App\Services\SeoService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class AuthorController extends Controller
{
    public function __construct(
        protected SeoService $seoService,
    ) {}

    public function show(User $author): Response
    {
        $this->seoService->setAuthor($author);

        $page = request()->integer('page', 1);
        $cacheKey = "public:author:{$author->username}:page:{$page}";

        $html = Cache::remember($cacheKey, PublicCacheService::listingTtl(), function () use ($author) {
            $posts = $author->posts()
                ->published()
                ->with(['categories', 'tags'])
                ->orderByDesc('published_at')
                ->paginate(12);

            return view('author.show', [
                'author' => $author,
                'posts' => $posts,
            ])->render();
        });

        return new Response($html);
    }
}
