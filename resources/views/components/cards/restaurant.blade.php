{{-- resources/views/components/cards/restaurant.blade.php
     Restaurant review card for On the Menu section
--}}
@props([
    'image',
    'imageAlt' => '',
    'name' => '',
    'tagline' => '',
    'description' => '',
    'rating' => 5,
    'url' => '#',
])

<article class="w-full h-full">
    <a href="{{ $url }}" class="block group">
        {{-- Image Section --}}
        <div class="relative h-[360px] md:h-[460px] rounded-xl overflow-hidden mb-8">
            <img
                src="{{ $image }}"
                alt="{{ $imageAlt }}"
                class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            >
            {{-- Review Badge --}}
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-10">
                <div class="bg-white px-3 py-2.5 rounded-full inline-flex items-center gap-2.5">
                    <span class="font-sans text-sm uppercase leading-3 tracking-wide text-tasty-blue-black">review</span>
                    <span class="font-sans text-sm uppercase leading-3 text-tasty-blue-black">•</span>
                    <x-ui.rating :value="$rating" size="sm" />
                </div>
            </div>
        </div>

        {{-- Content Section --}}
        <div class="text-center">
            <h3 class="font-display text-3xl md:text-5xl text-tasty-blue-black tracking-tight uppercase mb-2 group-hover:opacity-80 transition-opacity">
                {{ $name }}
            </h3>
            <p class="font-display text-xl md:text-3xl text-tasty-blue-black tracking-tight mb-5">
                {{ $tagline }}
            </p>
            <p class="font-sans text-lg md:text-xl text-tasty-blue-black leading-relaxed">
                {{ $description }}
            </p>
        </div>
    </a>
</article>
