{{-- resources/views/components/post/card-recipe.blade.php
     Large recipe card component
     Based on Figma node 158:855
     Specs: bg-off-white, rounded-12px, pb-40px, gap-32px
     Photo: flex-1, p-24px, rounded-t-4px
     Text: px-40px, gap-24px
--}}
@props([
    'image',
    'imageAlt' => '',
    'tags' => [], // e.g., ['recipe', 'best of']
    'category' => null,
    'tag' => null,
    'author' => '',
    'authorUrl' => '#',
    'date' => '',
    'title' => '',
    'description' => '',
    'url' => '#',
    'variant' => 'large', // backwards compatibility
])

@php
    $tagItems = $tags;
    if (empty($tagItems)) {
        if ($category) $tagItems[] = $category;
        if ($tag) $tagItems[] = $tag;
    }
@endphp

{{-- Card: bg-off-white, rounded-12px, pb-40px, gap-32px, flex-col, items-center --}}
<article class="w-full bg-tasty-off-white rounded-[12px] overflow-hidden flex flex-col items-center gap-8 pb-10">

    {{-- Photo Section: flex-1, p-24px, justify-end, items-center --}}
    <a href="{{ $url }}" class="block relative w-full flex-1 min-h-[300px] lg:min-h-[500px] p-6 flex flex-col justify-end items-center hover:opacity-90 transition-opacity duration-200">
        {{-- Background Image --}}
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt ?: $title }}"
            class="absolute inset-0 w-full h-full object-cover object-center rounded-t-[4px]"
        >

        {{-- Tag Badge: bg-light-gray, p-12px, rounded-48px --}}
        @if(count($tagItems) > 0)
            <div class="relative z-10">
                <div class="bg-tasty-light-gray text-tasty-blue-black px-3 py-3 rounded-full inline-flex items-center justify-center gap-2.5 font-sans text-sm uppercase leading-3">
                    @foreach($tagItems as $index => $item)
                        <span>{{ $item }}</span>
                        @if($index < count($tagItems) - 1)
                            <span>•</span>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </a>

    {{-- Text Section: px-40px, gap-24px --}}
    <div class="w-full px-6 lg:px-10 flex flex-col items-center gap-6">
        {{-- Title: H3 style - 48px, center --}}
        <a href="{{ $url }}" class="block w-full text-center hover:opacity-80 transition-opacity">
            <h3 class="text-h3 text-tasty-blue-black text-center">
                {{ $title }}
            </h3>
        </a>

        {{-- Description: body-md, 20px/28px, #202020 --}}
        @if($description)
            <p class="text-body-md text-[#202020] text-center">
                {{ $description }}
            </p>
        @endif

        {{-- Author/Date: 14px, uppercase, gap-20px --}}
        <x-post.author-date
            :author="$author"
            :authorUrl="$authorUrl"
            :date="$date"
            size="sm"
            layout="horizontal"
            align="center"
            color="text-tasty-blue-black"
        />
    </div>
</article>
