<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends Controller
{
    public function __construct(
        protected SeoService $seoService,
    ) {}

    /**
     * Display the home page.
     */
    public function home(): View
    {
        return $this->show('home');
    }

    /**
     * Display a page by slug.
     */
    public function show(string $slug): View
    {
        // In local environment, check if a direct blade template exists first
        // This makes it easier to develop and debug page templates
        if (app()->environment('local') && ViewFacade::exists("pages.{$slug}")) {
            return view("pages.{$slug}");
        }

        $page = Page::findBySlug($slug);

        if (! $page) {
            throw new NotFoundHttpException("Page not found: {$slug}");
        }

        // Set SEO
        $this->seoService->setPage($page);

        return view('pages.show', [
            'page' => $page,
        ]);
    }
}
