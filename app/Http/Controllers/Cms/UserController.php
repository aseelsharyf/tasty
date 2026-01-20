<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        // Validate sort field to prevent SQL injection
        $allowedSorts = ['name', 'email', 'username', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }

        $users = User::query()
            ->with('roles')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when($request->roles, function ($query, $roles) {
                $query->whereHas('roles', function ($q) use ($roles) {
                    $q->whereIn('name', (array) $roles);
                });
            })
            ->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'avatar_url' => $user->avatar_url ?? $user->avatar,
                    'google_id' => $user->google_id,
                    'roles' => $user->roles->pluck('name'),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            });

        $roles = Role::orderBy('name')->pluck('name');

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only(['search', 'sort', 'direction', 'roles']),
        ]);
    }

    public function create(): Response
    {
        $roles = Role::all()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
            ];
        });

        return Inertia::render('Users/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users', 'regex:/^[a-z0-9\-]+$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        if ($request->hasFile('avatar')) {
            $user->addMediaFromRequest('avatar')
                ->toMediaCollection('avatar');
        }

        return redirect()->route('cms.users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user): Response
    {
        return $this->edit($user);
    }

    public function edit(User $user): Response
    {
        $user->load('roles');

        $roles = Role::all()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
            ];
        });

        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url ?? $user->avatar,
                'avatar' => $user->avatar,
                'google_id' => $user->google_id,
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$user->id, 'regex:/^[a-z0-9\-]+$/'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
        ]);

        if (! empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        $user->syncRoles($validated['roles'] ?? []);

        if ($request->hasFile('avatar')) {
            // Store avatar directly on the user model (simpler than media library for this use case)
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => '/storage/'.$avatarPath]);
            // Also update media library for backward compatibility
            $user->addMediaFromRequest('avatar')
                ->toMediaCollection('avatar');
        } elseif ($request->boolean('remove_avatar')) {
            // Clear both the avatar field and media library
            $user->update(['avatar' => null]);
            $user->clearMediaCollection('avatar');
        }

        return redirect()->route('cms.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('cms.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
