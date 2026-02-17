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

    Role::firstOrCreate(['name' => 'Writer', 'guard_name' => 'web']);
});

test('unauthenticated users cannot access author analytics', function () {
    $this->get('/cms/analytics/authors')
        ->assertStatus(302);
});

test('users without permission get 403', function () {
    $writer = User::factory()->create();
    $writer->assignRole('Writer');

    $this->actingAs($writer)
        ->get('/cms/analytics/authors')
        ->assertForbidden();
});

test('admin can access author analytics', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get('/cms/analytics/authors')
        ->assertSuccessful();
});

test('author analytics returns correct data structure', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $response = $this->actingAs($admin)
        ->get('/cms/analytics/authors');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Analytics/Authors')
        ->has('analytics.leaderboard')
        ->has('analytics.publishing_trend')
        ->has('period')
    );
});

test('author leaderboard includes published counts and views', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $author = User::factory()->create();
    $posts = Post::factory()->count(3)->published()->create([
        'author_id' => $author->id,
        'published_at' => now()->subDays(5),
    ]);

    // Add views to the first post
    PostView::factory()->count(10)->forPost($posts[0])->create([
        'viewed_at' => now()->subDays(2),
    ]);

    $response = $this->actingAs($admin)
        ->get('/cms/analytics/authors?period=30d');

    $response->assertInertia(fn ($page) => $page
        ->has('analytics.leaderboard', 1)
        ->where('analytics.leaderboard.0.user.id', $author->id)
        ->where('analytics.leaderboard.0.published_count', 3)
        ->where('analytics.leaderboard.0.total_views', 10)
    );
});

test('author analytics respects period parameter', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get('/cms/analytics/authors?period=7d')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('period', '7d'));
});
