{{-- resources/views/components/blocks/media-text.blade.php --}}
{{-- Custom Media + Text side-by-side block (from Figma design) --}}
{{-- This is a compound block that combines media and text in a two-column layout --}}

@props([
    'media' => null, // Single media item { url, thumbnail_url, alt_text, caption, is_video }
    'text' => '',
    'mediaPosition' => 'left', // left or right
    'isRtl' => false,
])

@php
    $orderClass = $mediaPosition === 'right' ? 'lg:flex-row-reverse' : '';
@endphp

<div class="flex flex-col lg:flex-row items-center gap-8 lg:gap-10 py-16 {{ $orderClass }}">
    {{-- Media Column --}}
    @if($media)
        <div class="w-full lg:flex-1">
            <figure class="m-0">
                @if($media['is_video'] ?? false)
                    <div class="relative aspect-[3/4] bg-tasty-blue-black overflow-hidden">
                        <img
                            src="{{ $media['thumbnail_url'] ?? '' }}"
                            alt="{{ $media['alt_text'] ?? 'Video' }}"
                            class="w-full h-full object-cover opacity-80"
                        />
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-20 h-20 bg-white/90 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-tasty-blue-black ml-1" fill="currentColor" viewBox="0 0 24 24">
                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                @else
                    <img
                        src="{{ $media['url'] ?? $media['thumbnail_url'] ?? '' }}"
                        alt="{{ $media['alt_text'] ?? '' }}"
                        class="w-full h-auto object-cover"
                    />
                @endif
                @if($media['caption'] ?? null)
                    <figcaption class="text-caption text-tasty-blue-black/50 mt-8 text-center">
                        {{ $media['caption'] }}
                    </figcaption>
                @endif
            </figure>
        </div>
    @endif

    {{-- Text Column --}}
    <div class="w-full lg:flex-1 flex flex-col justify-center">
        <div class="text-body-lg text-tasty-blue-black/90 {{ $isRtl ? 'text-right' : '' }}">
            {!! $text !!}
        </div>
    </div>
</div>
