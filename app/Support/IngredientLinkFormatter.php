<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class IngredientLinkFormatter
{
    public static function toHtml(string $value): HtmlString
    {
        $html = '';
        $offset = 0;
        preg_match_all('/\[([^\]\r\n]+)\]\(([^)\s]+)\)/u', $value, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $match) {
            [$markdown, $position] = $match[0];
            $label = $match[1][0];
            $url = $match[2][0];

            $html .= e(substr($value, $offset, $position - $offset));

            if (self::isSafeUrl($url)) {
                $externalAttributes = str_starts_with($url, '/')
                    ? ''
                    : ' target="_blank" rel="noopener noreferrer"';
                $html .= '<a class="font-semibold underline decoration-1 underline-offset-3 hover:decoration-2" href="'.e($url).'"'.$externalAttributes.'>'.e($label).'</a>';
            } else {
                $html .= e($markdown);
            }

            $offset = $position + strlen($markdown);
        }

        $html .= e(substr($value, $offset));

        return new HtmlString($html);
    }

    public static function toPlainText(string $value): string
    {
        return preg_replace('/\[([^\]\r\n]+)\]\([^)\s]+\)/u', '$1', $value) ?? $value;
    }

    private static function isSafeUrl(string $url): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array(strtolower((string) $scheme), ['http', 'https'], true)
            && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
