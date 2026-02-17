<?php

use App\Models\Language;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductClick;
use App\Models\ProductStore;
use App\Models\ProductView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Language::firstOrCreate(['code' => 'en'], [
        'name' => 'English',
        'native_name' => 'English',
        'direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
        'order' => 1,
    ]);

    Permission::firstOrCreate(['name' => 'analytics.view', 'guard_name' => 'web']);
    $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    $adminRole->syncPermissions(['analytics.view']);

    Role::firstOrCreate(['name' => 'Writer', 'guard_name' => 'web']);
});

test('unauthenticated users cannot access product analytics', function () {
    $this->get('/cms/analytics/products')
        ->assertStatus(302);
});

test('users without permission get 403', function () {
    $writer = User::factory()->create();
    $writer->assignRole('Writer');

    $this->actingAs($writer)
        ->get('/cms/analytics/products')
        ->assertForbidden();
});

test('admin can access product analytics', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get('/cms/analytics/products')
        ->assertSuccessful();
});

test('product analytics returns correct data structure', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $response = $this->actingAs($admin)
        ->get('/cms/analytics/products');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Analytics/Products')
        ->has('analytics.summary')
        ->has('analytics.top_by_views')
        ->has('analytics.top_by_clicks')
        ->has('analytics.over_time')
        ->has('analytics.by_store')
        ->has('analytics.by_category')
        ->has('period')
    );
});

test('product analytics summary calculates CTR correctly', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $category = ProductCategory::factory()->create(['is_active' => true]);
    $store = ProductStore::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'product_category_id' => $category->id,
        'product_store_id' => $store->id,
        'is_active' => true,
    ]);

    // 20 views, 5 clicks = 25% CTR
    ProductView::factory()->count(20)->forProduct($product)->create([
        'viewed_at' => now()->subDays(2),
    ]);
    for ($i = 0; $i < 5; $i++) {
        ProductClick::create([
            'product_id' => $product->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'session_id' => "click-session-$i",
            'clicked_at' => now()->subDays(2),
        ]);
    }

    $response = $this->actingAs($admin)
        ->get('/cms/analytics/products?period=30d');

    $response->assertInertia(fn ($page) => $page
        ->where('analytics.summary.views', 20)
        ->where('analytics.summary.clicks', 5)
        ->where('analytics.summary.ctr', 25.0)
    );
});

test('product analytics respects period parameter', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get('/cms/analytics/products?period=7d')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('period', '7d'));
});

test('top products by views returns products ordered by view count', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $category = ProductCategory::factory()->create(['is_active' => true]);
    $product1 = Product::factory()->create([
        'product_category_id' => $category->id,
        'is_active' => true,
    ]);
    $product2 = Product::factory()->create([
        'product_category_id' => $category->id,
        'is_active' => true,
    ]);

    ProductView::factory()->count(5)->forProduct($product1)->create([
        'viewed_at' => now()->subDays(2),
    ]);
    ProductView::factory()->count(15)->forProduct($product2)->create([
        'viewed_at' => now()->subDays(2),
    ]);

    $response = $this->actingAs($admin)
        ->get('/cms/analytics/products?period=30d');

    $response->assertInertia(fn ($page) => $page
        ->has('analytics.top_by_views', 2)
        ->where('analytics.top_by_views.0.id', $product2->id)
        ->where('analytics.top_by_views.0.views', 15)
        ->where('analytics.top_by_views.1.id', $product1->id)
        ->where('analytics.top_by_views.1.views', 5)
    );
});
