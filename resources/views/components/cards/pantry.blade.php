{{-- resources/views/components/cards/pantry.blade.php
     Pantry/Product card for Add to Cart section
--}}
@props([
    'image',
    'imageAlt' => '',
    'tags' => [], // e.g., ['pantry', 'appliance']
    'name' => '',
    'description' => '',
    'url' => '#',
])

<article class="bg-tasty-off-white rounded-xl overflow-hidden h-full flex flex-col">
    {{-- Image Section (white background for product photos) --}}
    <a href="{{ $url }}" class="block relative h-[350px] md:h-[400px] bg-white group">
        <div class="absolute inset-0 p-6 flex items-center justify-center">
            <img
                src="{{ $image }}"
                alt="{{ $imageAlt }}"
                class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300"
            >
        </div>
        @if(count($tags) > 0)
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-10">
                <x-ui.tag :items="$tags" />
            </div>
        @endif
    </a>

    {{-- Content Section --}}
    <div class="p-10 text-center flex-1 flex flex-col gap-6">
        <a href="{{ $url }}" class="block">
            <h3 class="font-display text-2xl md:text-3xl text-tasty-blue-black tracking-tight hover:opacity-80 transition-opacity">
                {{ $name }}
            </h3>
        </a>

        <p class="font-sans text-lg md:text-xl text-tasty-blue-black leading-relaxed">
            {{ $description }}
        </p>
    </div>
</article>
