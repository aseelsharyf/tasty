<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "setting.{$key}";

        return Cache::rememberForever($cacheKey, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $value,
            ]
        );

        Cache::forget("setting.{$key}");
    }

    /**
     * Get all settings for a group.
     *
     * @return array<string, mixed>
     */
    public static function getGroup(string $group): array
    {
        $cacheKey = "settings.group.{$group}";

        return Cache::rememberForever($cacheKey, function () use ($group) {
            return static::where('group', $group)
                ->get()
                ->pluck('value', 'key')
                ->all();
        });
    }

    /**
     * Clear cache for a group.
     */
    public static function clearGroupCache(string $group): void
    {
        Cache::forget("settings.group.{$group}");
    }

    /**
     * Get default post types configuration.
     *
     * @return array<int, array{slug: string, name: string, icon: string, fields: array<int, array{name: string, label: string, type: string, suffix?: string, options?: array<string>}>}>
     */
    public static function getDefaultPostTypes(): array
    {
        return [
            [
                'slug' => 'article',
                'name' => 'Article',
                'icon' => 'i-lucide-file-text',
                'is_default' => true,
                'fields' => [],
            ],
            [
                'slug' => 'recipe',
                'name' => 'Recipe',
                'icon' => 'i-lucide-chef-hat',
                'is_default' => true,
                'fields' => [
                    ['name' => 'introduction', 'label' => 'Introduction', 'type' => 'textarea'],
                    ['name' => 'prep_time', 'label' => 'Prep Time', 'type' => 'number', 'suffix' => 'min'],
                    ['name' => 'cook_time', 'label' => 'Cook Time', 'type' => 'number', 'suffix' => 'min'],
                    ['name' => 'servings', 'label' => 'Servings', 'type' => 'number'],
                    ['name' => 'difficulty', 'label' => 'Difficulty', 'type' => 'select', 'options' => ['Easy', 'Medium', 'Hard']],
                    ['name' => 'ingredients', 'label' => 'Ingredients', 'type' => 'grouped-repeater'],
                ],
            ],
            [
                'slug' => 'people',
                'name' => 'People',
                'icon' => 'i-lucide-user',
                'is_default' => true,
                'fields' => [
                    ['name' => 'role', 'label' => 'Role', 'type' => 'text'],
                    ['name' => 'organization', 'label' => 'Organization', 'type' => 'text'],
                    ['name' => 'bio', 'label' => 'Bio', 'type' => 'textarea'],
                    ['name' => 'social_twitter', 'label' => 'Twitter/X', 'type' => 'text'],
                    ['name' => 'social_linkedin', 'label' => 'LinkedIn', 'type' => 'text'],
                ],
            ],
            [
                'slug' => 'restaurant-review',
                'name' => 'Restaurant Review',
                'icon' => 'i-lucide-utensils',
                'is_default' => true,
                'fields' => [
                    ['name' => 'restaurant_name', 'label' => 'Restaurant Name', 'type' => 'text'],
                    ['name' => 'location', 'label' => 'Location', 'type' => 'text'],
                    ['name' => 'cuisine', 'label' => 'Cuisine Type', 'type' => 'text'],
                    ['name' => 'rating', 'label' => 'Rating', 'type' => 'select', 'options' => ['1', '2', '3', '4', '5']],
                    ['name' => 'price_range', 'label' => 'Price Range', 'type' => 'select', 'options' => ['$', '$$', '$$$', '$$$$']],
                ],
            ],
        ];
    }

    /**
     * Get post types from settings, with fallback to defaults.
     *
     * @return array<int, array{slug: string, name: string, icon: string, fields: array}>
     */
    public static function getPostTypes(): array
    {
        return static::get('content.post_types', static::getDefaultPostTypes());
    }

    /**
     * Save post types to settings.
     *
     * @param  array<int, array{slug: string, name: string, icon: string, fields: array}>  $postTypes
     */
    public static function setPostTypes(array $postTypes): void
    {
        static::set('content.post_types', $postTypes, 'content');
    }

    /**
     * Get default crop presets for media.
     *
     * @return array<int, array{name: string, label: string, width: int, height: int}>
     */
    public static function getDefaultCropPresets(): array
    {
        return [
            ['name' => 'thumbnail', 'label' => 'Thumbnail', 'width' => 300, 'height' => 200],
            ['name' => 'medium', 'label' => 'Medium', 'width' => 800, 'height' => 600],
            ['name' => 'large', 'label' => 'Large', 'width' => 1920, 'height' => 1080],
            ['name' => 'social', 'label' => 'Social (OG)', 'width' => 1200, 'height' => 630],
        ];
    }

    /**
     * Get crop presets from settings, with fallback to defaults.
     *
     * @return array<int, array{name: string, label: string, width: int, height: int}>
     */
    public static function getCropPresets(): array
    {
        return static::get('media.crop_presets', static::getDefaultCropPresets());
    }

    /**
     * Save crop presets to settings.
     *
     * @param  array<int, array{name: string, label: string, width: int, height: int}>  $presets
     */
    public static function setCropPresets(array $presets): void
    {
        static::set('media.crop_presets', $presets, 'media');
    }

    /**
     * Get default media categories.
     *
     * @return array<int, array{slug: string, label: string}>
     */
    public static function getDefaultMediaCategories(): array
    {
        return [
            ['slug' => 'media', 'label' => 'Media'],
            ['slug' => 'sponsors', 'label' => 'Sponsors'],
            ['slug' => 'avatars', 'label' => 'Avatars'],
            ['slug' => 'clients', 'label' => 'Clients'],
            ['slug' => 'products', 'label' => 'Products'],
            ['slug' => 'others', 'label' => 'Others'],
        ];
    }

    /**
     * Get media categories from settings with fallback to defaults.
     *
     * @return array<int, array{slug: string, label: string}>
     */
    public static function getMediaCategories(): array
    {
        return static::get('media.categories', static::getDefaultMediaCategories());
    }

    /**
     * Save media categories to settings.
     *
     * @param  array<int, array{slug: string, label: string}>  $categories
     */
    public static function setMediaCategories(array $categories): void
    {
        static::set('media.categories', $categories, 'media');
    }

    /**
     * Get the default workflow configuration.
     *
     * Simplified workflow:
     * - Writer: Draft -> CopyDesk (submit for review)
     * - Editor: CopyDesk -> Reject (back to draft) OR Publish directly
     * - Editor: Can write and publish directly, can always edit even published posts
     *
     * @return array<string, mixed>
     */
    public static function getDefaultWorkflow(): array
    {
        return [
            'name' => 'Default Editorial Workflow',
            'states' => [
                ['key' => 'draft', 'label' => 'Draft', 'color' => 'neutral', 'icon' => 'i-lucide-file-edit'],
                ['key' => 'copydesk', 'label' => 'Copy Desk', 'color' => 'info', 'icon' => 'i-lucide-spell-check'],
                ['key' => 'parked', 'label' => 'Parked', 'color' => 'emerald', 'icon' => 'i-lucide-archive'],
                ['key' => 'scheduled', 'label' => 'Scheduled', 'color' => 'warning', 'icon' => 'i-lucide-calendar-clock'],
                ['key' => 'published', 'label' => 'Published', 'color' => 'success', 'icon' => 'i-lucide-globe'],
            ],
            'transitions' => [
                // Writer submits draft for review
                ['from' => 'draft', 'to' => 'copydesk', 'roles' => ['Writer', 'Editor', 'Admin', 'Developer'], 'label' => 'Send to Copy Desk'],
                // ['from' => 'copydesk', 'to' => 'draft', 'roles' => ['Writer'], 'label' => 'Withdraw'],
                // Editor rejects back to draft
                ['from' => 'copydesk', 'to' => 'draft', 'roles' => ['Editor', 'Admin', 'Developer'], 'label' => 'Reject'],
                // Editor parks (approved, banked for later)
                ['from' => 'copydesk', 'to' => 'parked', 'roles' => ['Editor', 'Admin', 'Developer'], 'label' => 'Park'],
                // Editor publishes from copydesk
                ['from' => 'copydesk', 'to' => 'published', 'roles' => ['Editor', 'Admin', 'Developer'], 'label' => 'Publish'],
                // Editor schedules from copydesk
                ['from' => 'copydesk', 'to' => 'scheduled', 'roles' => ['Editor', 'Admin', 'Developer'], 'label' => 'Schedule'],
                // Editor publishes a parked post
                ['from' => 'parked', 'to' => 'published', 'roles' => ['Editor', 'Admin', 'Developer'], 'label' => 'Publish'],
                // Editor sends parked post back to draft
                ['from' => 'parked', 'to' => 'draft', 'roles' => ['Editor', 'Admin', 'Developer'], 'label' => 'Send Back'],
                // ['from' => 'draft', 'to' => 'published', 'roles' => ['Editor', 'Admin', 'Developer'], 'label' => 'Publish'],
                // Unpublish goes to copydesk (not draft)
                ['from' => 'published', 'to' => 'copydesk', 'roles' => ['Editor', 'Admin', 'Developer'], 'label' => 'Unpublish'],
                // Scheduled post actions
                ['from' => 'scheduled', 'to' => 'copydesk', 'roles' => ['Editor', 'Admin', 'Developer'], 'label' => 'Unschedule'],
                ['from' => 'scheduled', 'to' => 'published', 'roles' => ['Editor', 'Admin', 'Developer'], 'label' => 'Publish Now'],
                // Legacy: handle old 'review' status
                ['from' => 'review', 'to' => 'copydesk', 'roles' => ['Editor', 'Admin', 'Developer'], 'label' => 'Send to Copy Desk'],
            ],
            'publish_roles' => ['Editor', 'Admin', 'Developer'],
            'edit_published_roles' => ['Editor', 'Admin', 'Developer'],
        ];
    }

    /**
     * Get workflow configuration for a specific content type, with fallback to default.
     *
     * @return array<string, mixed>
     */
    public static function getWorkflow(?string $postType = null): array
    {
        // Check for post-type specific workflow first
        if ($postType) {
            $typeWorkflow = static::get("workflow.post_type.{$postType}");
            if ($typeWorkflow) {
                return $typeWorkflow;
            }
        }

        // Fall back to default workflow
        return static::get('workflow.default', static::getDefaultWorkflow());
    }

    /**
     * Set workflow configuration for a specific content type.
     *
     * @param  array<string, mixed>  $workflow
     */
    public static function setWorkflow(array $workflow, ?string $postType = null): void
    {
        $key = $postType ? "workflow.post_type.{$postType}" : 'workflow.default';
        static::set($key, $workflow, 'workflow');
    }

    /**
     * Get all workflow configurations.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getAllWorkflows(): array
    {
        $workflows = [];

        // Get the default workflow
        $workflows['default'] = static::get('workflow.default', static::getDefaultWorkflow());

        // Get all post-type specific workflows
        $postTypes = static::getPostTypes();
        foreach ($postTypes as $postType) {
            $typeWorkflow = static::get("workflow.post_type.{$postType['slug']}");
            if ($typeWorkflow) {
                $workflows[$postType['slug']] = $typeWorkflow;
            }
        }

        return $workflows;
    }

    /**
     * Delete a workflow configuration for a specific post type.
     * (Will fall back to default workflow)
     */
    public static function deleteWorkflow(string $postType): void
    {
        $key = "workflow.post_type.{$postType}";
        static::where('key', $key)->delete();
        Cache::forget("setting.{$key}");
    }
}
