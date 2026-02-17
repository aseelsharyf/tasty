<?php

use App\Models\Language;
use App\Models\Post;
use App\Models\PostView;
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

    $writerRole = Role::firstOrCreate(['name' => 'Writer', 'guard_name' => 'web']);
});

function createAnalyticsAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

function createWriter(): User
{
    $user = User::factory()->create();
    $user->assignRole('Writer');

    return $user;
}

test('unauthenticated users cannot access article analytics', function () {
    $this->get('/cms/analytics/articles')
        ->assertStatus(302);
});

test('users without analytics.view permission get 403', function () {
    $writer = createWriter();

    $this->actingAs($writer)
        ->get('/cms/analytics/articles')
        ->assertForbidden();
});

test('admin can access article analytics', function () {
    $admin = createAnalyticsAdmin();

    $this->actingAs($admin)
        ->get('/cms/analytics/articles')
        ->assertSuccessful();
});

test('article analytics returns correct data structure', function () {
    $admin = createAnalyticsAdmin();

    $response = $this->actingAs($admin)
        ->get('/cms/analytics/articles');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Analytics/Articles')
        ->has('analytics.summary')
        ->has('analytics.top_articles')
        ->has('analytics.views_over_time')
        ->has('analytics.views_by_type')
        ->has('analytics.views_by_category')
        ->has('period')
    );
});

test('article analytics respects period parameter', function () {
    $admin = createAnalyticsAdmin();

    $this->actingAs($admin)
        ->get('/cms/analytics/articles?period=7d')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('period', '7d')
        );

    $this->actingAs($admin)
        ->get('/cms/analytics/articles?period=90d')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('period', '90d')
        );
});

test('article analytics summary includes correct counts', function () {
    $admin = createAnalyticsAdmin();

    $post = Post::factory()->published()->create();
    PostView::factory()->count(3)->forPost($post)->create([
        'viewed_at' => now(),
    ]);
    PostView::factory()->count(2)->forPost($post)->create([
        'viewed_at' => now()->subDays(5),
    ]);

    $response = $this->actingAs($admin)
        ->get('/cms/analytics/articles');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('analytics.summary.today', 3)
        ->where('analytics.summary.total', 5)
    );
});

test('top articles are ordered by view count', function () {
    $admin = createAnalyticsAdmin();

    $post1 = Post::factory()->published()->create();
    $post2 = Post::factory()->published()->create();

    PostView::factory()->count(5)->forPost($post1)->create([
        'viewed_at' => now()->subDays(2),
    ]);
    PostView::factory()->count(10)->forPost($post2)->create([
        'viewed_at' => now()->subDays(2),
    ]);

    $response = $this->actingAs($admin)
        ->get('/cms/analytics/articles?period=30d');

    $response->assertInertia(fn ($page) => $page
        ->has('analytics.top_articles', 2)
        ->where('analytics.top_articles.0.id', $post2->id)
        ->where('analytics.top_articles.0.views', 10)
        ->where('analytics.top_articles.1.id', $post1->id)
        ->where('analytics.top_articles.1.views', 5)
    );
});
