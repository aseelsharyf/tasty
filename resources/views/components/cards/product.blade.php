<a href="{{ $url }}" class="group flex flex-col bg-off-white rounded-xl overflow-hidden p-1 pb-8 w-[426px] aspect-[426/626] max-lg:w-full max-lg:aspect-auto max-lg:h-auto">
    {{-- Image container: fixed proportion of card height --}}
    <div class="relative h-[55%] max-lg:h-[250px] bg-white rounded-lg flex items-end justify-center p-6 mb-6">
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            class="absolute inset-0 w-full h-full object-contain p-5"
        >
        @if(count($tags) > 0)
            <span class="tag relative z-10">{{ implode(' • ', $tags) }}</span>
        @endif
    </div>

    {{-- Content --}}
    <div class="flex flex-col items-center gap-4 px-8 max-lg:px-6 text-center flex-1">
        {{-- Store Logo --}}
        @if($storeLogo)
            <img
                src="{{ $storeLogo }}"
                alt="{{ $storeName ?? 'Store' }}"
                class="max-w-[80px] max-h-[32px] object-contain grayscale group-hover:grayscale-0 transition-all duration-300"
            >
        @endif

        <h3 class="text-h4 text-blue-black line-clamp-2">{{ $title }}</h3>
        @if($description)
            <p class="text-body-md text-blue-black line-clamp-3">{{ $description }}</p>
        @endif
        @if($showPrice && $price)
            <div class="flex items-center gap-2">
                @if($compareAtPrice)
                    <span class="text-body-sm text-gray-500 line-through">{{ $compareAtPrice }}</span>
                @endif
                <span class="text-body-md font-semibold text-blue-black">{{ $price }}</span>
            </div>
        @endif
    </div>
</a>
