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
@endphp

@if(count($items) > 0)
    @if(count($items) === 1 || $layout === 'single')
        {{-- Single Image Layout --}}
        @php $item = $items[0]; @endphp
        <figure class="{{ $fullWidth ? 'w-full' : '' }}">
            @if($item['is_video'] ?? false)
                <div class="relative aspect-video bg-tasty-blue-black overflow-hidden">
                    <img
                        src="{{ $item['thumbnail_url'] ?? '' }}"
                        alt="{{ $item['alt_text'] ?? 'Video' }}"
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
                    src="{{ $item['url'] ?? $item['thumbnail_url'] ?? '' }}"
                    alt="{{ $item['alt_text'] ?? '' }}"
                    class="w-full h-auto object-cover"
                />
            @endif
            @if($item['caption'] ?? null)
                <figcaption class="text-caption text-tasty-blue-black/50 mt-8 text-center">
                    {{ $item['caption'] }}
                </figcaption>
            @endif
        </figure>

    @elseif($layout === 'grid')
        {{-- Grid Layout --}}
        <div class="grid {{ $gapClass }}" style="grid-template-columns: repeat({{ $gridColumns }}, minmax(0, 1fr));">
            @foreach($items as $item)
                <figure class="m-0">
                    @if($item['is_video'] ?? false)
                        <div class="relative aspect-video bg-tasty-blue-black overflow-hidden">
                            <img
                                src="{{ $item['thumbnail_url'] ?? '' }}"
                                alt="{{ $item['alt_text'] ?? 'Video' }}"
                                class="w-full h-full object-cover opacity-80"
                            />
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-16 h-16 bg-white/90 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-tasty-blue-black ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                        <polygon points="5 3 19 12 5 21 5 3"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    @else
                        <img
                            src="{{ $item['url'] ?? $item['thumbnail_url'] ?? '' }}"
                            alt="{{ $item['alt_text'] ?? '' }}"
                            class="w-full h-full object-cover"
                        />
                    @endif
                    @if($item['caption'] ?? null)
                        <figcaption class="text-caption text-tasty-blue-black/50 mt-8 text-center">
                            {{ $item['caption'] }}
                        </figcaption>
                    @endif
                </figure>
            @endforeach
        </div>

    @elseif($layout === 'carousel')
        {{-- Carousel Layout --}}
        <div class="overflow-x-auto scrollbar-hide">
            <div class="flex {{ $gapClass }} pb-4" style="width: max-content;">
                @foreach($items as $item)
                    <figure class="m-0 flex-shrink-0" style="width: calc({{ 100 / $gridColumns }}vw - 2rem); min-width: 200px; max-width: 500px;">
                        @if($item['is_video'] ?? false)
                            <div class="relative aspect-video bg-tasty-blue-black overflow-hidden">
                                <img
                                    src="{{ $item['thumbnail_url'] ?? '' }}"
                                    alt="{{ $item['alt_text'] ?? 'Video' }}"
                                    class="w-full h-full object-cover opacity-80"
                                />
                            </div>
                        @else
                            <img
                                src="{{ $item['url'] ?? $item['thumbnail_url'] ?? '' }}"
                                alt="{{ $item['alt_text'] ?? '' }}"
                                class="w-full aspect-[4/3] object-cover"
                            />
                        @endif
                        @if($item['caption'] ?? null)
                            <figcaption class="text-caption text-tasty-blue-black/50 mt-4 text-center">
                                {{ $item['caption'] }}
                            </figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        </div>
    @endif
@endif
