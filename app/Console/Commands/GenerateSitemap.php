<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\ProductCategory;
use App\Models\ProductStore;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Console\Command;
use SimpleXMLElement;

class GenerateSitemap extends Command
{
    protected $signature = 'app:generate-sitemap';

    protected $description = 'Generate a static sitemap.xml in the public directory';

    public function handle(): int
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

        // Homepage
        $this->addUrl($xml, url('/'), now()->toDateString(), 'daily', '1.0');

        // Published posts
        Post::query()
            ->published()
            ->with('categories')
            ->orderByDesc('published_at')
            ->chunk(500, function ($posts) use ($xml) {
                foreach ($posts as $post) {
                    $category = $post->categories->first();
                    $slug = $category?->slug ?? 'uncategorized';

                    try {
                        $url = route('post.show', ['category' => $slug, 'post' => $post->slug]);
                    } catch (\Exception) {
                        continue;
                    }

                    $this->addUrl(
                        $xml,
                        $url,
                        $post->updated_at?->toDateString() ?? $post->published_at?->toDateString(),
                        'weekly',
                        '0.8'
                    );
                }
            });

        // Categories
        Category::query()->each(function (Category $category) use ($xml) {
            try {
                $url = route('category.show', $category);
            } catch (\Exception) {
                return;
            }

            $this->addUrl($xml, $url, $category->updated_at?->toDateString(), 'weekly', '0.6');
        });

        // Tags
        Tag::query()->each(function (Tag $tag) use ($xml) {
            try {
                $url = route('tag.show', $tag);
            } catch (\Exception) {
                return;
            }

            $this->addUrl($xml, $url, $tag->updated_at?->toDateString(), 'weekly', '0.5');
        });

        // Author pages
        User::query()
            ->whereHas('posts', fn ($q) => $q->published())
            ->each(function (User $author) use ($xml) {
                if (! $author->username) {
                    return;
                }

                try {
                    $url = route('author.show', $author->username);
                } catch (\Exception) {
                    return;
                }

                $this->addUrl($xml, $url, $author->updated_at?->toDateString(), 'weekly', '0.5');
            });

        // Published pages
        Page::query()
            ->published()
            ->each(function (Page $page) use ($xml) {
                try {
                    $url = route('page.show', $page);
                } catch (\Exception) {
                    return;
                }

                $this->addUrl($xml, $url, $page->updated_at?->toDateString(), 'monthly', '0.5');
            });

        // Products index
        try {
            $this->addUrl($xml, route('products.index'), now()->toDateString(), 'weekly', '0.6');
        } catch (\Exception) {
            // Route may not exist
        }

        // Product categories
        ProductCategory::query()->each(function (ProductCategory $category) use ($xml) {
            try {
                $url = route('products.category', $category);
            } catch (\Exception) {
                return;
            }

            $this->addUrl($xml, $url, $category->updated_at?->toDateString(), 'weekly', '0.5');
        });

        // Product stores
        ProductStore::query()->each(function (ProductStore $store) use ($xml) {
            try {
                $url = route('products.store', $store);
            } catch (\Exception) {
                return;
            }

            $this->addUrl($xml, $url, $store->updated_at?->toDateString(), 'weekly', '0.5');
        });

        $path = public_path('sitemap.xml');
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        file_put_contents($path, $dom->saveXML());

        $this->info("Sitemap generated at {$path}");

        return self::SUCCESS;
    }

    protected function addUrl(SimpleXMLElement $xml, string $loc, ?string $lastmod, string $changefreq, string $priority): void
    {
        $url = $xml->addChild('url');
        $url->addChild('loc', htmlspecialchars($loc));

        if ($lastmod) {
            $url->addChild('lastmod', $lastmod);
        }

        $url->addChild('changefreq', $changefreq);
        $url->addChild('priority', $priority);
    }
}
