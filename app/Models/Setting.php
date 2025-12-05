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
}
