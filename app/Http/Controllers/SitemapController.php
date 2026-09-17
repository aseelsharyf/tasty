<?php

namespace App\Http\Controllers;

use App\Services\PublicCacheService;
use App\Services\SitemapService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(
        protected SitemapService $sitemapService,
    ) {}

    public function __invoke(): Response
    {
        $content = PublicCacheService::remember('public:sitemap', PublicCacheService::sitemapTtl(), function () {
            return $this->sitemapService->generate();
        });

        return response($content, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
