<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\ProductCategory;
use App\Models\ProductStore;
use App\Models\SeoSetting;
use App\Models\Tag;
use Artesaos\SEOTools\Facades\JsonLd;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;

class SeoService
{
    public function __construct(
        protected ?OgImageService $ogImageService = null
    ) {
        $this->ogImageService = $ogImageService ?? app(OgImageService::class);
    }

    /**
     * Set SEO for the homepage.
     */
    public function setHomepage(?string $customTitle = null, ?string $customDescription = null): void
    {
        $seoSetting = SeoSetting::findByRoute('homepage');

        $title = $customTitle ?? $seoSetting?->meta_title ?? config('app.name');
        $description = $customDescription ?? $seoSetting?->meta_description ?? config('seotools.meta.defaults.description');

        SEOMeta::setTitle($title, false);
        SEOMeta::setDescription($description);

        if ($seoSetting?->meta_keywords) {
            SEOMeta::setKeywords($seoSetting->meta_keywords);
        }

        if ($seoSetting?->robots) {
            SEOMeta::setRobots($seoSetting->robots);
        }

        OpenGraph::setTitle($seoSetting?->og_title ?? $title);
        OpenGraph::setDescription($seoSetting?->og_description ?? $description);
        OpenGraph::setType($seoSetting?->og_type ?? 'website');

        if ($seoSetting?->og_image) {
            OpenGraph::addImage($seoSetting->og_image);
        }

        TwitterCard::setTitle($seoSetting?->twitter_title ?? $title);
        TwitterCard::setDescription($seoSetting?->twitter_description ?? $description);

        if ($seoSetting?->twitter_image) {
            TwitterCard::setImage($seoSetting->twitter_image);
        }

        JsonLd::setTitle($title);
        JsonLd::setDescription($description);
        JsonLd::setType('WebSite');
    }

    /**
     * Set SEO for a post/article page.
     */
    public function setPost(Post $post): void
    {
        $title = $post->meta_title ?: $post->title;
        $description = $post->meta_description ?: $post->excerpt ?: \Illuminate\Support\Str::limit($this->extractTextFromContent($post->content), 160);
        $url = route('post.show', ['category' => $post->categories->first()?->slug ?? 'uncategorized', 'post' => $post->slug]);

        // Try to get generated OG image, fallback to featured image
        $ogImage = $this->ogImageService->getUrlForPost($post);
        $image = $ogImage ?? $post->featured_image_url;

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        // Posts don't have keywords field currently

        // Add article-specific meta
        SEOMeta::addMeta('article:published_time', $post->published_at?->toIso8601String(), 'property');
        if ($post->updated_at) {
            SEOMeta::addMeta('article:modified_time', $post->updated_at->toIso8601String(), 'property');
        }

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType('article');
        OpenGraph::setUrl($url);

        if ($image) {
            OpenGraph::addImage($image);
        }

        // Add author
        if ($post->author) {
            OpenGraph::addProperty('article:author', $post->author->name);
        }

        // Add categories as article sections
        foreach ($post->categories as $category) {
            OpenGraph::addProperty('article:section', $category->name);
        }

        // Add tags
        foreach ($post->tags as $tag) {
            OpenGraph::addProperty('article:tag', $tag->name);
        }

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);

        if ($image) {
            TwitterCard::setImage($image);
        }

        // JSON-LD Article schema
        JsonLd::setTitle($title);
        JsonLd::setDescription($description);
        JsonLd::setType('Article');
        JsonLd::setUrl($url);

        if ($image) {
            JsonLd::addImage($image);
        }

        $jsonLdValues = [
            'headline' => $title,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
        ];

        if ($post->author) {
            $jsonLdValues['author'] = [
                '@type' => 'Person',
                'name' => $post->author->name,
            ];
        }

        JsonLd::addValues($jsonLdValues);
    }

    /**
     * Set SEO for a category page.
     */
    public function setCategory(Category $category): void
    {
        $title = $category->name;
        $description = $category->description ?: "Browse all {$category->name} articles and content.";
        $url = route('category.show', $category);

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType('website');
        OpenGraph::setUrl($url);

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);

        JsonLd::setTitle($title);
        JsonLd::setDescription($description);
        JsonLd::setType('CollectionPage');
        JsonLd::setUrl($url);
    }

    /**
     * Set SEO for a tag page.
     */
    public function setTag(Tag $tag): void
    {
        $title = $tag->name;
        $description = "Browse all content tagged with {$tag->name}.";
        $url = route('tag.show', $tag);

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType('website');
        OpenGraph::setUrl($url);

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);

        JsonLd::setTitle($title);
        JsonLd::setDescription($description);
        JsonLd::setType('CollectionPage');
        JsonLd::setUrl($url);
    }

    /**
     * Set SEO for a static page.
     */
    public function setPage(Page $page): void
    {
        $title = $page->meta_title ?: $page->title;
        $description = $page->meta_description ?: \Illuminate\Support\Str::limit($this->extractTextFromContent($page->content), 160);
        $url = route('page.show', $page);

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType('article');
        OpenGraph::setUrl($url);

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);

        JsonLd::setTitle($title);
        JsonLd::setDescription($description);
        JsonLd::setType('WebPage');
        JsonLd::setUrl($url);
    }

    /**
     * Set SEO for a named route from database settings.
     */
    public function setFromRoute(string $routeName): void
    {
        $seoSetting = SeoSetting::findByRoute($routeName);

        if (! $seoSetting) {
            return;
        }

        if ($seoSetting->meta_title) {
            SEOMeta::setTitle($seoSetting->meta_title);
        }

        if ($seoSetting->meta_description) {
            SEOMeta::setDescription($seoSetting->meta_description);
        }

        if ($seoSetting->meta_keywords) {
            SEOMeta::setKeywords($seoSetting->meta_keywords);
        }

        if ($seoSetting->canonical_url) {
            SEOMeta::setCanonical($seoSetting->canonical_url);
        }

        if ($seoSetting->robots) {
            SEOMeta::setRobots($seoSetting->robots);
        }

        OpenGraph::setTitle($seoSetting->og_title ?? $seoSetting->meta_title);
        OpenGraph::setDescription($seoSetting->og_description ?? $seoSetting->meta_description);
        OpenGraph::setType($seoSetting->og_type ?? 'website');

        if ($seoSetting->og_image) {
            OpenGraph::addImage($seoSetting->og_image);
        }

        TwitterCard::setTitle($seoSetting->twitter_title ?? $seoSetting->meta_title);
        TwitterCard::setDescription($seoSetting->twitter_description ?? $seoSetting->meta_description);

        if ($seoSetting->twitter_card) {
            TwitterCard::setType($seoSetting->twitter_card);
        }

        if ($seoSetting->twitter_image) {
            TwitterCard::setImage($seoSetting->twitter_image);
        }

        JsonLd::setTitle($seoSetting->meta_title);
        JsonLd::setDescription($seoSetting->meta_description);

        if ($seoSetting->json_ld) {
            JsonLd::addValues($seoSetting->json_ld);
        }
    }

    /**
     * Set basic SEO with title and optional description.
     */
    public function setBasic(string $title, ?string $description = null, ?string $image = null): void
    {
        SEOMeta::setTitle($title);

        if ($description) {
            SEOMeta::setDescription($description);
        }

        OpenGraph::setTitle($title);

        if ($description) {
            OpenGraph::setDescription($description);
        }

        if ($image) {
            OpenGraph::addImage($image);
        }

        TwitterCard::setTitle($title);

        if ($description) {
            TwitterCard::setDescription($description);
        }

        if ($image) {
            TwitterCard::setImage($image);
        }

        JsonLd::setTitle($title);

        if ($description) {
            JsonLd::setDescription($description);
        }
    }

    /**
     * Add pagination information to SEO.
     */
    public function setPagination(int $currentPage, int $lastPage, string $baseUrl): void
    {
        if ($currentPage > 1) {
            $prevUrl = $baseUrl.'?page='.($currentPage - 1);
            SEOMeta::addMeta('prev', $prevUrl, 'rel');
        }

        if ($currentPage < $lastPage) {
            $nextUrl = $baseUrl.'?page='.($currentPage + 1);
            SEOMeta::addMeta('next', $nextUrl, 'rel');
        }
    }

    /**
     * Set SEO for the products index page.
     */
    public function setProductsIndex(): void
    {
        $title = 'Products';
        $description = 'Discover ingredients, tools, and staples we actually use and recommend.';
        $url = route('products.index');

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType('website');
        OpenGraph::setUrl($url);

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);

        JsonLd::setTitle($title);
        JsonLd::setDescription($description);
        JsonLd::setType('CollectionPage');
        JsonLd::setUrl($url);
    }

    /**
     * Set SEO for a product category page.
     */
    public function setProductCategory(ProductCategory $category): void
    {
        $title = $category->name.' Products';
        $description = $category->description ?: "Browse all {$category->name} products we recommend.";
        $url = route('products.category', $category);

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType('website');
        OpenGraph::setUrl($url);

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);

        JsonLd::setTitle($title);
        JsonLd::setDescription($description);
        JsonLd::setType('CollectionPage');
        JsonLd::setUrl($url);
    }

    /**
     * Set SEO for a product store page.
     */
    public function setProductStore(ProductStore $store): void
    {
        $title = $store->name.' Products';
        $description = "Browse all products from {$store->name}.";
        $url = route('products.store', $store);

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType('website');
        OpenGraph::setUrl($url);

        if ($store->logo_url) {
            OpenGraph::addImage($store->logo_url);
        }

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);

        if ($store->logo_url) {
            TwitterCard::setImage($store->logo_url);
        }

        JsonLd::setTitle($title);
        JsonLd::setDescription($description);
        JsonLd::setType('CollectionPage');
        JsonLd::setUrl($url);
    }

    /**
     * Extract plain text from content (handles both string and EditorJS array format).
     */
    protected function extractTextFromContent(mixed $content): string
    {
        if (is_string($content)) {
            return strip_tags($content);
        }

        if (! is_array($content)) {
            return '';
        }

        $text = '';
        $blocks = $content['blocks'] ?? $content;

        foreach ($blocks as $block) {
            $data = $block['data'] ?? [];

            switch ($block['type'] ?? '') {
                case 'paragraph':
                case 'header':
                    if (! empty($data['text'])) {
                        $text .= ' '.strip_tags($data['text']);
                    }
                    break;

                case 'list':
                    foreach ($data['items'] ?? [] as $item) {
                        if (is_string($item)) {
                            $text .= ' '.strip_tags($item);
                        } elseif (is_array($item) && isset($item['content'])) {
                            $text .= ' '.strip_tags($item['content']);
                        }
                    }
                    break;

                case 'quote':
                    if (! empty($data['text'])) {
                        $text .= ' '.strip_tags($data['text']);
                    }
                    break;

                case 'collapsible':
                    if (! empty($data['title'])) {
                        $text .= ' '.strip_tags($data['title']);
                    }
                    if (! empty($data['content']['blocks'])) {
                        $text .= ' '.$this->extractTextFromContent($data['content']);
                    }
                    break;
            }
        }

        return trim($text);
    }
}
