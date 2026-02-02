<?php

use App\Models\AdPlacement;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'settings.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'settings.edit', 'guard_name' => 'web']);

    $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    $adminRole->syncPermissions(['settings.view', 'settings.edit']);
});

function createAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

test('ad placement model generates uuid on creation', function () {
    $adPlacement = AdPlacement::factory()->create();

    expect($adPlacement->uuid)->not->toBeNull();
    expect($adPlacement->uuid)->toBeString();
});

test('ad placement belongs to category', function () {
    $category = Category::factory()->create();
    $adPlacement = AdPlacement::factory()->forCategory($category)->create();

    expect($adPlacement->category)->toBeInstanceOf(Category::class);
    expect($adPlacement->category->id)->toBe($category->id);
});

test('ad placement can be global with null category', function () {
    $adPlacement = AdPlacement::factory()->global()->create();

    expect($adPlacement->category_id)->toBeNull();
    expect($adPlacement->category)->toBeNull();
});

test('getAdForArticleSlot returns category-specific ad over global ad', function () {
    $category = Category::factory()->create();

    AdPlacement::factory()->forSlot(AdPlacement::SLOT_AFTER_HEADER)->global()->create([
        'ad_code' => '<div>Global Ad</div>',
        'priority' => 10,
    ]);

    AdPlacement::factory()->forSlot(AdPlacement::SLOT_AFTER_HEADER)->forCategory($category)->create([
        'ad_code' => '<div>Category Ad</div>',
        'priority' => 5,
    ]);

    $result = AdPlacement::getAdForArticleSlot(AdPlacement::SLOT_AFTER_HEADER, $category->id);

    expect($result)->toBe('<div>Category Ad</div>');
});

test('getAdForArticleSlot returns global ad when no category-specific ad exists', function () {
    $category = Category::factory()->create();

    AdPlacement::factory()->forSlot(AdPlacement::SLOT_AFTER_HEADER)->global()->create([
        'ad_code' => '<div>Global Ad</div>',
    ]);

    $result = AdPlacement::getAdForArticleSlot(AdPlacement::SLOT_AFTER_HEADER, $category->id);

    expect($result)->toBe('<div>Global Ad</div>');
});

test('getAdForArticleSlot returns higher priority ad when multiple ads exist for same category', function () {
    $category = Category::factory()->create();

    AdPlacement::factory()->forSlot(AdPlacement::SLOT_AFTER_HEADER)->forCategory($category)->create([
        'ad_code' => '<div>Low Priority Ad</div>',
        'priority' => 5,
    ]);

    AdPlacement::factory()->forSlot(AdPlacement::SLOT_AFTER_HEADER)->forCategory($category)->create([
        'ad_code' => '<div>High Priority Ad</div>',
        'priority' => 10,
    ]);

    $result = AdPlacement::getAdForArticleSlot(AdPlacement::SLOT_AFTER_HEADER, $category->id);

    expect($result)->toBe('<div>High Priority Ad</div>');
});

test('getAdForArticleSlot returns null when no ads exist', function () {
    $result = AdPlacement::getAdForArticleSlot(AdPlacement::SLOT_AFTER_HEADER, null);

    expect($result)->toBeNull();
});

test('getAdForArticleSlot only returns active ads', function () {
    $category = Category::factory()->create();

    AdPlacement::factory()->forSlot(AdPlacement::SLOT_AFTER_HEADER)->forCategory($category)->inactive()->create([
        'ad_code' => '<div>Inactive Ad</div>',
    ]);

    $result = AdPlacement::getAdForArticleSlot(AdPlacement::SLOT_AFTER_HEADER, $category->id);

    expect($result)->toBeNull();
});

test('getAdForArticleSlot only returns ads for the specified slot', function () {
    AdPlacement::factory()->forSlot(AdPlacement::SLOT_BEFORE_COMMENTS)->global()->create([
        'ad_code' => '<div>Before Comments Ad</div>',
    ]);

    $result = AdPlacement::getAdForArticleSlot(AdPlacement::SLOT_AFTER_HEADER, null);

    expect($result)->toBeNull();
});

test('ad placement scope active filters correctly', function () {
    AdPlacement::factory()->active()->create();
    AdPlacement::factory()->inactive()->create();

    $activeCount = AdPlacement::active()->count();

    expect($activeCount)->toBe(1);
});

test('ad placement scope forPageType filters correctly', function () {
    AdPlacement::factory()->create(['page_type' => 'article_detail']);
    AdPlacement::factory()->create(['page_type' => 'other_page']);

    $articleDetailCount = AdPlacement::forPageType('article_detail')->count();

    expect($articleDetailCount)->toBe(1);
});

test('ad placement slot_label attribute returns correct label', function () {
    $adPlacement = AdPlacement::factory()->forSlot(AdPlacement::SLOT_AFTER_HEADER)->create();

    expect($adPlacement->slot_label)->toBe('After Header/Meta');
});

test('authenticated user with permission can access ad placements index', function () {
    $user = createAdminUser();

    $response = $this->actingAs($user)->get(route('cms.ad-placements.index'));

    $response->assertSuccessful();
});

test('authenticated user with permission can create ad placement', function () {
    $user = createAdminUser();

    $response = $this->actingAs($user)->post(route('cms.ad-placements.store'), [
        'name' => 'Test Ad',
        'page_type' => 'article_detail',
        'slot' => 'after_header',
        'ad_code' => '<div>Test</div>',
        'is_active' => true,
        'priority' => 0,
    ]);

    $response->assertRedirect(route('cms.ad-placements.index'));
    expect(AdPlacement::count())->toBe(1);
    expect(AdPlacement::first()->name)->toBe('Test Ad');
});

test('authenticated user with permission can update ad placement', function () {
    $user = createAdminUser();
    $adPlacement = AdPlacement::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs($user)->put(route('cms.ad-placements.update', $adPlacement), [
        'name' => 'New Name',
        'page_type' => 'article_detail',
        'slot' => 'after_header',
        'ad_code' => '<div>Updated</div>',
        'is_active' => true,
        'priority' => 5,
    ]);

    $response->assertRedirect(route('cms.ad-placements.index'));
    expect($adPlacement->fresh()->name)->toBe('New Name');
});

test('authenticated user with permission can delete ad placement', function () {
    $user = createAdminUser();
    $adPlacement = AdPlacement::factory()->create();

    $response = $this->actingAs($user)->delete(route('cms.ad-placements.destroy', $adPlacement));

    $response->assertRedirect(route('cms.ad-placements.index'));
    expect(AdPlacement::count())->toBe(0);
});

test('authenticated user with permission can bulk delete ad placements', function () {
    $user = createAdminUser();
    $adPlacements = AdPlacement::factory()->count(3)->create();

    $response = $this->actingAs($user)->delete(route('cms.ad-placements.bulk-destroy'), [
        'ids' => $adPlacements->pluck('id')->toArray(),
    ]);

    $response->assertRedirect(route('cms.ad-placements.index'));
    expect(AdPlacement::count())->toBe(0);
});

test('ad placement store validates required fields', function () {
    $user = createAdminUser();

    $response = $this->actingAs($user)->post(route('cms.ad-placements.store'), []);

    $response->assertSessionHasErrors(['name', 'page_type', 'slot', 'ad_code']);
});

test('ad placement store validates slot is valid', function () {
    $user = createAdminUser();

    $response = $this->actingAs($user)->post(route('cms.ad-placements.store'), [
        'name' => 'Test',
        'page_type' => 'article_detail',
        'slot' => 'invalid_slot',
        'ad_code' => '<div>Test</div>',
    ]);

    $response->assertSessionHasErrors(['slot']);
});

test('ad placement store validates category exists when provided', function () {
    $user = createAdminUser();

    $response = $this->actingAs($user)->post(route('cms.ad-placements.store'), [
        'name' => 'Test',
        'page_type' => 'article_detail',
        'slot' => 'after_header',
        'ad_code' => '<div>Test</div>',
        'category_id' => 99999,
    ]);

    $response->assertSessionHasErrors(['category_id']);
});

test('ad placement is deleted when associated category is deleted', function () {
    $category = Category::factory()->create();
    $adPlacement = AdPlacement::factory()->forCategory($category)->create();

    $category->delete();

    expect(AdPlacement::find($adPlacement->id))->toBeNull();
});
