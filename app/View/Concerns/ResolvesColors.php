<?php

namespace App\View\Concerns;

use App\View\Colors;

/**
 * Trait for resolving colors in view components.
 *
 * Uses the centralized Colors class for all color lookups.
 *
 * Supports:
 * - Named colors: 'yellow', 'off-white', 'white' (app-specific shortcuts)
 * - Tailwind classes: 'bg-gray-100', 'bg-tasty-yellow' (passed through directly)
 * - Hex colors: '#FFE762', '#fff' (used as inline style)
 * - RGBA colors: 'rgba(255, 231, 98, 0.5)' (used as inline style)
 *
 * @see \App\View\Colors
 */
trait ResolvesColors
{
    /**
     * Resolve a background color value to class and/or style.
     *
     * @return array{class: string, style: string}
     */
    protected function resolveBgColor(string $color, string $default = 'yellow'): array
    {
        // App-specific named color shortcut
        if (Colors::isNamed($color)) {
            return ['class' => Colors::bg($color), 'style' => ''];
        }

        // Tailwind class (bg-*, text-*, etc.) - pass through directly
        if (str_starts_with($color, 'bg-')) {
            return ['class' => $color, 'style' => ''];
        }

        // Hex or rgba - use inline style
        if (Colors::isHex($color) || Colors::isRgb($color)) {
            return ['class' => '', 'style' => "background-color: {$color};"];
        }

        // Fallback to default
        return ['class' => Colors::bg($default), 'style' => ''];
    }

    /**
     * Get hex value for a color.
     */
    protected function resolveHexColor(string $color, string $default = 'yellow'): string
    {
        return Colors::hex($color, $default);
    }

    /**
     * Get rgba value for a color with opacity.
     */
    protected function resolveRgbaColor(string $color, float $opacity = 0.5, string $default = 'yellow'): string
    {
        return Colors::rgba($color, $opacity, $default);
    }

    /**
     * Convert hex to rgba.
     */
    protected function hexToRgba(string $hex, float $opacity = 0.5): string
    {
        return Colors::hexToRgba($hex, $opacity);
    }

    /**
     * Convert rgba to hex (drops alpha).
     */
    protected function rgbaToHex(string $rgba): string
    {
        return Colors::rgbaToHex($rgba);
    }
}
