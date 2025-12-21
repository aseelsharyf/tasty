{{-- Article Image + Text Side by Side Block --}}
@props([
    'imageSrc' => '',
    'imageAlt' => '',
    'imageCaption' => '',
    'text' => '',
    'imagePosition' => 'left', // left or right
])

<div class="w-full py-8 md:py-16 px-4 md:px-10">
    <div class="flex flex-col md:flex-row gap-8 md:gap-10 items-center justify-center max-w-[1360px] mx-auto">
        {{-- Image Column --}}
        <div class="w-full md:w-1/2 flex flex-col gap-6 md:gap-8 {{ $imagePosition === 'right' ? 'md:order-2' : '' }}">
            <div class="relative aspect-[582/750] w-full overflow-hidden">
                <img
                    src="{{ $imageSrc }}"
                    alt="{{ $imageAlt }}"
                    class="absolute inset-0 w-full h-full object-cover"
                >
            </div>
            @if($imageCaption)
                <p class="article-caption">{{ $imageCaption }}</p>
            @endif
        </div>

        {{-- Divider --}}
        <div class="hidden md:block w-px bg-white self-stretch {{ $imagePosition === 'right' ? 'md:order-1' : '' }}"></div>

        {{-- Text Column --}}
        <div class="w-full md:flex-1 flex items-center {{ $imagePosition === 'right' ? 'md:order-0' : '' }}">
            <div class="article-text">
                {!! $text !!}
            </div>
        </div>
    </div>
</div>
