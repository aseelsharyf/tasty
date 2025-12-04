{{-- resources/views/components/cards/news.blade.php
     News card for Latest Updates section
     Supports 'large' and 'small' sizes
--}}
@props([
    'image',
    'imageAlt' => '',
    'tags' => [], // e.g., ['latest', 'event']
    'author' => '',
    'authorUrl' => '#',
    'date' => '',
    'title' => '',
    'description' => null,
    'url' => '#',
    'size' => 'large', // 'large' or 'small'
])

@php
    $isLarge = $size === 'large';
    $containerClass = $isLarge ? 'flex-col' : 'flex-row gap-5';
    $imageClass = $isLarge ? 'h-[300px] md:h-[400px] rounded-xl' : 'w-[120px] h-[120px] md:w-[160px] md:h-[160px] rounded-lg shrink-0';
    $titleClass = $isLarge ? 'text-2xl md:text-4xl' : 'text-lg md:text-xl';
@endphp

<article class="w-full">
    <a href="{{ $url }}" class="block group">
        <div class="flex {{ $containerClass }}">
            {{-- Image --}}
            <div class="relative {{ $imageClass }} overflow-hidden">
                <img
                    src="{{ $image }}"
                    alt="{{ $imageAlt }}"
                    class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                >
                @if($isLarge && count($tags) > 0)
                    <div class="absolute bottom-4 left-4 z-10">
                        <x-ui.tag :items="$tags" />
                    </div>
                @endif
            </div>

            {{-- Content --}}
            <div class="flex flex-col gap-3 {{ $isLarge ? 'mt-5' : '' }}">
                @if(!$isLarge && count($tags) > 0)
                    <x-ui.tag :items="$tags" />
                @endif

                <h3 class="font-display {{ $titleClass }} text-tasty-blue-black tracking-tight leading-tight group-hover:opacity-80 transition-opacity">
                    {{ $title }}
                </h3>

                @if($description && $isLarge)
                    <p class="font-sans text-base md:text-lg text-tasty-blue-black leading-relaxed">
                        {{ $description }}
                    </p>
                @endif

                <div class="flex items-center gap-3 text-xs uppercase tracking-wider text-tasty-blue-black">
                    <span class="underline underline-offset-4">BY {{ $author }}</span>
                    <span>•</span>
                    <span>{{ strtoupper($date) }}</span>
                </div>
            </div>
        </div>
    </a>
</article>
