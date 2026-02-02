<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreSponsorRequest;
use App\Http\Requests\Cms\UpdateSponsorRequest;
use App\Models\Language;
use App\Models\Sponsor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SponsorController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'order');
        $sortDirection = $request->get('direction', 'asc');

        $allowedSorts = ['name', 'slug', 'posts_count', 'order', 'is_active', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'order';
        }

        $query = Sponsor::query()->withCount('posts')->with('featuredMedia');

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereTranslatedNameLike($search)
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sort
        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';
        if ($sortField === 'name') {
            $query->orderByTranslatedName(app()->getLocale(), $direction);
        } else {
            $query->orderBy($sortField, $direction);
        }

        // Get active languages
        $activeLanguages = Language::active()->ordered()->get();

        $sponsors = $query->paginate(20)
            ->withQueryString()
            ->through(fn (Sponsor $sponsor) => [
                'id' => $sponsor->id,
                'uuid' => $sponsor->uuid,
                'name' => $sponsor->name,
                'slug' => $sponsor->slug,
                'url' => $sponsor->url,
                'featured_image_url' => $sponsor->featured_image_url,
                'is_active' => $sponsor->is_active,
                'order' => $sponsor->order,
                'posts_count' => $sponsor->posts_count,
                'created_at' => $sponsor->created_at,
                'translated_locales' => array_keys($sponsor->getTranslations('name')),
            ]);

        return Inertia::render('Sponsors/Index', [
            'sponsors' => $sponsors,
            'filters' => $request->only(['search', 'sort', 'direction', 'is_active']),
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

        return Inertia::render('Sponsors/Create', [
            'languages' => $languages->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
            ]),
        ]);
    }

    public function store(StoreSponsorRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Handle translations - filter out empty values
        $name = $validated['name'];
        if (is_array($name)) {
            $name = array_filter($name, fn ($v) => $v !== null && $v !== '');
        }

        $url = $validated['url'] ?? null;
        if (is_array($url)) {
            $url = array_filter($url, fn ($v) => $v !== null && $v !== '');
        }

        $label = $validated['label'] ?? null;
        if (is_array($label)) {
            $label = array_filter($label, fn ($v) => $v !== null && $v !== '');
        }

        Sponsor::create([
            'name' => $name,
            'slug' => $validated['slug'] ?? null,
            'url' => $url,
            'label' => $label,
            'featured_media_id' => $validated['featured_media_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'order' => $validated['order'] ?? 0,
        ]);

        return redirect()->route('cms.sponsors.index')
            ->with('success', 'Sponsor created successfully.');
    }

    public function edit(Request $request, Sponsor $sponsor): Response|\Illuminate\Http\JsonResponse
    {
        $sponsor->load('featuredMedia');
        $activeLanguages = Language::active()->ordered()->get();

        $sponsorData = [
            'id' => $sponsor->id,
            'uuid' => $sponsor->uuid,
            'name' => $sponsor->name,
            'name_translations' => $sponsor->getTranslations('name'),
            'slug' => $sponsor->slug,
            'url' => $sponsor->url,
            'url_translations' => $sponsor->getTranslations('url'),
            'label' => $sponsor->label,
            'label_translations' => $sponsor->getTranslations('label'),
            'featured_media_id' => $sponsor->featured_media_id,
            'featured_media' => $sponsor->featuredMedia ? [
                'id' => $sponsor->featuredMedia->id,
                'url' => $sponsor->featuredMedia->url,
                'title' => $sponsor->featuredMedia->title,
            ] : null,
            'is_active' => $sponsor->is_active,
            'order' => $sponsor->order,
            'posts_count' => $sponsor->posts_count,
            'created_at' => $sponsor->created_at,
        ];

        $languageData = $activeLanguages->map(fn ($lang) => [
            'code' => $lang->code,
            'name' => $lang->name,
            'native_name' => $lang->native_name,
            'direction' => $lang->direction,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'props' => [
                    'sponsor' => $sponsorData,
                    'languages' => $languageData,
                ],
            ]);
        }

        return Inertia::render('Sponsors/Edit', [
            'sponsor' => $sponsorData,
            'languages' => $languageData,
        ]);
    }

    public function update(UpdateSponsorRequest $request, Sponsor $sponsor): RedirectResponse
    {
        $validated = $request->validated();

        // Handle translations - filter out empty values
        $name = $validated['name'];
        if (is_array($name)) {
            $name = array_filter($name, fn ($v) => $v !== null && $v !== '');
        }

        $url = $validated['url'] ?? $sponsor->getTranslations('url');
        if (is_array($url)) {
            $url = array_filter($url, fn ($v) => $v !== null && $v !== '');
        }

        $label = $validated['label'] ?? $sponsor->getTranslations('label');
        if (is_array($label)) {
            $label = array_filter($label, fn ($v) => $v !== null && $v !== '');
        }

        $sponsor->update([
            'name' => $name,
            'slug' => $validated['slug'] ?? $sponsor->slug,
            'url' => $url,
            'label' => $label,
            'featured_media_id' => $validated['featured_media_id'] ?? $sponsor->featured_media_id,
            'is_active' => $validated['is_active'] ?? $sponsor->is_active,
            'order' => $validated['order'] ?? $sponsor->order,
        ]);

        return redirect()->route('cms.sponsors.index')
            ->with('success', 'Sponsor updated successfully.');
    }

    public function destroy(Sponsor $sponsor): RedirectResponse
    {
        if ($sponsor->posts()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete a sponsor with associated posts. Please remove posts from this sponsor first.');
        }

        $sponsor->delete();

        return redirect()->route('cms.sponsors.index')
            ->with('success', 'Sponsor deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:sponsors,id'],
        ]);

        $ids = $validated['ids'];

        // Check for sponsors with posts
        $sponsorsWithPosts = Sponsor::whereIn('id', $ids)
            ->whereHas('posts')
            ->pluck('name')
            ->toArray();

        if (! empty($sponsorsWithPosts)) {
            return redirect()->back()
                ->with('error', 'Cannot delete sponsors with posts: '.implode(', ', $sponsorsWithPosts));
        }

        $count = Sponsor::whereIn('id', $ids)->delete();

        return redirect()->route('cms.sponsors.index')
            ->with('success', "{$count} sponsors deleted successfully.");
    }
}
