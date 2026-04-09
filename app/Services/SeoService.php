<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\ProductCategory;
use App\Models\ProductStore;
use App\Models\SeoSetting;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use Artesaos\SEOTools\Facades\JsonLd;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;
use Illuminate\Support\Facades\Storage;

class SeoService
{
    /**
     * Cached site-wide SEO defaults from the key-value Setting store.
     *
     * @var array{meta_keywords: ?string, meta_description: ?string, og_title: ?string, og_description: ?string, og_image: ?string}|null
     */
    protected ?array $siteDefaults = null;

    public function __construct(
        protected ?OgImageService $ogImageService = null
    ) {
        $this->ogImageService = $ogImageService ?? app(OgImageService::class);
    }

    /**
     * Load site-wide defaults from settings/seo + settings/opengraph tabs.
     *
     * @return array{meta_keywords: ?string, meta_description: ?string, og_title: ?string, og_description: ?string, og_image: ?string}
     */
    protected function siteDefaults(): array
    {
        if ($this->siteDefaults !== null) {
            return $this->siteDefaults;
        }

        $ogImagePath = Setting::get('seo.og_image', '') ?: null;

        return $this->siteDefaults = [
            'meta_keywords' => Setting::get('seo.meta_keywords', '') ?: null,
            'meta_description' => Setting::get('seo.meta_description', '') ?: null,
            'og_title' => Setting::get('seo.og_title', '') ?: null,
            'og_description' => Setting::get('seo.og_description', '') ?: null,
            'og_image' => $ogImagePath ? Storage::disk('public')->url($ogImagePath) : null,
        ];
    }

    /**
     * Set SEO for the homepage.
     */
    public function setHomepage(?string $customTitle = null, ?string $customDescription = null): void
    {
        $seoSetting = SeoSetting::findByRoute('homepage');
        $defaults = $this->siteDefaults();

        $title = $customTitle
            ?? $seoSetting?->meta_title
            ?? $defaults['og_title']
            ?? config('app.name');

        $description = $customDescription
            ?? $seoSetting?->meta_description
            ?? $defaults['meta_description']
            ?? $defaults['og_description']
            ?? config('seotools.meta.defaults.description');

        SEOMeta::setTitle($title, false);
        SEOMeta::setDescription($description);

        $keywords = $seoSetting?->meta_keywords ?: $defaults['meta_keywords'];
        if ($keywords) {
            SEOMeta::setKeywords($keywords);
        }

        if ($seoSetting?->robots) {
            SEOMeta::setRobots($seoSetting->robots);
        }

        OpenGraph::setTitle($seoSetting?->og_title ?? $defaults['og_title'] ?? $title);
        OpenGraph::setDescription($seoSetting?->og_description ?? $defaults['og_description'] ?? $description);
        OpenGraph::setType($seoSetting?->og_type ?? 'website');

        $ogImage = $seoSetting?->og_image
            ?? $defaults['og_image']
            ?? $this->ogImageService->getDefaultUrl();

        if ($ogImage) {
            OpenGraph::addImage($ogImage);
        }

        TwitterCard::setTitle($seoSetting?->twitter_title ?? $defaults['og_title'] ?? $title);
        TwitterCard::setDescription($seoSetting?->twitter_description ?? $defaults['og_description'] ?? $description);

        $twitterImage = $seoSetting?->twitter_image ?? $ogImage;
        if ($twitterImage) {
            TwitterCard::setImage($twitterImage);
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
        $seoSetting = SeoSetting::findByRoute('post.show');

        $title = $post->meta_title ?: ($seoSetting?->meta_title ?: $post->title);
        $description = $post->meta_description
            ?: ($seoSetting?->meta_description
                ?: ($post->excerpt ?: \Illuminate\Support\Str::limit($this->extractTextFromContent($post->content), 160)));
        $url = $this->safeRoute('post.show', ['category' => $post->categories->first()?->slug ?? 'uncategorized', 'post' => $post->slug]);

        // Try to get generated OG image, fallback to featured image, then route setting, then site default, then default
        $ogImage = $this->ogImageService->getUrlForPost($post);
        $image = $ogImage
            ?? $post->featured_image_url
            ?? $seoSetting?->og_image
            ?? $this->siteDefaults()['og_image']
            ?? $this->ogImageService->getDefaultUrl();

        $this->applySettingExtras($seoSetting);

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

        // BreadcrumbList JSON-LD
        $category = $post->categories->first();
        $breadcrumbItems = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => url('/'),
            ],
        ];

        if ($category) {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $category->name,
                'item' => $this->safeRoute('category.show', $category),
            ];
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $post->title,
                'item' => $url,
            ];
        } else {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $post->title,
                'item' => $url,
            ];
        }

        view()->share('breadcrumbJsonLd', [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ]);
    }

    /**
     * Set SEO for a category page.
     */
    public function setCategory(Category $category): void
    {
        $seoSetting = SeoSetting::findByRoute('category.show');

        $title = $seoSetting?->meta_title
            ? str_replace(':name', $category->name, $seoSetting->meta_title)
            : $category->name;
        $description = $seoSetting?->meta_description
            ? str_replace(':name', $category->name, $seoSetting->meta_description)
            : ($category->description ?: "Browse all {$category->name} articles and content.");
        $url = $this->safeRoute('category.show', $category);
        $image = $this->ogImageService->getUrlForCategory($category)
            ?: $seoSetting?->og_image
            ?: $this->siteDefaults()['og_image'];

        $this->applySettingExtras($seoSetting);

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType('website');
        OpenGraph::setUrl($url);

        if ($image) {
            OpenGraph::addImage($image);
        }

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);

        if ($image) {
            TwitterCard::setImage($image);
        }

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
        $seoSetting = SeoSetting::findByRoute('tag.show');

        $title = $seoSetting?->meta_title
            ? str_replace(':name', $tag->name, $seoSetting->meta_title)
            : $tag->name;
        $description = $seoSetting?->meta_description
            ? str_replace(':name', $tag->name, $seoSetting->meta_description)
            : "Browse all content tagged with {$tag->name}.";
        $url = $this->safeRoute('tag.show', $tag);
        $image = $seoSetting?->og_image
            ?: $this->siteDefaults()['og_image']
            ?: $this->ogImageService->getDefaultUrl();

        $this->applySettingExtras($seoSetting);

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType('website');
        OpenGraph::setUrl($url);

        if ($image) {
            OpenGraph::addImage($image);
        }

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);

        if ($image) {
            TwitterCard::setImage($image);
        }

        JsonLd::setTitle($title);
        JsonLd::setDescription($description);
        JsonLd::setType('CollectionPage');
        JsonLd::setUrl($url);
    }

    /**
     * Set SEO for an author page.
     */
    public function setAuthor(User $author): void
    {
        $seoSetting = SeoSetting::findByRoute('author.show');

        $title = $seoSetting?->meta_title
            ? str_replace(':name', $author->name, $seoSetting->meta_title)
            : $author->name;
        $description = $seoSetting?->meta_description
            ? str_replace(':name', $author->name, $seoSetting->meta_description)
            : "Articles and content by {$author->name}.";
        $url = $this->safeRoute('author.show', $author->username);
        $image = $author->avatar_url
            ?: $seoSetting?->og_image
            ?: $this->siteDefaults()['og_image']
            ?: $this->ogImageService->getDefaultUrl();

        $this->applySettingExtras($seoSetting);

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType('profile');
        OpenGraph::setUrl($url);

        if ($image) {
            OpenGraph::addImage($image);
        }

        OpenGraph::addProperty('profile:username', $author->username);

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);

        if ($image) {
            TwitterCard::setImage($image);
        }

        JsonLd::setTitle($title);
        JsonLd::setDescription($description);
        JsonLd::setType('ProfilePage');
        JsonLd::setUrl($url);

        $jsonLdValues = [
            'mainEntity' => [
                '@type' => 'Person',
                'name' => $author->name,
            ],
        ];

        if ($image) {
            $jsonLdValues['mainEntity']['image'] = $image;
        }

        JsonLd::addValues($jsonLdValues);
    }

    /**
     * Set SEO for a static page.
     */
    public function setPage(Page $page): void
    {
        $seoSetting = SeoSetting::findByRoute('page.show');

        $title = $page->meta_title ?: ($seoSetting?->meta_title ?: $page->title);
        $description = $page->meta_description
            ?: ($seoSetting?->meta_description
                ?: \Illuminate\Support\Str::limit($this->extractTextFromContent($page->content), 160));
        $url = $this->safeRoute('page.show', $page);
        $image = $seoSetting?->og_image
            ?: $this->siteDefaults()['og_image']
            ?: $this->ogImageService->getDefaultUrl();

        $this->applySettingExtras($seoSetting);

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType('article');
        OpenGraph::setUrl($url);

        if ($image) {
            OpenGraph::addImage($image);
        }

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);

        if ($image) {
            TwitterCard::setImage($image);
        }

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

        $ogImage = $seoSetting->og_image ?? $this->ogImageService->getDefaultUrl();
        if ($ogImage) {
            OpenGraph::addImage($ogImage);
        }

        TwitterCard::setTitle($seoSetting->twitter_title ?? $seoSetting->meta_title);
        TwitterCard::setDescription($seoSetting->twitter_description ?? $seoSetting->meta_description);

        if ($seoSetting->twitter_card) {
            TwitterCard::setType($seoSetting->twitter_card);
        }

        $twitterImage = $seoSetting->twitter_image ?? $ogImage;
        if ($twitterImage) {
            TwitterCard::setImage($twitterImage);
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
        $seoSetting = SeoSetting::findByRoute('products.index');

        $title = $seoSetting?->meta_title ?: 'Products';
        $description = $seoSetting?->meta_description ?: 'Discover ingredients, tools, and staples we actually use and recommend.';
        $url = $this->safeRoute('products.index');
        $image = $seoSetting?->og_image
            ?: $this->siteDefaults()['og_image']
            ?: $this->ogImageService->getDefaultUrl();

        $this->applySettingExtras($seoSetting);

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType('website');
        OpenGraph::setUrl($url);

        if ($image) {
            OpenGraph::addImage($image);
        }

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);

        if ($image) {
            TwitterCard::setImage($image);
        }

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
        $seoSetting = SeoSetting::findByRoute('products.category');

        $title = $seoSetting?->meta_title
            ? str_replace(':name', $category->name, $seoSetting->meta_title)
            : $category->name.' Products';
        $description = $seoSetting?->meta_description
            ? str_replace(':name', $category->name, $seoSetting->meta_description)
            : ($category->description ?: "Browse all {$category->name} products we recommend.");
        $url = $this->safeRoute('products.category', $category);
        $image = $seoSetting?->og_image
            ?: $this->siteDefaults()['og_image']
            ?: $this->ogImageService->getDefaultUrl();

        $this->applySettingExtras($seoSetting);

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType('website');
        OpenGraph::setUrl($url);

        if ($image) {
            OpenGraph::addImage($image);
        }

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);

        if ($image) {
            TwitterCard::setImage($image);
        }

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
        $seoSetting = SeoSetting::findByRoute('products.store');

        $title = $seoSetting?->meta_title
            ? str_replace(':name', $store->name, $seoSetting->meta_title)
            : $store->name.' Products';
        $description = $seoSetting?->meta_description
            ? str_replace(':name', $store->name, $seoSetting->meta_description)
            : "Browse all products from {$store->name}.";
        $url = $this->safeRoute('products.store', $store);

        $this->applySettingExtras($seoSetting);

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType('website');
        OpenGraph::setUrl($url);

        $image = $store->logo_url
            ?: $seoSetting?->og_image
            ?: $this->siteDefaults()['og_image']
            ?: $this->ogImageService->getDefaultUrl();
        if ($image) {
            OpenGraph::addImage($image);
        }

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);

        if ($image) {
            TwitterCard::setImage($image);
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

    /**
     * Apply non title/description fields from a SeoSetting (keywords, robots, twitter card, json-ld).
     *
     * Title, description, url, and og_image are resolved per-method so they can blend
     * entity data with placeholders. This helper only layers on the fields that don't
     * need per-entity logic.
     */
    protected function applySettingExtras(?SeoSetting $seoSetting): void
    {
        $keywords = $seoSetting?->meta_keywords ?: $this->siteDefaults()['meta_keywords'];
        if ($keywords) {
            SEOMeta::setKeywords($keywords);
        }

        if ($seoSetting?->robots) {
            SEOMeta::setRobots($seoSetting->robots);
        }

        if ($seoSetting?->twitter_card) {
            TwitterCard::setType($seoSetting->twitter_card);
        }

        if ($seoSetting?->json_ld) {
            JsonLd::addValues($seoSetting->json_ld);
        }
    }

    /**
     * Safely generate a route URL, returning '#' if route doesn't exist (CMS_ONLY mode).
     */
    protected function safeRoute(string $name, mixed $parameters = []): string
    {
        try {
            return route($name, $parameters);
        } catch (\Symfony\Component\Routing\Exception\RouteNotFoundException) {
            return '#';
        }
    }
}
