<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'settings' => [
                'app_name' => config('app.name'),
                'app_url' => config('app.url'),
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
            'post_types.*.fields.*.type' => ['required', 'string', 'in:text,number,textarea,select,toggle,repeater'],
            'post_types.*.fields.*.suffix' => ['nullable', 'string', 'max:20'],
            'post_types.*.fields.*.options' => ['nullable', 'array'],
        ]);

        Setting::setPostTypes($validated['post_types']);

        return redirect()->route('cms.settings.post-types')
            ->with('success', 'Post types updated successfully.');
    }

    public function update(Request $request): RedirectResponse
    {
        // Settings update logic would go here
        // For now, just redirect back with a success message

        return redirect()->route('cms.settings')
            ->with('success', 'Settings updated successfully.');
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
}
