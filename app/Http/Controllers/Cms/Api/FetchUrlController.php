<?php

namespace App\Http\Controllers\Cms\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FetchUrlController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'url'],
        ]);

        $url = $request->input('url');

        try {
            $response = Http::timeout(10)
                ->withUserAgent('Mozilla/5.0 (compatible; TastyBot/1.0)')
                ->get($url);

            if (! $response->successful()) {
                return response()->json([
                    'success' => 0,
                    'message' => 'Failed to fetch URL',
                ]);
            }

            $html = $response->body();
            $meta = $this->parseMetaTags($html, $url);

            return response()->json([
                'success' => 1,
                'link' => $url,
                'meta' => $meta,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 0,
                'message' => 'Failed to fetch URL: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Parse meta tags from HTML content.
     *
     * @return array{title: string, description: string, image: array{url: string}|null}
     */
    private function parseMetaTags(string $html, string $url): array
    {
        $meta = [
            'title' => '',
            'description' => '',
            'image' => null,
        ];

        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument;
        $dom->loadHTML($html);

        libxml_clear_errors();

        // Get title - prefer og:title, then twitter:title, then <title>
        $meta['title'] = $this->getMetaContent($dom, 'og:title')
            ?? $this->getMetaContent($dom, 'twitter:title')
            ?? $this->getTitleTag($dom)
            ?? parse_url($url, PHP_URL_HOST) ?? '';

        // Get description - prefer og:description, then twitter:description, then meta description
        $meta['description'] = $this->getMetaContent($dom, 'og:description')
            ?? $this->getMetaContent($dom, 'twitter:description')
            ?? $this->getMetaContent($dom, 'description', 'name')
            ?? '';

        // Get image - prefer og:image, then twitter:image
        $imageUrl = $this->getMetaContent($dom, 'og:image')
            ?? $this->getMetaContent($dom, 'twitter:image');

        if ($imageUrl) {
            // Make relative URLs absolute
            if (! str_starts_with($imageUrl, 'http')) {
                $parsed = parse_url($url);
                $baseUrl = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');
                $imageUrl = $baseUrl.'/'.ltrim($imageUrl, '/');
            }
            $meta['image'] = ['url' => $imageUrl];
        }

        return $meta;
    }

    private function getMetaContent(\DOMDocument $dom, string $property, string $attribute = 'property'): ?string
    {
        $xpath = new \DOMXPath($dom);

        // Try property attribute (for og: tags)
        $nodes = $xpath->query("//meta[@{$attribute}='{$property}']/@content");
        if ($nodes && $nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue ?? '');
        }

        // Also try name attribute for compatibility
        if ($attribute === 'property') {
            $nodes = $xpath->query("//meta[@name='{$property}']/@content");
            if ($nodes && $nodes->length > 0) {
                return trim($nodes->item(0)->nodeValue ?? '');
            }
        }

        return null;
    }

    private function getTitleTag(\DOMDocument $dom): ?string
    {
        $titles = $dom->getElementsByTagName('title');
        if ($titles->length > 0) {
            return trim($titles->item(0)->textContent ?? '');
        }

        return null;
    }
}
