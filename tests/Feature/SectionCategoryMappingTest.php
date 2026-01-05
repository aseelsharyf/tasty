<?php

use App\Models\Category;
use App\Models\Language;
use App\Models\Post;
use App\Models\SectionCategoryMapping;
use App\Models\User;
use App\Services\Layouts\SectionCategoryMappingService;
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
    ]);

    Permission::firstOrCreate(['name' => 'settings.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'settings.edit', 'guard_name' => 'web']);

    $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    $adminRole->syncPermissions(['settings.view', 'settings.edit']);
});

describe('SectionCategoryMappingService', function () {
    it('returns null when no mappings exist (allow all)', function () {
        $service = app(SectionCategoryMappingService::class);

        $allowedIds = $service->getAllowedCategoryIds('review');

        expect($allowedIds)->toBeNull();
    });

    it('returns category IDs when mappings exist', function () {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        SectionCategoryMapping::create([
            'section_type' => 'review',
            'category_id' => $category1->id,
        ]);
        SectionCategoryMapping::create([
            'section_type' => 'review',
            'category_id' => $category2->id,
        ]);

        $service = app(SectionCategoryMappingService::class);
        $service->clearCache();

        $allowedIds = $service->getAllowedCategoryIds('review');

        expect($allowedIds)->toBeArray()
            ->and($allowedIds)->toContain($category1->id)
            ->and($allowedIds)->toContain($category2->id);
    });

    it('allows category when no restrictions set', function () {
        $category = Category::factory()->create();
        $service = app(SectionCategoryMappingService::class);

        $isAllowed = $service->isCategoryAllowed('review', $category->id);

        expect($isAllowed)->toBeTrue();
    });

    it('allows category when in allowed list', function () {
        $category = Category::factory()->create();

        SectionCategoryMapping::create([
            'section_type' => 'review',
            'category_id' => $category->id,
        ]);

        $service = app(SectionCategoryMappingService::class);
        $service->clearCache();

        $isAllowed = $service->isCategoryAllowed('review', $category->id);

        expect($isAllowed)->toBeTrue();
    });

    it('denies category when not in allowed list', function () {
        $allowedCategory = Category::factory()->create();
        $deniedCategory = Category::factory()->create();

        SectionCategoryMapping::create([
            'section_type' => 'review',
            'category_id' => $allowedCategory->id,
        ]);

        $service = app(SectionCategoryMappingService::class);
        $service->clearCache();

        $isAllowed = $service->isCategoryAllowed('review', $deniedCategory->id);

        expect($isAllowed)->toBeFalse();
    });

    it('checks post categories correctly', function () {
        $allowedCategory = Category::factory()->create();
        $deniedCategory = Category::factory()->create();

        SectionCategoryMapping::create([
            'section_type' => 'featured-person',
            'category_id' => $allowedCategory->id,
        ]);

        $service = app(SectionCategoryMappingService::class);
        $service->clearCache();

        $allowedResult = $service->isPostAllowedByCategories('featured-person', [$allowedCategory->id]);
        $deniedResult = $service->isPostAllowedByCategories('featured-person', [$deniedCategory->id]);
        $mixedResult = $service->isPostAllowedByCategories('featured-person', [$allowedCategory->id, $deniedCategory->id]);

        expect($allowedResult)->toBeTrue()
            ->and($deniedResult)->toBeFalse()
            ->and($mixedResult)->toBeTrue();
    });

    it('sets allowed categories and clears old ones', function () {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        $category3 = Category::factory()->create();

        SectionCategoryMapping::create([
            'section_type' => 'spread',
            'category_id' => $category1->id,
        ]);

        $service = app(SectionCategoryMappingService::class);
        $service->setAllowedCategories('spread', [$category2->id, $category3->id]);

        $allowedIds = $service->getAllowedCategoryIds('spread');

        expect($allowedIds)->not->toContain($category1->id)
            ->and($allowedIds)->toContain($category2->id)
            ->and($allowedIds)->toContain($category3->id);
    });
});

describe('Section Categories Settings Page', function () {
    it('shows settings page for authorized users', function () {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->get('/cms/settings/section-categories');

        $response->assertStatus(200);
    });

    it('returns categories and section types', function () {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Category::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/cms/settings/section-categories');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->has('sections')
                ->has('categories')
                ->has('mappings')
            );
    });

    it('updates mappings via PUT request', function () {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        $response = $this->actingAs($user)->put('/cms/settings/section-categories', [
            'mappings' => [
                'review' => [$category1->id],
                'featured-person' => [$category1->id, $category2->id],
            ],
        ]);

        $response->assertRedirect('/cms/settings/section-categories');

        expect(SectionCategoryMapping::where('section_type', 'review')->count())->toBe(1)
            ->and(SectionCategoryMapping::where('section_type', 'featured-person')->count())->toBe(2);
    });
});

describe('Post Search API with Section Type', function () {
    it('returns all posts when no section type specified', function () {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        $post1 = Post::factory()->published()->create();
        $post1->categories()->attach($category1);

        $post2 = Post::factory()->published()->create();
        $post2->categories()->attach($category2);

        $response = $this->actingAs($user)->get('/cms/layouts/homepage/search-posts');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'posts');
    });

    it('filters posts by section type when restrictions exist', function () {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $allowedCategory = Category::factory()->create();
        $deniedCategory = Category::factory()->create();

        SectionCategoryMapping::create([
            'section_type' => 'review',
            'category_id' => $allowedCategory->id,
        ]);

        $allowedPost = Post::factory()->published()->create(['title' => 'Allowed Post']);
        $allowedPost->categories()->attach($allowedCategory);

        $deniedPost = Post::factory()->published()->create(['title' => 'Denied Post']);
        $deniedPost->categories()->attach($deniedCategory);

        $service = app(SectionCategoryMappingService::class);
        $service->clearCache();

        $response = $this->actingAs($user)->get('/cms/layouts/homepage/search-posts?sectionType=review');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'posts')
            ->assertJsonPath('posts.0.title', 'Allowed Post');
    });

    it('returns all posts when section type has no restrictions', function () {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $category = Category::factory()->create();

        $post1 = Post::factory()->published()->create();
        $post1->categories()->attach($category);

        $post2 = Post::factory()->published()->create();
        $post2->categories()->attach($category);

        $response = $this->actingAs($user)->get('/cms/layouts/homepage/search-posts?sectionType=hero');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'posts');
    });
});
