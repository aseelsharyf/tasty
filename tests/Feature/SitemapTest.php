<?php

use App\Models\Category;
use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStore;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Language::create([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'direction' => 'ltr',
        'is_default' => true,
        'is_active' => true,
        'order' => 1,
    ]);
});

it('lists canonical public URLs and excludes unpublished or non-indexable content', function () {
    $category = Category::factory()->create(['slug' => 'features']);
    $emptyCategory = Category::factory()->create(['slug' => 'empty-category']);
    $tag = Tag::factory()->create(['slug' => 'island-food']);
    $draftTag = Tag::factory()->create(['slug' => 'draft-only']);

    $post = Post::factory()->published()->create(['slug' => 'published-story']);
    $post->categories()->attach($category);
    $post->tags()->attach($tag);

    $draft = Post::factory()->draft()->create(['slug' => 'draft-story']);
    $draft->categories()->attach($emptyCategory);
    $draft->tags()->attach($draftTag);

    $page = Page::factory()->published()->create([
        'slug' => 'editorial-policy',
        'author_id' => null,
    ]);
    Page::factory()->draft()->create([
        'slug' => 'private-draft',
        'author_id' => null,
    ]);

    $productCategory = ProductCategory::factory()->create(['slug' => 'cookware']);
    $store = ProductStore::factory()->create(['slug' => 'tasty-shop']);
    $product = Product::factory()->inHouse()->forCategory($productCategory)->forStore($store)->create([
        'slug' => 'cast-iron-pan',
    ]);
    $referral = Product::factory()->referral()->forCategory($productCategory)->forStore($store)->create([
        'slug' => 'external-product',
    ]);
    $inactive = Product::factory()->inHouse()->inactive()->forCategory($productCategory)->forStore($store)->create([
        'slug' => 'inactive-product',
    ]);

    $response = $this->get('/sitemap.xml')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $document = new DOMDocument;
    expect($document->loadXML($response->getContent()))->toBeTrue();

    $xpath = new DOMXPath($document);
    $xpath->registerNamespace('sitemap', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $locations = collect($xpath->query('//sitemap:loc'))
        ->map(fn (DOMNode $node): string => $node->textContent);

    expect($locations)
        ->toContain(url('/'))
        ->toContain(route('post.show', ['category' => $category->slug, 'post' => $post->slug]))
        ->toContain(route('category.show', ['category' => $category->slug]))
        ->toContain(route('tag.show', ['tag' => $tag->slug]))
        ->toContain(route('author.show', ['author' => $post->author->username]))
        ->toContain(route('page.show', ['slug' => $page->slug]))
        ->toContain(route('products.index'))
        ->toContain(route('products.category', ['category' => $productCategory->slug]))
        ->toContain(route('products.store', ['store' => $store->slug]))
        ->toContain(route('products.show', ['store' => $store->slug, 'product' => $product->slug]))
        ->not->toContain(route('post.show', ['category' => $emptyCategory->slug, 'post' => $draft->slug]))
        ->not->toContain(route('category.show', ['category' => $emptyCategory->slug]))
        ->not->toContain(route('tag.show', ['tag' => $draftTag->slug]))
        ->not->toContain(route('page.show', ['slug' => 'private-draft']))
        ->not->toContain(route('products.show', ['store' => $store->slug, 'product' => $referral->slug]))
        ->not->toContain(route('products.show', ['store' => $store->slug, 'product' => $inactive->slug]));
});

it('advertises the sitemap and keeps private areas out of robots crawling', function () {
    $response = $this->get('/robots.txt')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8');

    $response->assertSee('Sitemap: '.url('/sitemap.xml'), false)
        ->assertSee('Disallow: /cms/', false)
        ->assertSee('Disallow: /api/', false)
        ->assertDontSee('Disallow: /og-preview/', false)
        ->assertDontSee('Disallow: /og-html/', false)
        ->assertDontSee('Disallow: /og-test', false);
});

it('does not expose OG image debugging routes', function () {
    $this->get('/og-preview/example-post')->assertNotFound();
    $this->get('/og-html/example-post')->assertNotFound();
    $this->get('/og-test')->assertNotFound();
});
