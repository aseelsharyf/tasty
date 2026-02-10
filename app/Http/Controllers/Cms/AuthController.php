<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        $devUsers = null;

        if (app()->environment('local')) {
            $devUsers = \App\Models\User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'Developer', 'Editor', 'Writer', 'Photographer']))
                ->with('roles')
                ->limit(10)
                ->get()
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name ?? 'User',
                ]);
        }

        return Inertia::render('Auth/Login', [
            'isLocal' => app()->environment('local'),
            'devUsers' => $devUsers,
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('cms.dashboard'));
    }

    public function devLogin(Request $request): RedirectResponse
    {
        abort_unless(app()->environment('local'), 404);

        $request->validate([
            'id' => ['required', 'integer', 'exists:users,id'],
        ]);

        Auth::loginUsingId($request->integer('id'));
        $request->session()->regenerate();

        return redirect()->intended(route('cms.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('cms.login');
    }
}
