<?php

namespace App\Services;

class PostTemplateRegistry
{
    /**
     * @var array<string, array{key: string, name: string, description: string, icon: string, preview_component: string}>
     */
    protected static array $templates = [];

    protected static bool $initialized = false;

    /**
     * Initialize default templates.
     */
    protected static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Register default templates
        self::register([
            'key' => 'default',
            'name' => 'Default',
            'description' => 'Clean, readable layout with centered content',
            'icon' => 'i-lucide-layout-template',
            'preview_component' => 'post-template-default',
        ]);

        self::register([
            'key' => 'feature',
            'name' => 'Feature Story',
            'description' => 'Full-width hero image with immersive reading experience',
            'icon' => 'i-lucide-image',
            'preview_component' => 'post-template-feature',
        ]);

        self::register([
            'key' => 'minimal',
            'name' => 'Minimal',
            'description' => 'Typography-focused layout without distractions',
            'icon' => 'i-lucide-type',
            'preview_component' => 'post-template-minimal',
        ]);

        self::$initialized = true;
    }

    /**
     * Register a new template.
     *
     * @param  array{key: string, name: string, description: string, icon: string, preview_component: string}  $template
     */
    public static function register(array $template): void
    {
        self::$templates[$template['key']] = $template;
    }

    /**
     * Get all registered templates.
     *
     * @return array<string, array{key: string, name: string, description: string, icon: string, preview_component: string}>
     */
    public static function all(): array
    {
        self::initialize();

        return self::$templates;
    }

    /**
     * Get a specific template by key.
     *
     * @return array{key: string, name: string, description: string, icon: string, preview_component: string}|null
     */
    public static function get(string $key): ?array
    {
        self::initialize();

        return self::$templates[$key] ?? null;
    }

    /**
     * Get the default template key.
     */
    public static function getDefault(): string
    {
        return 'default';
    }

    /**
     * Check if a template exists.
     */
    public static function exists(string $key): bool
    {
        self::initialize();

        return isset(self::$templates[$key]);
    }

    /**
     * Get templates formatted for frontend select.
     *
     * @return array<int, array{key: string, name: string, description: string, icon: string}>
     */
    public static function forSelect(): array
    {
        self::initialize();

        return array_values(array_map(fn ($t) => [
            'key' => $t['key'],
            'name' => $t['name'],
            'description' => $t['description'],
            'icon' => $t['icon'],
        ], self::$templates));
    }
}
