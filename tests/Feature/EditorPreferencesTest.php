<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Writer', 'guard_name' => 'web']);
});

function createWriterUser(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole('Writer');

    return $user;
}

it('saves editor block order preference', function () {
    $user = createWriterUser();
    $order = ['header', 'media', 'list', 'delimiter'];

    $response = $this->actingAs($user)->put(route('cms.profile.editor-preferences'), [
        'editor_block_order' => $order,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $user->refresh();
    expect($user->getEditorBlockOrder())->toBe($order);
});

it('validates editor block order is required', function () {
    $user = createWriterUser();

    $response = $this->actingAs($user)->put(route('cms.profile.editor-preferences'), [
        'editor_block_order' => [],
    ]);

    $response->assertSessionHasErrors('editor_block_order');
});

it('validates editor block order items must be strings', function () {
    $user = createWriterUser();

    $response = $this->actingAs($user)->put(route('cms.profile.editor-preferences'), [
        'editor_block_order' => [123, null],
    ]);

    $response->assertSessionHasErrors();
});

it('preserves other preferences when saving block order', function () {
    $user = createWriterUser([
        'preferences' => ['some_other_pref' => 'value'],
    ]);

    $order = ['media', 'header'];

    $this->actingAs($user)->put(route('cms.profile.editor-preferences'), [
        'editor_block_order' => $order,
    ]);

    $user->refresh();
    expect($user->preferences['some_other_pref'])->toBe('value');
    expect($user->getEditorBlockOrder())->toBe($order);
});

it('returns null for editor block order when no preference set', function () {
    $user = createWriterUser();

    expect($user->getEditorBlockOrder())->toBeNull();
});

it('requires authentication to update editor preferences', function () {
    $response = $this->put(route('cms.profile.editor-preferences'), [
        'editor_block_order' => ['header'],
    ]);

    $response->assertRedirect();
});
