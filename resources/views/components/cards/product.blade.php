<a href="{{ $url }}" class="card-vertical bg-off-white rounded-xl overflow-hidden p-1 pb-10">
    {{-- Image container: square, white bg, contain --}}
    <div class="relative aspect-square bg-white rounded-lg flex items-end justify-center p-6 mb-8">
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
    <div class="flex flex-col items-center gap-6 px-8 text-center">
        <h3 class="text-h4 text-blue-black line-clamp-2">{{ $title }}</h3>
        @if($description)
            <p class="text-body-md text-blue-black line-clamp-3">{{ $description }}</p>
        @endif
    </div>
</a>
