<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStore;
use App\Models\Tag;
use App\Models\User;
use DOMDocument;
use DOMElement;

class SitemapService
{
    public function generate(): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $urlSet = $document->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $document->appendChild($urlSet);

        $this->addUrl($document, $urlSet, url('/'));
        $this->addPosts($document, $urlSet);
        $this->addCategories($document, $urlSet);
        $this->addTags($document, $urlSet);
        $this->addAuthors($document, $urlSet);
        $this->addPages($document, $urlSet);
        $this->addProducts($document, $urlSet);

        return $document->saveXML() ?: '';
    }

    protected function addPosts(DOMDocument $document, DOMElement $urlSet): void
    {
        Post::query()
            ->published()
            ->with('categories:id,slug')
            ->orderBy('id')
            ->chunkById(500, function ($posts) use ($document, $urlSet): void {
                foreach ($posts as $post) {
                    $this->addUrl(
                        $document,
                        $urlSet,
                        route('post.show', [
                            'category' => $post->categories->first()?->slug ?? 'uncategorized',
                            'post' => $post->slug,
                        ]),
                        $post->updated_at?->toAtomString() ?? $post->published_at?->toAtomString(),
                    );
                }
            });
    }

    protected function addCategories(DOMDocument $document, DOMElement $urlSet): void
    {
        Category::query()
            ->whereHas('posts', fn ($query) => $query->published())
            ->orderBy('id')
            ->each(function (Category $category) use ($document, $urlSet): void {
                $this->addUrl(
                    $document,
                    $urlSet,
                    route('category.show', ['category' => $category->slug]),
                    $category->updated_at?->toAtomString(),
                );
            });
    }

    protected function addTags(DOMDocument $document, DOMElement $urlSet): void
    {
        Tag::query()
            ->whereHas('posts', fn ($query) => $query->published())
            ->orderBy('id')
            ->each(function (Tag $tag) use ($document, $urlSet): void {
                $this->addUrl(
                    $document,
                    $urlSet,
                    route('tag.show', ['tag' => $tag->slug]),
                    $tag->updated_at?->toAtomString(),
                );
            });
    }

    protected function addAuthors(DOMDocument $document, DOMElement $urlSet): void
    {
        User::query()
            ->whereNotNull('username')
            ->whereHas('posts', fn ($query) => $query->published())
            ->orderBy('id')
            ->each(function (User $author) use ($document, $urlSet): void {
                $this->addUrl(
                    $document,
                    $urlSet,
                    route('author.show', ['author' => $author->username]),
                    $author->updated_at?->toAtomString(),
                );
            });
    }

    protected function addPages(DOMDocument $document, DOMElement $urlSet): void
    {
        Page::query()
            ->published()
            ->orderBy('id')
            ->each(function (Page $page) use ($document, $urlSet): void {
                $this->addUrl(
                    $document,
                    $urlSet,
                    route('page.show', ['slug' => $page->slug]),
                    $page->updated_at?->toAtomString(),
                );
            });
    }

    protected function addProducts(DOMDocument $document, DOMElement $urlSet): void
    {
        $this->addUrl($document, $urlSet, route('products.index'));

        ProductCategory::query()
            ->active()
            ->whereHas('products', fn ($query) => $query->active())
            ->orderBy('id')
            ->each(function (ProductCategory $category) use ($document, $urlSet): void {
                $this->addUrl(
                    $document,
                    $urlSet,
                    route('products.category', ['category' => $category->slug]),
                    $category->updated_at?->toAtomString(),
                );
            });

        ProductStore::query()
            ->active()
            ->whereHas('products', fn ($query) => $query->active())
            ->orderBy('id')
            ->each(function (ProductStore $store) use ($document, $urlSet): void {
                $this->addUrl(
                    $document,
                    $urlSet,
                    route('products.store', ['store' => $store->slug]),
                    $store->updated_at?->toAtomString(),
                );
            });

        Product::query()
            ->active()
            ->whereIn('product_type', [ProductType::InHouse->value, ProductType::Affiliate->value])
            ->whereHas('store', fn ($query) => $query->active())
            ->with('store:id,slug')
            ->orderBy('id')
            ->chunkById(500, function ($products) use ($document, $urlSet): void {
                foreach ($products as $product) {
                    $this->addUrl(
                        $document,
                        $urlSet,
                        route('products.show', [
                            'store' => $product->store->slug,
                            'product' => $product->slug,
                        ]),
                        $product->updated_at?->toAtomString(),
                    );
                }
            });
    }

    protected function addUrl(
        DOMDocument $document,
        DOMElement $urlSet,
        string $location,
        ?string $lastModified = null,
    ): void {
        $url = $document->createElement('url');

        $locationElement = $document->createElement('loc');
        $locationElement->appendChild($document->createTextNode($location));
        $url->appendChild($locationElement);

        if ($lastModified) {
            $lastModifiedElement = $document->createElement('lastmod');
            $lastModifiedElement->appendChild($document->createTextNode($lastModified));
            $url->appendChild($lastModifiedElement);
        }

        $urlSet->appendChild($url);
    }
}
