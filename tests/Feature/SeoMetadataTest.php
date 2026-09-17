<?php

use App\Models\Category;
use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductStore;
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

it('uses public slugs in canonical URLs', function () {
    $page = Page::factory()->published()->create([
        'slug' => 'privacy-policy',
        'author_id' => null,
    ]);
    $category = Category::factory()->create(['slug' => 'food-stories']);
    $post = Post::factory()->published()->create(['slug' => 'market-guide']);
    $post->categories()->attach($category);

    $this->get(route('page.show', ['slug' => $page->slug]))
        ->assertSuccessful()
        ->assertSee('<link rel="canonical" href="'.route('page.show', ['slug' => $page->slug]).'"', false);

    $postUrl = route('post.show', ['category' => $category->slug, 'post' => $post->slug]);

    $this->get($postUrl)
        ->assertSuccessful()
        ->assertSee('<link rel="canonical" href="'.$postUrl.'"', false);
});

it('gives paginated archives their own canonical URL', function () {
    $category = Category::factory()->create(['slug' => 'recipes']);
    $url = route('category.show', ['category' => $category->slug, 'page' => 2]);

    $this->get($url)
        ->assertSuccessful()
        ->assertSee('<link rel="canonical" href="'.$url.'"', false);
});

it('marks search results as noindex', function () {
    $this->get(route('search', ['q' => 'fish']))
        ->assertSuccessful()
        ->assertSee('<meta name="robots" content="noindex,follow">', false);
});

it('sets product canonical and structured metadata and rejects a mismatched store', function () {
    $store = ProductStore::factory()->create(['slug' => 'kitchen-shop']);
    $otherStore = ProductStore::factory()->create(['slug' => 'other-shop']);
    $product = Product::factory()->inHouse()->forStore($store)->create([
        'slug' => 'chef-knife',
        'title' => ['en' => 'Chef Knife'],
        'brand' => 'Tasty Tools',
    ]);
    $url = route('products.show', ['store' => $store->slug, 'product' => $product->slug]);

    $response = $this->get($url)->assertSuccessful();

    $response->assertSee('<link rel="canonical" href="'.$url.'"', false)
        ->assertSee('property="og:type" content="product"', false)
        ->assertSee('"@type":"Product"', false);

    $this->get(route('products.show', [
        'store' => $otherStore->slug,
        'product' => $product->slug,
    ]))->assertNotFound();
});
