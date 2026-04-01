<?php

use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the application returns a successful response', function () {
    Language::create([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
    ]);

    $user = User::factory()->create();

    Page::factory()->create([
        'slug' => 'home',
        'title' => 'Welcome',
        'content' => '<h1>Welcome</h1>',
        'status' => Page::STATUS_PUBLISHED,
        'is_blade' => true,
        'author_id' => $user->id,
        'published_at' => now(),
    ]);

    $response = $this->get('/');

    $response->assertStatus(200);
});
