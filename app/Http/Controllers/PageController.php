<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;
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
