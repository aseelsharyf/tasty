<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile page.
     */
    public function index(?string $tab = null): Response
    {
        $user = Auth::user();

        return Inertia::render('Profile/Index', [
            'tab' => $tab ?? 'profile',
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
                'roles' => $user->getRoleNames()->toArray(),
                'created_at' => $user->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:users,username,'.$user->id],
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Password updated successfully.');
    }

    /**
     * Update the user's avatar.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'], // 2MB max
        ]);

        $user = Auth::user();

        // Clear existing avatar if any
        $user->clearMediaCollection('avatar');

        // Add new avatar
        $user->addMediaFromRequest('avatar')
            ->toMediaCollection('avatar');

        return redirect()->back()->with('success', 'Avatar updated successfully.');
    }

    /**
     * Update the user's editor preferences.
     */
    public function updateEditorPreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'editor_block_order' => ['required', 'array', 'min:1'],
            'editor_block_order.*' => ['required', 'string'],
        ]);

        Auth::user()->setEditorBlockOrder($validated['editor_block_order']);

        return redirect()->back()->with('success', 'Editor preferences updated.');
    }

    /**
     * Remove the user's avatar.
     */
    public function destroyAvatar(): RedirectResponse
    {
        Auth::user()->clearMediaCollection('avatar');

        return redirect()->back()->with('success', 'Avatar removed successfully.');
    }
}
