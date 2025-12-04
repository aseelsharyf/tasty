@props([
    'image',
    'imageAlt' => '',
    'tags' => [],
    'category' => null,
    'tag' => null,
    'author' => '',
    'authorUrl' => '#',
    'date' => '',
    'title' => '',
    'description' => '',
    'url' => '#',
    'variant' => 'large',
])

@php
    $tagItems = $tags;
    if (empty($tagItems)) {
        if ($category) $tagItems[] = $category;
        if ($tag) $tagItems[] = $tag;
    }
@endphp

<article class="w-full bg-tasty-off-white rounded-[12px] overflow-hidden flex flex-col items-center gap-8 pb-10">
    <a href="{{ $url }}" class="block relative w-full flex-1 min-h-[300px] lg:min-h-[500px] p-6 flex flex-col justify-end items-center hover:opacity-90 transition-opacity duration-200">
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt ?: $title }}"
            class="absolute inset-0 w-full h-full object-cover object-center rounded-t-[4px]"
        >

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

    <div class="w-full px-6 lg:px-10 flex flex-col items-center gap-6">
        <a href="{{ $url }}" class="block w-full text-center hover:opacity-80 transition-opacity">
            <h3 class="text-h3 text-tasty-blue-black text-center">
                {{ $title }}
            </h3>
        </a>

        @if($description)
            <p class="text-body-md text-[#202020] text-center">
                {{ $description }}
            </p>
        @endif

        <x-content.author-date
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
