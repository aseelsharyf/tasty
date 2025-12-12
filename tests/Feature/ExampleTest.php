<?php

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the application returns a successful response', function () {
    // Create the home page
    Page::factory()->create([
        'slug' => 'home',
        'title' => 'Welcome',
        'content' => '<h1>Welcome</h1>',
        'status' => Page::STATUS_PUBLISHED,
        'is_blade' => true,
        'published_at' => now(),
    ]);

    $response = $this->get('/');

    $response->assertStatus(200);
});
