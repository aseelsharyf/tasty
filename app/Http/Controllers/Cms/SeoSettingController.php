<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\SeoSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeoSettingController extends Controller
{
    /**
     * Display a listing of SEO settings.
     */
    public function index(): Response
    {
        $settings = SeoSetting::query()
            ->with('updatedByUser')
            ->orderBy('page_type')
            ->orderBy('route_name')
            ->get();

        return Inertia::render('Settings/SeoSettings', [
            'settings' => $settings,
            'pageTypes' => $this->getPageTypes(),
            'robotsOptions' => $this->getRobotsOptions(),
            'ogTypes' => $this->getOgTypes(),
            'twitterCardTypes' => $this->getTwitterCardTypes(),
        ]);
    }

    /**
     * Store a new SEO setting.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $validated['updated_by'] = auth()->id();

        SeoSetting::create($validated);

        return redirect()->route('cms.seo-settings.index')
            ->with('success', 'SEO setting created successfully.');
    }

    /**
     * Update the specified SEO setting.
     */
    public function update(Request $request, SeoSetting $seoSetting): RedirectResponse
    {
        $rules = $this->validationRules();
        $rules['route_name'] = ['required', 'string', 'max:100', 'unique:seo_settings,route_name,'.$seoSetting->id];

        $validated = $request->validate($rules);
        $validated['updated_by'] = auth()->id();

        $seoSetting->update($validated);

        return redirect()->route('cms.seo-settings.index')
            ->with('success', 'SEO setting updated successfully.');
    }

    /**
     * Remove the specified SEO setting.
     */
    public function destroy(SeoSetting $seoSetting): RedirectResponse
    {
        $seoSetting->delete();

        return redirect()->route('cms.seo-settings.index')
            ->with('success', 'SEO setting deleted successfully.');
    }

    /**
     * Get validation rules.
     *
     * @return array<string, array<int, string>>
     */
    protected function validationRules(): array
    {
        return [
            'route_name' => ['required', 'string', 'max:100', 'unique:seo_settings,route_name'],
            'page_type' => ['required', 'string', 'in:static,dynamic,archive'],
            'meta_title' => ['nullable', 'array'],
            'meta_title.*' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'array'],
            'meta_description.*' => ['nullable', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'array'],
            'meta_keywords.*' => ['nullable', 'string'],
            'og_title' => ['nullable', 'array'],
            'og_title.*' => ['nullable', 'string', 'max:95'],
            'og_description' => ['nullable', 'array'],
            'og_description.*' => ['nullable', 'string', 'max:200'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'og_type' => ['nullable', 'string', 'max:50'],
            'twitter_card' => ['nullable', 'string', 'in:summary,summary_large_image'],
            'twitter_title' => ['nullable', 'array'],
            'twitter_title.*' => ['nullable', 'string', 'max:70'],
            'twitter_description' => ['nullable', 'array'],
            'twitter_description.*' => ['nullable', 'string', 'max:200'],
            'twitter_image' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'robots' => ['nullable', 'string', 'max:100'],
            'json_ld' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get available page types.
     *
     * @return array<array{value: string, label: string}>
     */
    protected function getPageTypes(): array
    {
        return [
            ['value' => 'static', 'label' => 'Static Page'],
            ['value' => 'dynamic', 'label' => 'Dynamic Page'],
            ['value' => 'archive', 'label' => 'Archive/Listing'],
        ];
    }

    /**
     * Get robots directive options.
     *
     * @return array<array{value: string, label: string}>
     */
    protected function getRobotsOptions(): array
    {
        return [
            ['value' => 'index,follow', 'label' => 'Index, Follow (Default)'],
            ['value' => 'noindex,follow', 'label' => 'No Index, Follow'],
            ['value' => 'index,nofollow', 'label' => 'Index, No Follow'],
            ['value' => 'noindex,nofollow', 'label' => 'No Index, No Follow'],
        ];
    }

    /**
     * Get Open Graph type options.
     *
     * @return array<array{value: string, label: string}>
     */
    protected function getOgTypes(): array
    {
        return [
            ['value' => 'website', 'label' => 'Website'],
            ['value' => 'article', 'label' => 'Article'],
            ['value' => 'profile', 'label' => 'Profile'],
        ];
    }

    /**
     * Get Twitter card type options.
     *
     * @return array<array{value: string, label: string}>
     */
    protected function getTwitterCardTypes(): array
    {
        return [
            ['value' => 'summary', 'label' => 'Summary'],
            ['value' => 'summary_large_image', 'label' => 'Summary Large Image'],
        ];
    }
}
