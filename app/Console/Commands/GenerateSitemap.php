<?php

namespace App\Console\Commands;

use App\Services\SitemapService;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'app:generate-sitemap';

    protected $description = 'Generate a static sitemap.xml in the public directory';

    public function handle(SitemapService $sitemapService): int
    {
        $path = public_path('sitemap.xml');
        file_put_contents($path, $sitemapService->generate());

        $this->info("Sitemap generated at {$path}");

        return self::SUCCESS;
    }
}
