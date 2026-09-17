<?php

use App\Models\Category;
use App\Models\Language;
use App\Models\Post;
use App\Models\Tag;
use App\Services\OgImageService;
use App\Services\RecipeSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

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

    Queue::fake();
    Storage::fake('public');
    $this->mock(OgImageService::class, function ($mock) {
        $mock->shouldReceive('getUrlForPost')->andReturn('https://example.com/social-card.png');
    });
});

/** @return list<array<string, mixed>> */
function recipeSeoMarkup(string $html): array
{
    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

    return array_map(fn (string $json): array => json_decode($json, true, 512, JSON_THROW_ON_ERROR), $matches[1]);
}

it('renders recipe structured data from the published recipe and preserves social metadata', function () {
    $post = Post::factory()->recipe()->published()->create([
        'title' => 'Mas Huni',
        'meta_title' => 'Easy Mas Huni Recipe | Tasty',
        'custom_fields' => [
            'prep_time' => '15',
            'cook_time' => '0',
            'servings' => 4,
            'difficulty' => 'Easy',
            'ingredients' => [
                ['section' => 'Main ingredients', 'items' => ['1 tin tuna', '1 cup coconut']],
                ['section' => 'Seasoning', 'items' => ['Salt & pepper']],
            ],
        ],
        'content' => ['blocks' => [
            ['type' => 'header', 'data' => ['text' => 'Preparation', 'level' => 2]],
            ['type' => 'paragraph', 'data' => ['text' => 'Drain the <b>tuna</b>.']],
            ['type' => 'list', 'data' => ['style' => 'ordered', 'items' => ['Mix with coconut.', 'Season &amp; serve.']]],
        ]],
    ]);
    $category = Category::factory()->create(['name' => ['en' => 'Breakfast'], 'slug' => 'breakfast']);
    $cuisineTag = Tag::factory()->create(['name' => ['en' => 'Maldivian'], 'slug' => 'maldivian']);
    $keywordTag = Tag::factory()->create(['name' => ['en' => 'Quick Meal'], 'slug' => 'quick-meal']);
    $post->categories()->attach($category);
    $post->tags()->attach([$cuisineTag->id, $keywordTag->id]);
    $post->addMedia(UploadedFile::fake()->image('mas-huni.jpg', 1200, 800))->toMediaCollection('featured', 'public');
    $url = route('post.show', ['category' => $category->slug, 'post' => $post->slug]);

    $response = $this->get($url)->assertSuccessful();
    $markup = collect(recipeSeoMarkup($response->getContent()));
    $recipe = $markup->firstWhere('@type', 'Recipe');

    expect($markup->where('@type', 'Recipe'))->toHaveCount(1)
        ->and($markup->where('@type', 'Article'))->toBeEmpty()
        ->and($markup->firstWhere('@type', 'BreadcrumbList'))->not->toBeNull()
        ->and($recipe)->toMatchArray([
            '@context' => 'https://schema.org',
            'name' => 'Mas Huni',
            'url' => $url,
            'prepTime' => 'PT15M',
            'cookTime' => 'PT0M',
            'totalTime' => 'PT15M',
            'recipeYield' => '4',
            'recipeCategory' => 'Breakfast',
            'recipeCuisine' => 'Maldivian',
            'keywords' => 'Quick Meal, Easy',
            'inLanguage' => 'en',
            'recipeIngredient' => ['1 tin tuna', '1 cup coconut', 'Salt & pepper'],
            'recipeInstructions' => [
                ['@type' => 'HowToStep', 'name' => 'Drain the tuna', 'text' => 'Drain the tuna.', 'url' => $url.'#recipe-step-1'],
                ['@type' => 'HowToStep', 'name' => 'Mix with coconut', 'text' => 'Mix with coconut.', 'url' => $url.'#recipe-step-2'],
                ['@type' => 'HowToStep', 'name' => 'Season & serve', 'text' => 'Season & serve.', 'url' => $url.'#recipe-step-3'],
            ],
        ])
        ->and($recipe['image'])->toBe($post->fresh()->featured_image_url)
        ->and($recipe['image'])->not->toBe('https://example.com/social-card.png')
        ->and($recipe['author']['name'])->toBe($post->author->name)
        ->and($recipe['datePublished'])->toBe($post->published_at->toIso8601String())
        ->and($recipe)->not->toHaveKeys(['aggregateRating', 'nutrition']);

    $response->assertSee('https://example.com/social-card.png', false);
    $response->assertSee('id="recipe-step-1"', false)
        ->assertSee('id="recipe-step-2"', false)
        ->assertSee('id="recipe-step-3"', false);
});

it('keeps regular posts as articles', function () {
    $post = Post::factory()->article()->published()->create();
    $category = Category::factory()->create();
    $post->categories()->attach($category);

    $response = $this->get(route('post.show', ['category' => $category->slug, 'post' => $post->slug]))->assertSuccessful();
    $markup = collect(recipeSeoMarkup($response->getContent()));

    expect($markup->where('@type', 'Recipe'))->toBeEmpty()
        ->and($markup->firstWhere('@type', 'Article'))->not->toHaveKeys(['recipeIngredient', 'recipeInstructions', 'prepTime'])
        ->and($markup->firstWhere('@type', 'Article')['image'])->toBe('https://example.com/social-card.png');
});

it('extracts nested and submitted instructions without headings or media captions', function () {
    $post = Post::factory()->recipe()->make([
        'content' => [
            ['type' => 'collapsible', 'data' => [
                'title' => 'Step 1: Preparation',
                'content' => ['blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'މަސް &amp; coconut<br>mix.']],
                    ['type' => 'list', 'data' => ['items' => [
                        ['content' => 'Heat the pan.', 'items' => [['content' => 'Add oil.', 'items' => []]]],
                    ]]],
                ]],
            ]],
            ['type' => 'checklist', 'data' => ['items' => [['text' => 'Serve warm.']]]],
            ['type' => 'paragraph', 'data' => ['text' => '&nbsp;']],
            ['type' => 'media', 'data' => ['caption' => 'Our sponsor']],
        ],
    ]);
    $url = $post->url;

    expect(app(RecipeSchemaService::class)->build($post)['recipeInstructions'])->toBe([
        ['@type' => 'HowToStep', 'name' => 'މަސް & coconut mix', 'text' => 'މަސް & coconut mix.', 'url' => $url.'#recipe-step-1'],
        ['@type' => 'HowToStep', 'name' => 'Heat the pan', 'text' => 'Heat the pan.', 'url' => $url.'#recipe-step-2'],
        ['@type' => 'HowToStep', 'name' => 'Add oil', 'text' => 'Add oil.', 'url' => $url.'#recipe-step-3'],
        ['@type' => 'HowToStep', 'name' => 'Serve warm', 'text' => 'Serve warm.', 'url' => $url.'#recipe-step-4'],
    ]);
});

it('omits missing or invalid optional recipe values', function (array $fields) {
    $post = Post::factory()->recipe()->make(['custom_fields' => $fields, 'content' => null]);

    expect(app(RecipeSchemaService::class)->build($post))->not->toHaveKeys([
        'prepTime', 'cookTime', 'totalTime', 'recipeYield', 'recipeIngredient', 'recipeInstructions',
    ]);
})->with([
    'missing' => [[]],
    'invalid' => [['prep_time' => -5, 'cook_time' => 'ten minutes', 'servings' => 0]],
    'empty' => [['prep_time' => '', 'cook_time' => null, 'servings' => ' ', 'ingredients' => []]],
]);

it('does not infer a total duration when one duration is missing', function () {
    $post = Post::factory()->recipe()->make(['custom_fields' => ['prep_time' => 10]]);

    expect(app(RecipeSchemaService::class)->build($post))->toHaveKey('prepTime', 'PT10M')
        ->not->toHaveKeys(['cookTime', 'totalTime']);
});

it('does not use a social card as a missing recipe photograph', function () {
    $post = Post::factory()->recipe()->published()->create(['custom_fields' => null, 'content' => null]);
    $category = Category::factory()->create();
    $post->categories()->attach($category);

    $response = $this->get(route('post.show', ['category' => $category->slug, 'post' => $post->slug]))->assertSuccessful();
    $recipe = collect(recipeSeoMarkup($response->getContent()))->firstWhere('@type', 'Recipe');

    expect($recipe)->toBeArray()
        ->not->toHaveKeys(['image', 'recipeIngredient', 'recipeInstructions', 'prepTime', 'recipeYield']);
});

it('safely encodes recipe metadata containing script delimiters', function () {
    $description = 'A recipe with </script><script>alert("test")</script> in its description.';
    $post = Post::factory()->recipe()->published()->create(['meta_description' => $description]);
    $category = Category::factory()->create();
    $post->categories()->attach($category);

    $response = $this->get(route('post.show', ['category' => $category->slug, 'post' => $post->slug]))->assertSuccessful();
    $recipe = collect(recipeSeoMarkup($response->getContent()))->firstWhere('@type', 'Recipe');

    expect($recipe['description'])->toBe($description);
    $response->assertDontSee('</script><script>alert("test")</script>', false);
});
