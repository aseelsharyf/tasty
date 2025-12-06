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
                'fields' => [],
            ],
            [
                'slug' => 'recipe',
                'name' => 'Recipe',
                'icon' => 'i-lucide-chef-hat',
                'fields' => [
                    ['name' => 'prep_time', 'label' => 'Prep Time', 'type' => 'number', 'suffix' => 'min'],
                    ['name' => 'cook_time', 'label' => 'Cook Time', 'type' => 'number', 'suffix' => 'min'],
                    ['name' => 'servings', 'label' => 'Servings', 'type' => 'number'],
                    ['name' => 'difficulty', 'label' => 'Difficulty', 'type' => 'select', 'options' => ['Easy', 'Medium', 'Hard']],
                    ['name' => 'ingredients', 'label' => 'Ingredients', 'type' => 'repeater'],
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
     * Get the default workflow configuration.
     *
     * @return array<string, mixed>
     */
    public static function getDefaultWorkflow(): array
    {
        return [
            'name' => 'Default Editorial Workflow',
            'states' => [
                ['key' => 'draft', 'label' => 'Draft', 'color' => 'neutral', 'icon' => 'i-lucide-file-edit'],
                ['key' => 'review', 'label' => 'Editorial Review', 'color' => 'warning', 'icon' => 'i-lucide-eye'],
                ['key' => 'copydesk', 'label' => 'Copy Desk', 'color' => 'info', 'icon' => 'i-lucide-spell-check'],
                ['key' => 'approved', 'label' => 'Approved', 'color' => 'success', 'icon' => 'i-lucide-check-circle'],
                ['key' => 'rejected', 'label' => 'Needs Revision', 'color' => 'error', 'icon' => 'i-lucide-alert-circle'],
                ['key' => 'published', 'label' => 'Published', 'color' => 'primary', 'icon' => 'i-lucide-globe'],
            ],
            'transitions' => [
                ['from' => 'draft', 'to' => 'review', 'roles' => ['Writer', 'Editor', 'Admin'], 'label' => 'Submit for Review'],
                ['from' => 'review', 'to' => 'copydesk', 'roles' => ['Editor', 'Admin'], 'label' => 'Send to Copy Desk'],
                ['from' => 'review', 'to' => 'rejected', 'roles' => ['Editor', 'Admin'], 'label' => 'Request Revisions'],
                ['from' => 'copydesk', 'to' => 'approved', 'roles' => ['Editor', 'Admin'], 'label' => 'Approve'],
                ['from' => 'copydesk', 'to' => 'rejected', 'roles' => ['Editor', 'Admin'], 'label' => 'Request Revisions'],
                ['from' => 'rejected', 'to' => 'review', 'roles' => ['Writer', 'Editor', 'Admin'], 'label' => 'Resubmit'],
                ['from' => 'approved', 'to' => 'published', 'roles' => ['Editor', 'Admin'], 'label' => 'Publish'],
                ['from' => 'published', 'to' => 'draft', 'roles' => ['Editor', 'Admin'], 'label' => 'Unpublish'],
            ],
            'publish_roles' => ['Editor', 'Admin'],
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
