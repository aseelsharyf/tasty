{{-- resources/views/components/cards/recipe.blade.php
     Recipe card for the Everyday Cooking section
--}}
@props([
    'image',
    'imageAlt' => '',
    'tags' => [], // e.g., ['recipe', 'vegan']
    'author' => '',
    'authorUrl' => '#',
    'date' => '',
    'title' => '',
    'description' => null,
    'url' => '#',
    'size' => 'default', // 'default' or 'featured'
])

@php
    $imageHeight = $size === 'featured' ? 'h-[400px] md:h-[599px]' : 'h-[300px] md:h-[400px]';
    $titleSize = $size === 'featured' ? 'text-3xl md:text-5xl' : 'text-2xl md:text-4xl';
    $padding = $size === 'featured' ? 'p-10' : 'p-8';
@endphp

<article class="bg-tasty-off-white rounded-xl overflow-hidden h-full flex flex-col">
    {{-- Image Section --}}
    <a href="{{ $url }}" class="block relative {{ $imageHeight }} group">
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        >
        @if(count($tags) > 0)
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-10">
                <x-ui.tag :items="$tags" />
            </div>
        @endif
    </a>

    {{-- Content Section --}}
    <div class="{{ $padding }} text-center flex-1 flex flex-col justify-between">
        <div>
            <a href="{{ $url }}" class="block">
                <h3 class="font-display {{ $titleSize }} text-tasty-blue-black tracking-tight mb-4 hover:opacity-80 transition-opacity">
                    {{ $title }}
                </h3>
            </a>

            @if($description)
                <p class="font-sans text-lg md:text-xl text-tasty-blue-black leading-relaxed mb-6">
                    {{ $description }}
                </p>
            @endif
        </div>

        <div class="flex items-center justify-center gap-4 text-sm uppercase tracking-wider text-tasty-blue-black">
            <a href="{{ $authorUrl }}" class="underline underline-offset-4 hover:opacity-80">BY {{ $author }}</a>
            <span>•</span>
            <span>{{ strtoupper($date) }}</span>
        </div>
    </div>
</article>
