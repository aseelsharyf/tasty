<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analytics
    ) {}

    public function articles(Request $request): Response
    {
        $period = $request->query('period', '30d');

        return Inertia::render('Analytics/Articles', [
            'analytics' => $this->analytics->getArticleAnalytics($period),
            'period' => $period,
        ]);
    }

    public function authors(Request $request): Response
    {
        $period = $request->query('period', '30d');

        return Inertia::render('Analytics/Authors', [
            'analytics' => $this->analytics->getAuthorAnalytics($period),
            'period' => $period,
        ]);
    }

    public function products(Request $request): Response
    {
        $period = $request->query('period', '30d');

        return Inertia::render('Analytics/Products', [
            'analytics' => $this->analytics->getProductAnalytics($period),
            'period' => $period,
        ]);
    }
}
