<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Language;
use App\Models\Setting;
use App\Services\Layouts\SectionCategoryMappingService;
use App\Services\Layouts\SectionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(?string $tab = 'general'): Response
    {
        return Inertia::render('Settings/Index', [
            'tab' => $tab,
            'settings' => [
                // General
                'site_name' => Setting::get('site.name', config('app.name')),
                'site_tagline' => Setting::get('site.tagline', ''),
                'app_name' => config('app.name'),
                'app_url' => config('app.url'),
                // SEO & Meta
                'meta_keywords' => Setting::get('seo.meta_keywords', ''),
                'meta_description' => Setting::get('seo.meta_description', ''),
                // OpenGraph
                'og_title' => Setting::get('seo.og_title', ''),
                'og_description' => Setting::get('seo.og_description', ''),
                'og_image' => Setting::get('seo.og_image', ''),
                // Favicons
                'favicon' => Setting::get('site.favicon', ''),
                'favicon_16' => Setting::get('site.favicon_16', ''),
                'favicon_32' => Setting::get('site.favicon_32', ''),
                'apple_touch_icon' => Setting::get('site.apple_touch_icon', ''),
                // Social Links
                'social_facebook' => Setting::get('social.facebook', ''),
                'social_twitter' => Setting::get('social.twitter', ''),
                'social_instagram' => Setting::get('social.instagram', ''),
                'social_youtube' => Setting::get('social.youtube', ''),
                'social_tiktok' => Setting::get('social.tiktok', ''),
                'social_linkedin' => Setting::get('social.linkedin', ''),
            ],
        ]);
    }

    public function postTypes(): Response
    {
        return Inertia::render('Settings/PostTypes', [
            'postTypes' => Setting::getPostTypes(),
            'defaultPostTypes' => Setting::getDefaultPostTypes(),
        ]);
    }

    public function postTypesJson(): JsonResponse
    {
        $postTypes = collect(Setting::getPostTypes())->map(fn ($type) => [
            'value' => $type['slug'],
            'label' => $type['name'],
            'icon' => $type['icon'] ?? null,
        ])->values()->all();

        return response()->json(['postTypes' => $postTypes]);
    }

    public function updatePostTypes(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'post_types' => ['required', 'array'],
            'post_types.*.slug' => ['required', 'string', 'max:50'],
            'post_types.*.name' => ['required', 'string', 'max:100'],
            'post_types.*.icon' => ['nullable', 'string', 'max:100'],
            'post_types.*.fields' => ['array'],
            'post_types.*.fields.*.name' => ['required', 'string', 'max:50'],
            'post_types.*.fields.*.label' => ['required', 'string', 'max:100'],
            'post_types.*.fields.*.type' => ['required', 'string', 'in:text,number,textarea,select,toggle,repeater,grouped-repeater'],
            'post_types.*.fields.*.suffix' => ['nullable', 'string', 'max:20'],
            'post_types.*.fields.*.options' => ['nullable', 'array'],
        ]);

        Setting::setPostTypes($validated['post_types']);

        return redirect()->route('cms.settings.post-types')
            ->with('success', 'Post types updated successfully.');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Tab tracking
            '_tab' => ['nullable', 'string', 'in:general,seo,opengraph,favicons,social'],
            // General
            'site_name' => ['nullable', 'string', 'max:255'],
            'site_tagline' => ['nullable', 'string', 'max:500'],
            // SEO & Meta
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            // OpenGraph
            'og_title' => ['nullable', 'string', 'max:200'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // Favicons
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg', 'max:512'],
            'favicon_16' => ['nullable', 'image', 'mimes:png', 'max:512'],
            'favicon_32' => ['nullable', 'image', 'mimes:png', 'max:512'],
            'apple_touch_icon' => ['nullable', 'image', 'mimes:png', 'max:512'],
            // Social Links
            'social_facebook' => ['nullable', 'url', 'max:255'],
            'social_twitter' => ['nullable', 'url', 'max:255'],
            'social_instagram' => ['nullable', 'url', 'max:255'],
            'social_youtube' => ['nullable', 'url', 'max:255'],
            'social_tiktok' => ['nullable', 'url', 'max:255'],
            'social_linkedin' => ['nullable', 'url', 'max:255'],
            // Removals
            'remove_og_image' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
            'remove_favicon_16' => ['nullable', 'boolean'],
            'remove_favicon_32' => ['nullable', 'boolean'],
            'remove_apple_touch_icon' => ['nullable', 'boolean'],
        ]);

        // General settings
        Setting::set('site.name', $validated['site_name'] ?? '', 'site');
        Setting::set('site.tagline', $validated['site_tagline'] ?? '', 'site');

        // SEO settings
        Setting::set('seo.meta_keywords', $validated['meta_keywords'] ?? '', 'seo');
        Setting::set('seo.meta_description', $validated['meta_description'] ?? '', 'seo');

        // OpenGraph settings
        Setting::set('seo.og_title', $validated['og_title'] ?? '', 'seo');
        Setting::set('seo.og_description', $validated['og_description'] ?? '', 'seo');

        // Handle OG image upload
        if ($request->hasFile('og_image')) {
            $path = $request->file('og_image')->store('settings', 'public');
            $this->deleteOldFile('seo.og_image');
            Setting::set('seo.og_image', $path, 'seo');
        } elseif ($request->boolean('remove_og_image')) {
            $this->deleteOldFile('seo.og_image');
            Setting::set('seo.og_image', '', 'seo');
        }

        // Handle favicon uploads
        $faviconMap = [
            'favicon' => 'site.favicon',
            'favicon_16' => 'site.favicon_16',
            'favicon_32' => 'site.favicon_32',
            'apple_touch_icon' => 'site.apple_touch_icon',
        ];

        foreach ($faviconMap as $field => $key) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store('settings/favicons', 'public');
                $this->deleteOldFile($key);
                Setting::set($key, $path, 'site');
            } elseif ($request->boolean("remove_{$field}")) {
                $this->deleteOldFile($key);
                Setting::set($key, '', 'site');
            }
        }

        // Social links
        Setting::set('social.facebook', $validated['social_facebook'] ?? '', 'social');
        Setting::set('social.twitter', $validated['social_twitter'] ?? '', 'social');
        Setting::set('social.instagram', $validated['social_instagram'] ?? '', 'social');
        Setting::set('social.youtube', $validated['social_youtube'] ?? '', 'social');
        Setting::set('social.tiktok', $validated['social_tiktok'] ?? '', 'social');
        Setting::set('social.linkedin', $validated['social_linkedin'] ?? '', 'social');

        $tab = $validated['_tab'] ?? 'general';

        return redirect()->route('cms.settings', ['tab' => $tab])
            ->with('success', 'Settings updated successfully.');
    }

    private function deleteOldFile(string $settingKey): void
    {
        $oldPath = Setting::get($settingKey);
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    public function languages(): Response
    {
        $languages = Language::ordered()
            ->withCount('posts')
            ->get();

        return Inertia::render('Settings/Languages', [
            'languages' => $languages,
        ]);
    }

    public function storeLanguage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:languages,code'],
            'name' => ['required', 'string', 'max:100'],
            'native_name' => ['required', 'string', 'max:100'],
            'direction' => ['required', 'in:ltr,rtl'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        // If setting as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            Language::where('is_default', true)->update(['is_default' => false]);
        }

        // Normalize language code to lowercase
        $validated['code'] = strtolower($validated['code']);

        $maxOrder = Language::max('order') ?? 0;
        $validated['order'] = $maxOrder + 1;

        Language::create($validated);

        return redirect()->route('cms.settings.languages')
            ->with('success', 'Language added successfully.');
    }

    public function updateLanguage(Request $request, Language $language): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:languages,code,'.$language->id],
            'name' => ['required', 'string', 'max:100'],
            'native_name' => ['required', 'string', 'max:100'],
            'direction' => ['required', 'in:ltr,rtl'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        // If setting as default, unset other defaults
        if (($validated['is_default'] ?? false) && ! $language->is_default) {
            Language::where('is_default', true)->update(['is_default' => false]);
        }

        // Prevent unsetting the only default
        if (! ($validated['is_default'] ?? false) && $language->is_default) {
            $otherDefaults = Language::where('id', '!=', $language->id)->where('is_default', true)->exists();
            if (! $otherDefaults) {
                $validated['is_default'] = true;
            }
        }

        $language->update($validated);

        return redirect()->route('cms.settings.languages')
            ->with('success', 'Language updated successfully.');
    }

    public function destroyLanguage(Language $language): RedirectResponse
    {
        // Prevent deleting the default language
        if ($language->is_default) {
            return redirect()->route('cms.settings.languages')
                ->with('error', 'Cannot delete the default language. Set another language as default first.');
        }

        // Prevent deleting if posts exist
        if ($language->posts()->exists()) {
            return redirect()->route('cms.settings.languages')
                ->with('error', 'Cannot delete language with existing posts.');
        }

        $language->delete();

        return redirect()->route('cms.settings.languages')
            ->with('success', 'Language deleted successfully.');
    }

    public function reorderLanguages(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer', 'exists:languages,id'],
        ]);

        foreach ($validated['order'] as $index => $id) {
            Language::where('id', $id)->update(['order' => $index + 1]);
        }

        return redirect()->route('cms.settings.languages')
            ->with('success', 'Languages reordered successfully.');
    }

    public function media(): Response
    {
        return Inertia::render('Settings/Media', [
            'cropPresets' => Setting::getCropPresets(),
            'defaultCropPresets' => Setting::getDefaultCropPresets(),
            'mediaCategories' => Setting::getMediaCategories(),
            'defaultMediaCategories' => Setting::getDefaultMediaCategories(),
        ]);
    }

    public function updateMedia(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'crop_presets' => ['required', 'array'],
            'crop_presets.*.name' => ['required', 'string', 'max:50', 'regex:/^[a-z_]+$/'],
            'crop_presets.*.label' => ['required', 'string', 'max:100'],
            'crop_presets.*.width' => ['required', 'integer', 'min:10', 'max:4000'],
            'crop_presets.*.height' => ['required', 'integer', 'min:10', 'max:4000'],
            'media_categories' => ['required', 'array'],
            'media_categories.*.slug' => ['required', 'string', 'max:50', 'regex:/^[a-z_]+$/'],
            'media_categories.*.label' => ['required', 'string', 'max:100'],
        ]);

        Setting::setCropPresets($validated['crop_presets']);
        Setting::setMediaCategories($validated['media_categories']);

        return redirect()->route('cms.settings.media')
            ->with('success', 'Media settings updated successfully.');
    }

    public function workflows(): Response
    {
        $workflows = Setting::getAllWorkflows();
        $availableRoles = \Spatie\Permission\Models\Role::pluck('name')->toArray();

        return Inertia::render('Settings/Workflow', [
            'workflows' => $workflows,
            'availableRoles' => $availableRoles,
        ]);
    }

    public function storeWorkflow(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'post_type' => ['required', 'string', 'max:50', 'regex:/^[a-z_]+$/'],
            'name' => ['required', 'string', 'max:100'],
            'workflow' => ['required', 'array'],
            'workflow.name' => ['required', 'string', 'max:100'],
            'workflow.states' => ['required', 'array', 'min:2'],
            'workflow.states.*.key' => ['required', 'string', 'max:50'],
            'workflow.states.*.label' => ['required', 'string', 'max:100'],
            'workflow.states.*.color' => ['required', 'string', 'max:50'],
            'workflow.states.*.icon' => ['required', 'string', 'max:100'],
            'workflow.transitions' => ['required', 'array'],
            'workflow.transitions.*.from' => ['required', 'string', 'max:50'],
            'workflow.transitions.*.to' => ['required', 'string', 'max:50'],
            'workflow.transitions.*.roles' => ['required', 'array'],
            'workflow.transitions.*.label' => ['required', 'string', 'max:100'],
            'workflow.publish_roles' => ['required', 'array'],
        ]);

        Setting::setWorkflow($validated['workflow'], $validated['post_type']);

        return redirect()->route('cms.settings.workflows')
            ->with('success', 'Workflow created successfully.');
    }

    public function updateWorkflow(Request $request, string $key): RedirectResponse
    {
        $validated = $request->validate([
            'workflow' => ['required', 'array'],
            'workflow.name' => ['required', 'string', 'max:100'],
            'workflow.states' => ['required', 'array', 'min:2'],
            'workflow.states.*.key' => ['required', 'string', 'max:50'],
            'workflow.states.*.label' => ['required', 'string', 'max:100'],
            'workflow.states.*.color' => ['required', 'string', 'max:50'],
            'workflow.states.*.icon' => ['required', 'string', 'max:100'],
            'workflow.transitions' => ['required', 'array'],
            'workflow.transitions.*.from' => ['required', 'string', 'max:50'],
            'workflow.transitions.*.to' => ['required', 'string', 'max:50'],
            'workflow.transitions.*.roles' => ['required', 'array'],
            'workflow.transitions.*.label' => ['required', 'string', 'max:100'],
            'workflow.publish_roles' => ['required', 'array'],
        ]);

        $postType = $key === 'default' ? null : $key;
        Setting::setWorkflow($validated['workflow'], $postType);

        return redirect()->route('cms.settings.workflows')
            ->with('success', 'Workflow updated successfully.');
    }

    public function destroyWorkflow(string $key): RedirectResponse
    {
        if ($key === 'default') {
            return redirect()->route('cms.settings.workflows')
                ->with('error', 'Cannot delete the default workflow.');
        }

        Setting::deleteWorkflow($key);

        return redirect()->route('cms.settings.workflows')
            ->with('success', 'Workflow deleted successfully.');
    }

    public function sectionCategories(
        SectionRegistry $registry,
        SectionCategoryMappingService $mappingService
    ): Response {
        $sections = collect($registry->all())
            ->map(fn ($section) => [
                'type' => $section->type(),
                'name' => $section->name(),
                'icon' => $section->icon(),
                'description' => $section->description(),
            ])
            ->values()
            ->toArray();

        $mappings = $mappingService->getAllMappingsWithCategories();

        $categories = Category::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('order')
            ->get();

        $flattenCategories = function ($items, $depth = 0) use (&$flattenCategories) {
            $result = [];
            foreach ($items as $item) {
                $result[] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'depth' => $depth,
                ];
                if ($item->children->isNotEmpty()) {
                    $result = array_merge($result, $flattenCategories($item->children, $depth + 1));
                }
            }

            return $result;
        };

        return Inertia::render('Settings/SectionCategories', [
            'sections' => $sections,
            'mappings' => $mappings,
            'categories' => $flattenCategories($categories),
        ]);
    }

    public function updateSectionCategories(
        Request $request,
        SectionCategoryMappingService $mappingService
    ): RedirectResponse {
        $validated = $request->validate([
            'mappings' => ['required', 'array'],
            'mappings.*' => ['array'],
            'mappings.*.*' => ['integer', 'exists:categories,id'],
        ]);

        foreach ($validated['mappings'] as $sectionType => $categoryIds) {
            $mappingService->setAllowedCategories($sectionType, $categoryIds);
        }

        return redirect()->route('cms.settings.section-categories')
            ->with('success', 'Section category restrictions updated successfully.');
    }
}
