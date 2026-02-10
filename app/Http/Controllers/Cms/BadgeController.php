<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreBadgeRequest;
use App\Http\Requests\Cms\UpdateBadgeRequest;
use App\Models\Badge;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BadgeController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'order');
        $sortDirection = $request->get('direction', 'asc');

        $allowedSorts = ['name', 'slug', 'order', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'order';
        }

        $query = Badge::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereTranslatedNameLike($search)
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';
        if ($sortField === 'name') {
            $query->orderByTranslatedName(app()->getLocale(), $direction);
        } else {
            $query->orderBy($sortField, $direction);
        }

        $activeLanguages = Language::active()->ordered()->get();

        $badges = $query->paginate(20)
            ->withQueryString()
            ->through(fn (Badge $badge) => [
                'id' => $badge->id,
                'uuid' => $badge->uuid,
                'name' => $badge->name,
                'slug' => $badge->slug,
                'icon' => $badge->icon,
                'color' => $badge->color,
                'is_active' => $badge->is_active,
                'order' => $badge->order,
                'created_at' => $badge->created_at,
                'translated_locales' => array_keys($badge->getTranslations('name')),
            ]);

        return Inertia::render('Badges/Index', [
            'badges' => $badges,
            'filters' => $request->only(['search', 'sort', 'direction']),
            'languages' => $activeLanguages->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
            ]),
        ]);
    }

    public function create(): Response
    {
        $languages = Language::active()->ordered()->get();

        return Inertia::render('Badges/Create', [
            'languages' => $languages->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
            ]),
        ]);
    }

    public function store(StoreBadgeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $name = $validated['name'];
        if (is_array($name)) {
            $name = array_filter($name, fn ($v) => $v !== null && $v !== '');
        }

        $description = $validated['description'] ?? null;
        if (is_array($description)) {
            $description = array_filter($description, fn ($v) => $v !== null && $v !== '');
        }

        Badge::create([
            'name' => $name,
            'slug' => $validated['slug'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'color' => $validated['color'] ?? 'primary',
            'description' => $description ?: null,
            'is_active' => $validated['is_active'] ?? true,
            'order' => $validated['order'] ?? 0,
        ]);

        return redirect()->route('cms.badges.index')
            ->with('success', 'Badge created successfully.');
    }

    public function edit(Badge $badge): Response
    {
        $activeLanguages = Language::active()->ordered()->get();

        return Inertia::render('Badges/Edit', [
            'badge' => [
                'id' => $badge->id,
                'uuid' => $badge->uuid,
                'name' => $badge->name,
                'name_translations' => $badge->getTranslations('name'),
                'slug' => $badge->slug,
                'icon' => $badge->icon,
                'color' => $badge->color,
                'description' => $badge->description,
                'description_translations' => $badge->getTranslations('description'),
                'is_active' => $badge->is_active,
                'order' => $badge->order,
                'created_at' => $badge->created_at,
            ],
            'languages' => $activeLanguages->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
            ]),
        ]);
    }

    public function update(UpdateBadgeRequest $request, Badge $badge): RedirectResponse
    {
        $validated = $request->validated();

        $name = $validated['name'];
        if (is_array($name)) {
            $name = array_filter($name, fn ($v) => $v !== null && $v !== '');
        }

        $description = $validated['description'] ?? null;
        if (is_array($description)) {
            $description = array_filter($description, fn ($v) => $v !== null && $v !== '');
        }

        $badge->update([
            'name' => $name,
            'slug' => $validated['slug'] ?? $badge->slug,
            'icon' => $validated['icon'] ?? null,
            'color' => $validated['color'] ?? 'primary',
            'description' => $description ?: null,
            'is_active' => $validated['is_active'] ?? true,
            'order' => $validated['order'] ?? 0,
        ]);

        return redirect()->route('cms.badges.index')
            ->with('success', 'Badge updated successfully.');
    }

    public function destroy(Badge $badge): RedirectResponse
    {
        $badge->delete();

        return redirect()->route('cms.badges.index')
            ->with('success', 'Badge deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:badges,id'],
        ]);

        $count = Badge::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('cms.badges.index')
            ->with('success', "{$count} badges deleted successfully.");
    }
}
