<?php

use App\Models\Language;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

uses(RefreshDatabase::class);

beforeEach(function () {
    Language::create([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
    ]);
});

it('does not repeat the page title when the content includes an h1', function () {
    Page::factory()->published()->create([
        'title' => 'Privacy Policy',
        'slug' => 'legal-page-with-heading',
        'content' => '<h1>Privacy Policy</h1><p>Policy content.</p>',
    ]);

    $response = $this->get('/legal-page-with-heading');

    $response->assertSuccessful();

    expect(substr_count($response->getContent(), '<h1>Privacy Policy</h1>'))->toBe(1);
});

it('shows the page title when the content does not include an h1', function () {
    Page::factory()->published()->create([
        'title' => 'Legal Information',
        'slug' => 'legal-page-without-heading',
        'content' => '<p>Legal content.</p>',
    ]);

    $response = $this->get('/legal-page-without-heading');

    $response->assertSuccessful();
    $response->assertSee('<h1>Legal Information</h1>', false);
});

it('shows the merchant identity and complete addresses in the site footer', function () {
    Page::factory()->published()->create([
        'title' => 'Merchant Information',
        'slug' => 'merchant-information',
        'content' => '<p>Merchant information page.</p>',
    ]);

    $response = $this->get('/merchant-information');

    $response->assertSuccessful()
        ->assertSee('Tasty Magazine')
        ->assertSee('Greyscale Creative Pvt Ltd (Reg: C11932022)')
        ->assertSee("H. Eevaau, 5th Floor, Mialani Goalhi 20069, K. Male', Maldives", false)
        ->assertSee('Permanent establishment')
        ->assertSee('Postal address');
});

it('renders single images using the selected orientation', function (string $orientation, string $aspectClass) {
    $html = Blade::render(
        '<x-blocks.media :items="$items" layout="single" :single-image-display="$orientation" />',
        [
            'items' => [[
                'url' => '/images/example.jpg',
                'alt_text' => 'Example image',
                'is_video' => false,
            ]],
            'orientation' => $orientation,
        ],
    );

    expect($html)
        ->toContain($aspectClass)
        ->toContain('object-cover');
})->with([
    'landscape' => ['landscape', 'aspect-[4/3]'],
    'portrait' => ['portrait', 'aspect-[3/4]'],
]);
