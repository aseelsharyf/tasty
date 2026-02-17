<?php

namespace App\Http\Controllers;

use App\Services\Layouts\HomepageConfigurationService;
use App\Services\Layouts\SectionDataResolver;
use App\Services\PublicCacheService;
use App\Services\SeoService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __construct(
        protected HomepageConfigurationService $configService,
        protected SectionDataResolver $dataResolver,
        protected SeoService $seoService,
    ) {}

    /**
     * Display the homepage.
     */
    public function __invoke(): Response
    {
        $html = Cache::remember('public:homepage:sections', PublicCacheService::homepageTtl(), function () {
            // Set SEO
            $this->seoService->setHomepage();

            $configuration = $this->configService->getConfiguration();

            // Get enabled sections sorted by order
            $sections = collect($configuration['sections'] ?? [])
                ->filter(fn (array $section) => $section['enabled'] ?? true)
                ->sortBy('order')
                ->map(fn (array $section) => [
                    'type' => $section['type'],
                    'data' => $this->dataResolver->resolve($section),
                ])
                ->values()
                ->all();

            return view('home', [
                'sections' => $sections,
            ])->render();
        });

        return new Response($html);
    }
}
