{{-- resources/views/components/blocks/code.blade.php --}}
{{-- EditorJS Code Block - Renders safe embeds or displays code --}}

@props([
    'code' => '',
    'language' => null,
])

@php
    $isEmbed = false;
    $embedHtml = null;
    $embedScripts = [];

    // Check if this is a safe embed (Instagram, Twitter/X, TikTok, YouTube, Facebook)
    $safeEmbedPatterns = [
        // Instagram
        'instagram' => [
            'pattern' => '/<blockquote[^>]*class="[^"]*instagram-media[^"]*"[^>]*data-instgrm-permalink="(https:\/\/(www\.)?instagram\.com\/[^"]+)"[^>]*>.*?<\/blockquote>/is',
            'script' => 'https://www.instagram.com/embed.js',
        ],
        // Twitter/X
        'twitter' => [
            'pattern' => '/<blockquote[^>]*class="[^"]*twitter-tweet[^"]*"[^>]*>.*?<\/blockquote>/is',
            'script' => 'https://platform.twitter.com/widgets.js',
        ],
        // TikTok
        'tiktok' => [
            'pattern' => '/<blockquote[^>]*class="[^"]*tiktok-embed[^"]*"[^>]*cite="(https:\/\/(www\.)?tiktok\.com\/[^"]+)"[^>]*>.*?<\/blockquote>/is',
            'script' => 'https://www.tiktok.com/embed.js',
        ],
        // Facebook
        'facebook' => [
            'pattern' => '/<div[^>]*class="[^"]*fb-(post|video)[^"]*"[^>]*data-href="(https:\/\/(www\.)?facebook\.com\/[^"]+)"[^>]*>.*?<\/div>/is',
            'script' => 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v18.0',
        ],
    ];

    foreach ($safeEmbedPatterns as $provider => $config) {
        if (preg_match($config['pattern'], $code, $matches)) {
            $isEmbed = true;
            // Extract just the blockquote/div (without any script tags from original)
            $embedHtml = $matches[0];
            $embedScripts[] = $config['script'];
            break;
        }
    }

    // Also check for YouTube/Vimeo iframes (already safe)
    if (!$isEmbed) {
        $iframePattern = '/<iframe[^>]*src="(https:\/\/(www\.)?(youtube\.com|youtube-nocookie\.com|player\.vimeo\.com)\/[^"]+)"[^>]*>.*?<\/iframe>/is';
        if (preg_match($iframePattern, $code, $matches)) {
            $isEmbed = true;
            // Sanitize iframe - only allow specific attributes
            $embedHtml = preg_replace_callback(
                '/<iframe([^>]*)>/i',
                function($m) {
                    $attrs = $m[1];
                    $allowedAttrs = [];

                    // Extract allowed attributes
                    if (preg_match('/src="([^"]+)"/i', $attrs, $src)) {
                        $allowedAttrs[] = 'src="' . htmlspecialchars($src[1], ENT_QUOTES, 'UTF-8') . '"';
                    }
                    if (preg_match('/width="([^"]+)"/i', $attrs, $w)) {
                        $allowedAttrs[] = 'width="' . htmlspecialchars($w[1], ENT_QUOTES, 'UTF-8') . '"';
                    }
                    if (preg_match('/height="([^"]+)"/i', $attrs, $h)) {
                        $allowedAttrs[] = 'height="' . htmlspecialchars($h[1], ENT_QUOTES, 'UTF-8') . '"';
                    }

                    $allowedAttrs[] = 'frameborder="0"';
                    $allowedAttrs[] = 'allowfullscreen';
                    $allowedAttrs[] = 'class="w-full aspect-video"';

                    return '<iframe ' . implode(' ', $allowedAttrs) . '>';
                },
                $matches[0]
            );
        }
    }
@endphp

@if($isEmbed)
    {{-- Render safe embed --}}
    <div class="embed-container flex justify-center">
        {!! $embedHtml !!}
        @foreach($embedScripts as $script)
            <script async src="{{ $script }}"></script>
        @endforeach
    </div>
@else
    {{-- Display as code block --}}
    <pre class="bg-tasty-light-gray p-6 overflow-x-auto font-mono text-sm text-tasty-blue-black"><code>{{ $code }}</code></pre>
@endif
