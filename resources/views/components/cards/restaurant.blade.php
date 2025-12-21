@php
    // Variant-specific classes
    $wrapperClass = match($variant) {
        'mobile' => 'w-[310px] h-[531px] flex flex-col gap-8 shrink-0',
        'grid' => 'flex-1 flex flex-col gap-6',
        default => 'flex flex-col gap-6',
    };

    $imageClass = match($variant) {
        'mobile' => 'h-[362px] rounded-xl overflow-hidden relative',
        default => 'h-[460px] rounded-xl overflow-hidden relative',
    };
@endphp

<article class="{{ $wrapperClass }}">
    {{-- Image with rating tag --}}
    <div class="{{ $imageClass }}">
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            class="absolute inset-0 w-full h-full object-cover"
        >
        <div class="absolute inset-0 flex items-end justify-center p-6">
            <div class="tag tag-white flex items-center gap-2.5">
                <span>REVIEW</span>
                <span>•</span>
                <span>{{ $ratingStars() }}</span>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="flex flex-col gap-5 text-center text-blue-black">
        <div class="flex flex-col">
            <h3 class="text-h3 uppercase">{{ $name }}</h3>
            @if($tagline)
                <p class="text-h4">{{ $tagline }}</p>
            @endif
        </div>
        @if($description)
            <p class="text-body-medium line-clamp-3">{{ $description }}</p>
        @endif
    </div>
</article>
