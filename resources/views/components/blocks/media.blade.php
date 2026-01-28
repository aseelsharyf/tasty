{{-- resources/views/components/blocks/media.blade.php --}}
{{-- EditorJS Media Block - Single image, grid, or carousel --}}

@props([
    'items' => [],
    'layout' => 'single', // single, grid, carousel
    'gridColumns' => 3,
    'gap' => 'md',
    'isRtl' => false,
    'fullWidth' => false, // For breaking out of content container
])

@php
    $items = is_array($items) ? $items : [];
    $gridColumns = min(12, max(1, (int) $gridColumns));

    // Gap classes
    $gapClasses = [
        'none' => 'gap-0',
        'xs' => 'gap-2',
        'sm' => 'gap-4',
        'md' => 'gap-8',
        'lg' => 'gap-10',
        'xl' => 'gap-12',
    ];
    $gapClass = $gapClasses[$gap] ?? 'gap-8';

    // Helper to extract YouTube video ID from various URL formats
    $extractYouTubeId = function($url) {
        if (empty($url)) return null;

        // Match YouTube thumbnail URLs: https://img.youtube.com/vi/VIDEO_ID/...
        if (preg_match('/img\.youtube\.com\/vi\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }
        // Match standard YouTube URLs
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    };

    // Helper to extract Vimeo video ID
    $extractVimeoId = function($url) {
        if (empty($url)) return null;

        // Match Vimeo URLs
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        if (preg_match('/player\.vimeo\.com\/video\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        // Match vumbnail URLs (Vimeo thumbnail service)
        if (preg_match('/vumbnail\.com\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    };

    // Helper to determine video embed URL (as closure to avoid redeclaration)
    $getVideoEmbedUrl = function($item) use ($extractYouTubeId, $extractVimeoId) {
        $type = $item['type'] ?? null;
        $embedProvider = $item['embed_provider'] ?? null;
        $embedVideoId = $item['embed_video_id'] ?? null;
        $thumbnailUrl = $item['thumbnail_url'] ?? null;
        $url = $item['url'] ?? $item['original_url'] ?? null;

        // If we have explicit embed data, use it
        if ($embedVideoId) {
            if ($embedProvider === 'youtube') {
                return "https://www.youtube.com/embed/{$embedVideoId}?rel=0";
            }
            if ($embedProvider === 'vimeo') {
                return "https://player.vimeo.com/video/{$embedVideoId}";
            }
        }

        // Try to extract from thumbnail URL or main URL (for legacy data)
        $youtubeId = $extractYouTubeId($thumbnailUrl) ?? $extractYouTubeId($url);
        if ($youtubeId) {
            return "https://www.youtube.com/embed/{$youtubeId}?rel=0";
        }

        $vimeoId = $extractVimeoId($thumbnailUrl) ?? $extractVimeoId($url);
        if ($vimeoId) {
            return "https://player.vimeo.com/video/{$vimeoId}";
        }

        return null;
    };

    // Helper to get local video URL (as closure to avoid redeclaration)
    $getLocalVideoUrl = function($item) {
        $type = $item['type'] ?? null;
        if ($type === 'video_local') {
            return $item['url'] ?? $item['original_url'] ?? null;
        }
        return null;
    };
@endphp

@if(count($items) > 0)
    @if(count($items) === 1 || $layout === 'single')
        {{-- Single Image/Video Layout --}}
        @php
            $item = $items[0];
            $isVideo = $item['is_video'] ?? false;
            $embedUrl = $isVideo ? $getVideoEmbedUrl($item) : null;
            $localVideoUrl = $isVideo ? $getLocalVideoUrl($item) : null;
            // Get focal point for object-position (defaults to center)
            $focalPoint = $item['focal_point'] ?? null;
            $objectPosition = $focalPoint ? "{$focalPoint['x']}% {$focalPoint['y']}%" : '50% 50%';
        @endphp
        @if($isVideo)
            {{-- Video: Content width, aspect-video --}}
            <figure class="max-w-[894px] w-full mx-auto px-4 lg:px-0">
                <div class="relative aspect-video bg-tasty-blue-black overflow-hidden">
                    @if($embedUrl)
                        {{-- YouTube/Vimeo Embed --}}
                        <iframe
                            src="{{ $embedUrl }}"
                            class="w-full h-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    @elseif($localVideoUrl)
                        {{-- Local Video --}}
                        <video
                            class="w-full h-full"
                            controls
                            playsinline
                            poster="{{ $item['thumbnail_url'] ?? '' }}"
                        >
                            <source src="{{ $localVideoUrl }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    @else
                        {{-- Fallback: just show thumbnail --}}
                        <img
                            src="{{ $item['thumbnail_url'] ?? '' }}"
                            alt="{{ $item['alt_text'] ?? 'Video' }}"
                            class="w-full h-full object-cover"
                        />
                    @endif
                </div>
                @if($item['caption'] ?? null)
                    <figcaption class="text-caption text-tasty-blue-black/40 mt-4 text-left">
                        {{ $item['caption'] }}
                    </figcaption>
                @endif
            </figure>
        @else
            {{-- Image: Full width, 1440x738 on desktop, 560px height on mobile --}}
            <figure class="w-full">
                <div class="h-[560px] lg:h-auto lg:aspect-[1440/738] overflow-hidden">
                    <img
                        src="{{ $item['url'] ?? $item['thumbnail_url'] ?? '' }}"
                        alt="{{ $item['alt_text'] ?? '' }}"
                        class="w-full h-full object-cover"
                        style="object-position: {{ $objectPosition }};"
                    />
                </div>
                @if($item['caption'] ?? null)
                    <figcaption class="max-w-[894px] mx-auto text-caption text-tasty-blue-black/40 mt-4 text-left px-4 lg:px-0">
                        {{ $item['caption'] }}
                    </figcaption>
                @endif
            </figure>
        @endif

    @elseif($layout === 'grid')
        {{-- Grid Layout: 2 photos side by side on desktop (scrollable if >2), stacked on mobile --}}
        {{-- Desktop: 640x712 aspect ratio, Mobile: 353x358 aspect ratio --}}
        <div class="flex flex-col gap-6 lg:hidden">
            {{-- Mobile: Stacked layout --}}
            @foreach($items as $index => $item)
                @php
                    $isVideo = $item['is_video'] ?? false;
                    $embedUrl = $isVideo ? $getVideoEmbedUrl($item) : null;
                    $localVideoUrl = $isVideo ? $getLocalVideoUrl($item) : null;
                @endphp
                <figure class="m-0">
                    @if($isVideo)
                        <div class="relative aspect-video bg-tasty-blue-black overflow-hidden">
                            @if($embedUrl)
                                <iframe
                                    src="{{ $embedUrl }}"
                                    class="w-full h-full"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                ></iframe>
                            @elseif($localVideoUrl)
                                <video
                                    class="w-full h-full"
                                    controls
                                    playsinline
                                    poster="{{ $item['thumbnail_url'] ?? '' }}"
                                >
                                    <source src="{{ $localVideoUrl }}" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            @else
                                <img
                                    src="{{ $item['thumbnail_url'] ?? '' }}"
                                    alt="{{ $item['alt_text'] ?? 'Video' }}"
                                    class="w-full h-full object-cover"
                                />
                            @endif
                        </div>
                    @else
                        <div class="aspect-[640/712] overflow-hidden">
                            <img
                                src="{{ $item['url'] ?? $item['thumbnail_url'] ?? '' }}"
                                alt="{{ $item['alt_text'] ?? '' }}"
                                class="w-full h-full object-cover"
                            />
                        </div>
                    @endif
                    @if($item['caption'] ?? null)
                        <figcaption class="text-caption text-tasty-blue-black/40 mt-4 text-left">
                            {{ $item['caption'] }}
                        </figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
        <div class="hidden lg:block overflow-x-auto scrollbar-hide">
            {{-- Desktop: 2 photos side by side, horizontally scrollable if more --}}
            <div class="flex gap-8" style="width: max-content;">
                @foreach($items as $index => $item)
                    @php
                        $isVideo = $item['is_video'] ?? false;
                        $embedUrl = $isVideo ? $getVideoEmbedUrl($item) : null;
                        $localVideoUrl = $isVideo ? $getLocalVideoUrl($item) : null;
                    @endphp
                    <figure class="m-0 flex-shrink-0 w-[calc(50%-1rem)]" style="min-width: 400px; max-width: 640px;">
                        @if($isVideo)
                            <div class="relative aspect-video bg-tasty-blue-black overflow-hidden">
                                @if($embedUrl)
                                    <iframe
                                        src="{{ $embedUrl }}"
                                        class="w-full h-full"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen
                                    ></iframe>
                                @elseif($localVideoUrl)
                                    <video
                                        class="w-full h-full"
                                        controls
                                        playsinline
                                        poster="{{ $item['thumbnail_url'] ?? '' }}"
                                    >
                                        <source src="{{ $localVideoUrl }}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                @else
                                    <img
                                        src="{{ $item['thumbnail_url'] ?? '' }}"
                                        alt="{{ $item['alt_text'] ?? 'Video' }}"
                                        class="w-full h-full object-cover"
                                    />
                                @endif
                            </div>
                        @else
                            <div class="aspect-[640/712] overflow-hidden">
                                <img
                                    src="{{ $item['url'] ?? $item['thumbnail_url'] ?? '' }}"
                                    alt="{{ $item['alt_text'] ?? '' }}"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                        @endif
                        @if($item['caption'] ?? null)
                            <figcaption class="text-caption text-tasty-blue-black/40 mt-4 text-left">
                                {{ $item['caption'] }}
                            </figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        </div>

    @elseif($layout === 'carousel')
        {{-- Carousel Layout: Full width scrollable on mobile, multi-column on desktop --}}
        <div class="overflow-x-auto scrollbar-hide">
            <div class="flex {{ $gapClass }} pb-4" style="width: max-content;">
                @foreach($items as $index => $item)
                    @php
                        $isVideo = $item['is_video'] ?? false;
                        $embedUrl = $isVideo ? $getVideoEmbedUrl($item) : null;
                        $localVideoUrl = $isVideo ? $getLocalVideoUrl($item) : null;
                    @endphp
                    {{-- Mobile: ~90vw per item (full width with peek), Desktop: based on columns --}}
                    <figure class="m-0 flex-shrink-0 w-[calc(90vw-2rem)] lg:w-[calc(50%-1rem)]" style="min-width: 400px; max-width: 640px;">
                        @if($isVideo)
                            <div class="relative aspect-video bg-tasty-blue-black overflow-hidden">
                                @if($embedUrl)
                                    <iframe
                                        src="{{ $embedUrl }}"
                                        class="w-full h-full"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen
                                    ></iframe>
                                @elseif($localVideoUrl)
                                    <video
                                        class="w-full h-full"
                                        controls
                                        playsinline
                                        poster="{{ $item['thumbnail_url'] ?? '' }}"
                                    >
                                        <source src="{{ $localVideoUrl }}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                @else
                                    <img
                                        src="{{ $item['thumbnail_url'] ?? '' }}"
                                        alt="{{ $item['alt_text'] ?? 'Video' }}"
                                        class="w-full h-full object-cover"
                                    />
                                @endif
                            </div>
                        @else
                            <div class="aspect-[640/712] overflow-hidden">
                                <img
                                    src="{{ $item['url'] ?? $item['thumbnail_url'] ?? '' }}"
                                    alt="{{ $item['alt_text'] ?? '' }}"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                        @endif
                        @if($item['caption'] ?? null)
                            <figcaption class="text-caption text-tasty-blue-black/50 mt-4 text-left">
                                {{ $item['caption'] }}
                            </figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        </div>
    @endif
@endif
