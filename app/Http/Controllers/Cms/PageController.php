<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StorePageRequest;
use App\Http\Requests\Cms\UpdatePageRequest;
use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function index(Request $request, Language $language): Response
    {
        $query = Page::query()
            ->with(['author:id,name', 'language:code,name,native_name,direction'])
            ->where('language_code', $language->code);

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%")
                    ->orWhere('content', 'ilike', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }

        // Sorting
        $sortField = $request->get('sort', 'updated_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['title', 'slug', 'status', 'created_at', 'updated_at', 'published_at'];
        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $direction);
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        // Paginate
        $pages = $query->paginate(20)
            ->withQueryString()
            ->through(fn (Page $page) => [
                'id' => $page->id,
                'uuid' => $page->uuid,
                'language_code' => $page->language_code,
                'language' => $page->language ? [
                    'code' => $page->language->code,
                    'name' => $page->language->name,
                    'native_name' => $page->language->native_name,
                ] : null,
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status,
                'layout' => $page->layout,
                'is_blade' => $page->is_blade,
                'author' => $page->author ? [
                    'id' => $page->author->id,
                    'name' => $page->author->name,
                ] : null,
                'created_at' => $page->created_at,
                'updated_at' => $page->updated_at,
                'published_at' => $page->published_at,
            ]);

        // Get counts for this language
        $baseQuery = Page::where('language_code', $language->code);
        $totalPages = (clone $baseQuery)->count();
        $publishedCount = (clone $baseQuery)->where('status', Page::STATUS_PUBLISHED)->count();
        $draftCount = (clone $baseQuery)->where('status', Page::STATUS_DRAFT)->count();

        // Get available languages
        $languages = Language::active()->ordered()->get(['code', 'name', 'native_name', 'direction', 'is_default']);

        return Inertia::render('Pages/Index', [
            'pages' => $pages,
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
            'counts' => [
                'total' => $totalPages,
                'published' => $publishedCount,
                'draft' => $draftCount,
            ],
            'statuses' => Page::getStatuses(),
            'layouts' => Page::getLayouts(),
            'languages' => $languages,
            'currentLanguage' => [
                'code' => $language->code,
                'name' => $language->name,
                'native_name' => $language->native_name,
                'direction' => $language->direction,
            ],
        ]);
    }

    public function create(Request $request, Language $language): Response
    {
        $authors = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $languages = Language::active()->ordered()->get(['code', 'name', 'native_name', 'direction']);

        return Inertia::render('Pages/Create', [
            'statuses' => Page::getStatuses(),
            'layouts' => Page::getLayouts(),
            'authors' => $authors,
            'languages' => $languages,
            'currentLanguage' => [
                'code' => $language->code,
                'name' => $language->name,
                'native_name' => $language->native_name,
                'direction' => $language->direction,
            ],
        ]);
    }

    public function store(StorePageRequest $request, Language $language): RedirectResponse
    {
        $validated = $request->validated();

        // Set language from URL
        $validated['language_code'] = $language->code;

        // Set author if not provided
        if (! isset($validated['author_id'])) {
            $validated['author_id'] = $request->user()->id;
        }

        // Handle publish
        if ($validated['status'] === Page::STATUS_PUBLISHED && ! isset($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $page = Page::create($validated);

        return redirect()->route('cms.pages.edit', ['language' => $language->code, 'page' => $page])
            ->with('success', 'Page created successfully.');
    }

    public function edit(Request $request, Language $language, Page $page): Response
    {
        $page->load(['author:id,name', 'language:code,name,native_name,direction']);

        $authors = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $languages = Language::active()->ordered()->get(['code', 'name', 'native_name', 'direction']);

        $pageData = [
            'id' => $page->id,
            'uuid' => $page->uuid,
            'language_code' => $page->language_code,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'layout' => $page->layout,
            'status' => $page->status,
            'is_blade' => $page->is_blade,
            'editor_mode' => $page->editor_mode ?? 'code',
            'author_id' => $page->author_id,
            'meta_title' => $page->getRawOriginal('meta_title'),
            'meta_description' => $page->meta_description,
            'published_at' => $page->published_at?->format('Y-m-d\TH:i'),
            'author' => $page->author ? [
                'id' => $page->author->id,
                'name' => $page->author->name,
            ] : null,
            'language' => $page->language ? [
                'code' => $page->language->code,
                'name' => $page->language->name,
                'native_name' => $page->language->native_name,
                'direction' => $page->language->direction,
            ] : null,
        ];

        return Inertia::render('Pages/Edit', [
            'page' => $pageData,
            'statuses' => Page::getStatuses(),
            'layouts' => Page::getLayouts(),
            'authors' => $authors,
            'languages' => $languages,
            'currentLanguage' => [
                'code' => $language->code,
                'name' => $language->name,
                'native_name' => $language->native_name,
                'direction' => $language->direction,
            ],
        ]);
    }

    public function update(UpdatePageRequest $request, Language $language, Page $page): RedirectResponse
    {
        $validated = $request->validated();

        // Handle publish
        if ($validated['status'] === Page::STATUS_PUBLISHED && $page->status !== Page::STATUS_PUBLISHED) {
            if (! isset($validated['published_at'])) {
                $validated['published_at'] = now();
            }
        }

        $page->update($validated);

        return redirect()->route('cms.pages.edit', ['language' => $language->code, 'page' => $page])
            ->with('success', 'Page updated successfully.');
    }

    public function destroy(Language $language, Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('cms.pages.index', ['language' => $language->code])
            ->with('success', 'Page deleted successfully.');
    }

    public function bulkDestroy(Request $request, Language $language): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:pages,id'],
        ]);

        $count = Page::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('cms.pages.index', ['language' => $language->code])
            ->with('success', "{$count} pages deleted successfully.");
    }
}
