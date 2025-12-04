{{-- resources/views/components/cards/article.blade.php
     Versatile article card for spread/feature sections
     Supports image top or bottom layout
--}}
@props([
    'image',
    'imageAlt' => '',
    'tags' => [], // e.g., ['the spread', 'on ingredients']
    'author' => '',
    'authorUrl' => '#',
    'date' => '',
    'title' => '',
    'description' => '',
    'url' => '#',
    'layout' => 'image-top', // 'image-top' or 'image-bottom'
])

<article class="w-full flex flex-col gap-8 h-full">
    @if($layout === 'image-top')
        {{-- Image Section --}}
        <a href="{{ $url }}" class="block flex-1 min-h-[300px] md:min-h-[400px] rounded-xl overflow-hidden relative group">
            <img
                src="{{ $image }}"
                alt="{{ $imageAlt }}"
                class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            >
            @if(count($tags) > 0)
                <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-10">
                    <x-ui.tag :items="$tags" bgColor="bg-white" />
                </div>
            @endif
        </a>

        {{-- Content Section --}}
        <div class="flex flex-col gap-6">
            <a href="{{ $url }}" class="block">
                <h3 class="font-display text-3xl md:text-5xl text-tasty-blue-black tracking-tight leading-tight hover:opacity-80 transition-opacity">
                    {{ $title }}
                </h3>
            </a>

            @if($description)
                <p class="font-sans text-lg md:text-xl text-tasty-blue-black leading-relaxed">
                    {{ $description }}
                </p>
            @endif

            <div class="flex items-center gap-5 text-sm uppercase tracking-wider text-tasty-blue-black">
                <a href="{{ $authorUrl }}" class="underline underline-offset-4 hover:opacity-80">BY {{ $author }}</a>
                <span>•</span>
                <span>{{ strtoupper($date) }}</span>
            </div>
        </div>
    @else
        {{-- Content Section First --}}
        <div class="flex flex-col gap-6">
            <a href="{{ $url }}" class="block">
                <h3 class="font-display text-3xl md:text-5xl text-tasty-blue-black tracking-tight leading-tight hover:opacity-80 transition-opacity">
                    {{ $title }}
                </h3>
            </a>

            @if($description)
                <p class="font-sans text-lg md:text-xl text-tasty-blue-black leading-relaxed">
                    {{ $description }}
                </p>
            @endif

            <div class="flex items-center gap-5 text-sm uppercase tracking-wider text-tasty-blue-black">
                <a href="{{ $authorUrl }}" class="underline underline-offset-4 hover:opacity-80">BY {{ $author }}</a>
                <span>•</span>
                <span>{{ strtoupper($date) }}</span>
            </div>
        </div>

        {{-- Image Section --}}
        <a href="{{ $url }}" class="block flex-1 min-h-[300px] md:min-h-[400px] rounded-xl overflow-hidden relative group">
            <img
                src="{{ $image }}"
                alt="{{ $imageAlt }}"
                class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            >
            @if(count($tags) > 0)
                <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-10">
                    <x-ui.tag :items="$tags" bgColor="bg-white" />
                </div>
            @endif
        </a>
    @endif
</article>
