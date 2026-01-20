<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        // Store the intended URL so we can redirect back after login
        if ($request->has('redirect')) {
            session(['social_auth_redirect' => $request->input('redirect')]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // First check if user exists with this email (most reliable)
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // User exists - only update Google ID if not set (don't overwrite avatar)
                if (! $user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->id,
                    ]);
                }
            } else {
                // Check if user exists with this Google ID (edge case)
                $user = User::where('google_id', $googleUser->id)->first();

                if (! $user) {
                    // Create new user - set avatar only on creation
                    $user = User::create([
                        'name' => $googleUser->name,
                        'email' => $googleUser->email,
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar,
                        'email_verified_at' => now(),
                    ]);
                }
                // Existing user with google_id - don't update anything
            }

            Auth::login($user, true);

            // Redirect to intended URL or default
            $redirect = session()->pull('social_auth_redirect', route('recipes.submit'));

            return redirect($redirect);
        } catch (\Exception $e) {
            \Log::error('Google OAuth Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('recipes.submit')
                ->with('error', 'Unable to login with Google: '.$e->getMessage());
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $redirect = $request->input('redirect', route('home'));

        return redirect($redirect);
    }

    public function user(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['authenticated' => false]);
        }

        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar ?? $user->avatar_url,
            ],
        ]);
    }

    public function updateProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'avatar' => ['sometimes', 'image', 'max:2048'],
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = '/storage/'.$avatarPath;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar ?? $user->avatar_url,
            ],
        ]);
    }

    public function removeAvatar(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Remove avatar
        $user->avatar = null;
        $user->save();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => null,
            ],
        ]);
    }
}
