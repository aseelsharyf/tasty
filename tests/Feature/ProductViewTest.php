<?php

use App\Jobs\RecordViewJob;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('product recordView endpoint dispatches RecordViewJob', function () {
    Queue::fake();

    $category = ProductCategory::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'product_category_id' => $category->id,
        'is_active' => true,
    ]);

    $this->postJson(route('products.view', $product))
        ->assertSuccessful()
        ->assertJson(['status' => 'ok']);

    Queue::assertPushed(RecordViewJob::class, function ($job) use ($product) {
        return $job->data['type'] === 'product' && $job->data['model_id'] === $product->id;
    });
});

test('bot requests to product recordView are skipped', function () {
    Queue::fake();

    $category = ProductCategory::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'product_category_id' => $category->id,
        'is_active' => true,
    ]);

    $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])
        ->postJson(route('products.view', $product))
        ->assertSuccessful()
        ->assertJson(['status' => 'skipped']);

    Queue::assertNotPushed(RecordViewJob::class);
});

test('ProductView session deduplication prevents duplicate within 30 minutes', function () {
    $category = ProductCategory::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'product_category_id' => $category->id,
        'is_active' => true,
    ]);
    $sessionId = 'test-session-product';

    $result1 = ProductView::record([
        'product_id' => $product->id,
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'referrer' => null,
        'session_id' => $sessionId,
    ]);

    $result2 = ProductView::record([
        'product_id' => $product->id,
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'referrer' => null,
        'session_id' => $sessionId,
    ]);

    expect($result1)->toBeInstanceOf(ProductView::class);
    expect($result2)->toBeNull();
    expect(ProductView::count())->toBe(1);
});

test('RecordViewJob creates ProductView record', function () {
    $category = ProductCategory::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'product_category_id' => $category->id,
        'is_active' => true,
    ]);

    $job = new RecordViewJob([
        'type' => 'product',
        'model_id' => $product->id,
        'user_id' => null,
        'ip_address' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0',
        'referrer' => null,
        'session_id' => 'product-job-session',
    ]);

    $job->handle();

    expect(ProductView::count())->toBe(1);
    $view = ProductView::first();
    expect($view->product_id)->toBe($product->id);
});
