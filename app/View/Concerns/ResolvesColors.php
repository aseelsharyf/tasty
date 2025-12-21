<?php

namespace App\View\Concerns;

/**
 * Trait for resolving colors in view components.
 *
 * Supports:
 * - Named colors: 'yellow', 'off-white', 'white' (app-specific shortcuts)
 * - Tailwind classes: 'bg-gray-100', 'bg-tasty-yellow' (passed through directly)
 * - Hex colors: '#FFE762', '#fff' (used as inline style)
 * - RGBA colors: 'rgba(255, 231, 98, 0.5)' (used as inline style)
 */
trait ResolvesColors
{
    /** @var array<string, string> App-specific named colors mapped to Tailwind classes */
    protected static array $namedBgColors = [
        'yellow' => 'bg-tasty-yellow',
        'off-white' => 'bg-tasty-off-white',
        'white' => 'bg-white',
    ];

    /** @var array<string, string> App-specific named colors mapped to hex values */
    protected static array $namedHexColors = [
        'yellow' => '#FFE762',
        'off-white' => '#F8F7F3',
        'white' => '#FFFFFF',
    ];

    /**
     * Resolve a background color value to class and/or style.
     *
     * @return array{class: string, style: string}
     */
    protected function resolveBgColor(string $color, string $default = 'yellow'): array
    {
        // App-specific named color shortcut
        if (isset(self::$namedBgColors[$color])) {
            return ['class' => self::$namedBgColors[$color], 'style' => ''];
        }

        // Tailwind class (bg-*, text-*, etc.) - pass through directly
        if (str_starts_with($color, 'bg-')) {
            return ['class' => $color, 'style' => ''];
        }

        // Hex or rgba - use inline style
        if (str_starts_with($color, '#') || str_starts_with($color, 'rgb')) {
            return ['class' => '', 'style' => "background-color: {$color};"];
        }

        // Fallback to default
        return ['class' => self::$namedBgColors[$default] ?? 'bg-tasty-yellow', 'style' => ''];
    }

    /**
     * Get hex value for a color.
     */
    protected function resolveHexColor(string $color, string $default = 'yellow'): string
    {
        // App-specific named color
        if (isset(self::$namedHexColors[$color])) {
            return self::$namedHexColors[$color];
        }

        // Already hex
        if (str_starts_with($color, '#')) {
            return $color;
        }

        // Extract from rgba
        if (str_starts_with($color, 'rgb')) {
            return $this->rgbaToHex($color);
        }

        // For Tailwind classes, we can't know the hex - use default
        return self::$namedHexColors[$default] ?? '#FFE762';
    }

    /**
     * Get rgba value for a color with opacity.
     */
    protected function resolveRgbaColor(string $color, float $opacity = 0.5, string $default = 'yellow'): string
    {
        // Already rgba - return as-is
        if (str_starts_with($color, 'rgba')) {
            return $color;
        }

        // Hex - convert to rgba
        if (str_starts_with($color, '#')) {
            return $this->hexToRgba($color, $opacity);
        }

        // RGB - add alpha
        if (str_starts_with($color, 'rgb(')) {
            return str_replace('rgb(', 'rgba(', str_replace(')', ", {$opacity})", $color));
        }

        // App-specific named color
        if (isset(self::$namedHexColors[$color])) {
            return $this->hexToRgba(self::$namedHexColors[$color], $opacity);
        }

        // Default
        return $this->hexToRgba(self::$namedHexColors[$default] ?? '#FFE762', $opacity);
    }

    /**
     * Convert hex to rgba.
     */
    protected function hexToRgba(string $hex, float $opacity = 0.5): string
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
    protected function rgbaToHex(string $rgba): string
    {
        if (preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+)/', $rgba, $matches)) {
            return sprintf('#%02X%02X%02X', (int) $matches[1], (int) $matches[2], (int) $matches[3]);
        }

        return '#FFE762';
    }
}
