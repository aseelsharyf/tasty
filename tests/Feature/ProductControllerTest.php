<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductClick;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('products index displays all active products', function () {
    $category = ProductCategory::factory()->create(['is_active' => true]);
    Product::factory()->count(3)->create([
        'product_category_id' => $category->id,
        'is_active' => true,
    ]);

    $response = $this->get(route('products.index'));

    $response->assertSuccessful();
    $response->assertViewHas('products');
    $response->assertViewHas('categories');
});

test('products index does not display inactive products', function () {
    $category = ProductCategory::factory()->create(['is_active' => true]);
    Product::factory()->create([
        'product_category_id' => $category->id,
        'is_active' => true,
    ]);
    Product::factory()->create([
        'product_category_id' => $category->id,
        'is_active' => false,
    ]);

    $response = $this->get(route('products.index'));

    $response->assertSuccessful();
    expect($response->viewData('products'))->toHaveCount(1);
});

test('products by category displays only products in that category', function () {
    $category1 = ProductCategory::factory()->create(['is_active' => true]);
    $category2 = ProductCategory::factory()->create(['is_active' => true]);

    Product::factory()->count(2)->create([
        'product_category_id' => $category1->id,
        'is_active' => true,
    ]);
    Product::factory()->create([
        'product_category_id' => $category2->id,
        'is_active' => true,
    ]);

    $response = $this->get(route('products.category', $category1));

    $response->assertSuccessful();
    expect($response->viewData('products'))->toHaveCount(2);
    expect($response->viewData('currentCategory')->id)->toBe($category1->id);
});

test('inactive categories return 404', function () {
    $category = ProductCategory::factory()->create(['is_active' => false]);

    $response = $this->get(route('products.category', $category));

    $response->assertNotFound();
});

test('product redirect tracks click and redirects to affiliate url', function () {
    $category = ProductCategory::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'product_category_id' => $category->id,
        'affiliate_url' => 'https://example.com/product',
        'is_active' => true,
    ]);

    $response = $this->get(route('products.redirect', ['product' => $product->slug]));

    $response->assertRedirect('https://example.com/product');
    expect(ProductClick::count())->toBe(1);
    expect(ProductClick::first()->product_id)->toBe($product->id);
});

test('product redirect for inactive product returns 404', function () {
    $category = ProductCategory::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'product_category_id' => $category->id,
        'is_active' => false,
    ]);

    $response = $this->get(route('products.redirect', ['product' => $product->slug]));

    $response->assertNotFound();
});

test('product click records user agent and ip address', function () {
    $category = ProductCategory::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'product_category_id' => $category->id,
        'affiliate_url' => 'https://example.com/product',
        'is_active' => true,
    ]);

    $this->get(route('products.redirect', ['product' => $product->slug]));

    $click = ProductClick::first();
    expect($click->ip_address)->not->toBeNull();
    expect($click->clicked_at)->not->toBeNull();
});
