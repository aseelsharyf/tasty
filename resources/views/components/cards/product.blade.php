<div class="group relative flex flex-col bg-off-white rounded-xl overflow-hidden p-1 pb-6 w-[426px] max-lg:w-full">
    {{-- Main card link (covers entire card) --}}
    <a href="{{ $url }}" class="absolute inset-0 z-0" aria-label="{{ $title }}"></a>

    {{-- Image container --}}
    <div class="relative h-[300px] max-lg:h-[250px] bg-white rounded-lg flex items-end justify-center p-6 mb-4">
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            class="absolute inset-0 w-full h-full object-contain p-5"
        >
        @if(count($tags) > 0)
            <span class="tag relative z-10 inline-flex items-center gap-1 whitespace-nowrap">
                @foreach($tags as $index => $tag)
                    @if($index > 0)
                        <span>•</span>
                    @endif
                    @if($tag['url'] ?? null)
                        <a href="{{ $tag['url'] }}" class="hover:underline relative z-10">{{ $tag['name'] }}</a>
                    @else
                        <span>{{ $tag['name'] }}</span>
                    @endif
                @endforeach
            </span>
        @endif
    </div>

    {{-- Content --}}
    <div class="flex flex-col items-center gap-3 px-8 max-lg:px-6 text-center">
        {{-- Store Logo --}}
        @if($storeLogo)
            @if($storeUrl)
                <a href="{{ $storeUrl }}" class="relative z-10">
                    <img
                        src="{{ $storeLogo }}"
                        alt="{{ $storeName ?? 'Store' }}"
                        class="max-w-[80px] max-h-[32px] object-contain grayscale hover:grayscale-0 transition-all duration-300"
                    >
                </a>
            @else
                <img
                    src="{{ $storeLogo }}"
                    alt="{{ $storeName ?? 'Store' }}"
                    class="max-w-[80px] max-h-[32px] object-contain grayscale group-hover:grayscale-0 transition-all duration-300"
                >
            @endif
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
</div>
