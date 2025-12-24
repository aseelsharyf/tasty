<?php

namespace App\Http\Controllers;

use App\Services\Layouts\HomepageConfigurationService;
use App\Services\Layouts\SectionDataResolver;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected HomepageConfigurationService $configService,
        protected SectionDataResolver $dataResolver,
    ) {}

    /**
     * Display the homepage.
     */
    public function __invoke(): View
    {
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
        ]);
    }
}
