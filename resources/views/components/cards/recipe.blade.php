@php
    // Variant-specific classes based on template
    $wrapperClass = match($variant) {
        'featured' => 'w-full bg-off-white rounded-xl overflow-hidden flex flex-col items-center gap-8 pb-10',
        'grid' => 'h-[626px] flex flex-col items-center gap-8 pb-10 bg-off-white rounded-xl overflow-hidden',
        'mobile' => 'w-[310px] h-[411px] flex flex-col items-center gap-8 pb-8 bg-off-white rounded-xl overflow-hidden shrink-0',
        default => 'flex flex-col items-center gap-8 pb-10 bg-off-white rounded-xl overflow-hidden',
    };

    $imageWrapperClass = match($variant) {
        'featured' => 'block relative w-full flex-1 min-h-[300px] lg:min-h-[500px] p-6 flex flex-col justify-end items-center',
        'grid' => 'block relative w-full flex-1 p-6 flex flex-col justify-end items-center',
        'mobile' => 'block relative w-full flex-1 p-6 flex flex-col justify-end items-center',
        default => 'block relative w-full flex-1 p-6 flex flex-col justify-end items-center',
    };

    $contentClass = match($variant) {
        'featured' => 'w-full px-6 lg:px-10 flex flex-col items-center gap-6',
        'grid' => 'flex flex-col items-center gap-6 px-8 text-center text-blue-black',
        'mobile' => 'flex flex-col items-center gap-4 px-5 text-center text-blue-black',
        default => 'flex flex-col items-center gap-5 px-6 text-center text-blue-black',
    };

    $isFeatured = $variant === 'featured';
@endphp

<article class="{{ $wrapperClass }}">
    {{-- Image with tag --}}
    <a href="{{ $url }}" class="{{ $imageWrapperClass }}">
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            class="absolute inset-0 w-full h-full object-cover rounded-t-[4px]"
        >
        @if(count($tags) > 0)
            <div class="relative z-10">
                <span class="tag">{{ implode(' • ', $tags) }}</span>
            </div>
        @endif
    </a>

    {{-- Content --}}
    <div class="{{ $contentClass }}">
        <h3 class="{{ $isFeatured ? 'text-h3' : 'text-h4' }} text-blue-black text-center line-clamp-2">{{ $title }}</h3>

        @if($isFeatured && $description)
            <p class="text-body-medium text-blue-black text-center">{{ $description }}</p>
        @endif

        {{-- Author/date --}}
        <div class="meta-row {{ $variant === 'featured' ? 'meta-row-stack' : '' }} text-caption uppercase text-blue-black">
            <span class="underline underline-offset-4">BY {{ $author }}</span>
            <span class="meta-separator">•</span>
            <span>{{ $date }}</span>
        </div>
    </div>
</article>
