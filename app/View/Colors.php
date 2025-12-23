<?php

namespace App\View;

/**
 * Centralized color configuration for the application.
 *
 * All colors defined in app.css @theme and used across components
 * are mapped here for consistency.
 *
 * @see resources/css/app.css for Tailwind theme color definitions
 */
final class Colors
{
    /**
     * Primary brand colors from Figma/app.css.
     *
     * These map to CSS custom properties defined in @theme.
     *
     * @var array<string, array{hex: string, tailwind: string, css_var: string}>
     */
    public const BRAND = [
        'yellow' => [
            'hex' => '#FFE762',
            'tailwind' => 'tasty-yellow',
            'css_var' => '--color-tasty-yellow',
        ],
        'blue-black' => [
            'hex' => '#0A0924',
            'tailwind' => 'blue-black',
            'css_var' => '--color-blue-black',
        ],
        'off-white' => [
            'hex' => '#F7F7F7',
            'tailwind' => 'off-white',
            'css_var' => '--color-off-white',
        ],
        'light-gray' => [
            'hex' => '#F2F2F2',
            'tailwind' => 'light-gray',
            'css_var' => '--color-light-gray',
        ],
        'pure-white' => [
            'hex' => '#FFFFFF',
            'tailwind' => 'tasty-pure-white',
            'css_var' => '--color-tasty-pure-white',
        ],
        'black' => [
            'hex' => '#000000',
            'tailwind' => 'tasty-black',
            'css_var' => '--color-tasty-black',
        ],
    ];

    /**
     * Semantic/shortcut color names to brand color mappings.
     *
     * These allow using simple names like 'yellow' or 'white' in components.
     *
     * @var array<string, string>
     */
    public const ALIASES = [
        'yellow' => 'yellow',
        'white' => 'pure-white',
        'off-white' => 'off-white',
        'gray' => 'light-gray',
        'light-gray' => 'light-gray',
        'black' => 'black',
        'blue-black' => 'blue-black',
    ];

    /**
     * Background color Tailwind classes for named colors.
     *
     * @var array<string, string>
     */
    public const BG_CLASSES = [
        'yellow' => 'bg-tasty-yellow',
        'off-white' => 'bg-off-white',
        'white' => 'bg-white',
        'light-gray' => 'bg-light-gray',
        'gray' => 'bg-light-gray',
        'black' => 'bg-tasty-black',
        'blue-black' => 'bg-blue-black',
    ];

    /**
     * Text color Tailwind classes for named colors.
     *
     * @var array<string, string>
     */
    public const TEXT_CLASSES = [
        'yellow' => 'text-tasty-yellow',
        'off-white' => 'text-off-white',
        'white' => 'text-white',
        'light-gray' => 'text-light-gray',
        'gray' => 'text-light-gray',
        'black' => 'text-tasty-black',
        'blue-black' => 'text-blue-black',
    ];

    /**
     * Border color Tailwind classes for named colors.
     *
     * @var array<string, string>
     */
    public const BORDER_CLASSES = [
        'yellow' => 'border-tasty-yellow',
        'off-white' => 'border-off-white',
        'white' => 'border-white',
        'light-gray' => 'border-light-gray',
        'gray' => 'border-light-gray',
        'black' => 'border-tasty-black',
        'blue-black' => 'border-blue-black',
    ];

    /**
     * Hex values for named colors (for inline styles, gradients, etc.).
     *
     * @var array<string, string>
     */
    public const HEX = [
        'yellow' => '#FFE762',
        'off-white' => '#F7F7F7',
        'white' => '#FFFFFF',
        'light-gray' => '#F2F2F2',
        'gray' => '#F2F2F2',
        'black' => '#000000',
        'blue-black' => '#0A0924',
    ];

    /**
     * Get the background class for a color name.
     *
     * Accepts named colors ('yellow', 'white') or Tailwind classes ('bg-gray-100').
     * Tailwind classes are passed through directly.
     */
    public static function bg(string $color, string $default = 'yellow'): string
    {
        // Already a Tailwind bg class - pass through
        if (str_starts_with($color, 'bg-')) {
            return $color;
        }

        // Named color lookup
        return self::BG_CLASSES[$color] ?? self::BG_CLASSES[$default] ?? 'bg-tasty-yellow';
    }

    /**
     * Get the text class for a color name.
     *
     * Accepts named colors ('yellow', 'white') or Tailwind classes ('text-gray-100').
     * Tailwind classes are passed through directly.
     */
    public static function text(string $color, string $default = 'blue-black'): string
    {
        // Already a Tailwind text class - pass through
        if (str_starts_with($color, 'text-')) {
            return $color;
        }

        // Named color lookup
        return self::TEXT_CLASSES[$color] ?? self::TEXT_CLASSES[$default] ?? 'text-blue-black';
    }

    /**
     * Get the border class for a color name.
     *
     * Accepts named colors ('yellow', 'white') or Tailwind classes ('border-gray-100').
     * Tailwind classes are passed through directly.
     */
    public static function border(string $color, string $default = 'gray'): string
    {
        // Already a Tailwind border class - pass through
        if (str_starts_with($color, 'border-')) {
            return $color;
        }

        // Named color lookup
        return self::BORDER_CLASSES[$color] ?? self::BORDER_CLASSES[$default] ?? 'border-light-gray';
    }

    /**
     * Get the hex value for a color name.
     */
    public static function hex(string $color, string $default = 'yellow'): string
    {
        // Already hex
        if (str_starts_with($color, '#')) {
            return $color;
        }

        return self::HEX[$color] ?? self::HEX[$default] ?? '#FFE762';
    }

    /**
     * Get rgba value for a color with opacity.
     */
    public static function rgba(string $color, float $opacity = 0.5, string $default = 'yellow'): string
    {
        // Already rgba - return as-is
        if (str_starts_with($color, 'rgba')) {
            return $color;
        }

        // RGB - add alpha
        if (str_starts_with($color, 'rgb(')) {
            return str_replace('rgb(', 'rgba(', str_replace(')', ", {$opacity})", $color));
        }

        $hex = self::hex($color, $default);

        return self::hexToRgba($hex, $opacity);
    }

    /**
     * Check if a value is a named color.
     */
    public static function isNamed(string $color): bool
    {
        return isset(self::BG_CLASSES[$color]);
    }

    /**
     * Check if a value is a Tailwind class.
     */
    public static function isTailwindClass(string $color): bool
    {
        return str_starts_with($color, 'bg-')
            || str_starts_with($color, 'text-')
            || str_starts_with($color, 'border-');
    }

    /**
     * Check if a value is a hex color.
     */
    public static function isHex(string $color): bool
    {
        return str_starts_with($color, '#');
    }

    /**
     * Check if a value is an RGB/RGBA color.
     */
    public static function isRgb(string $color): bool
    {
        return str_starts_with($color, 'rgb');
    }

    /**
     * Convert hex to rgba.
     */
    public static function hexToRgba(string $hex, float $opacity = 0.5): string
    {
        $hex = ltrim($hex, '#');

        // Handle 3-char hex
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "rgba({$r}, {$g}, {$b}, {$opacity})";
    }

    /**
     * Convert rgba to hex (drops alpha).
     */
    public static function rgbaToHex(string $rgba): string
    {
        if (preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+)/', $rgba, $matches)) {
            return sprintf('#%02X%02X%02X', (int) $matches[1], (int) $matches[2], (int) $matches[3]);
        }

        return '#FFE762';
    }

    /**
     * Get all available named colors.
     *
     * @return array<string>
     */
    public static function names(): array
    {
        return array_keys(self::BG_CLASSES);
    }
}
