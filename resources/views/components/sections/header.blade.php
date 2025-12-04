{{-- resources/views/components/sections/header.blade.php
     Section header with image, title and description
--}}
@props([
    'image' => null,
    'imageAlt' => '',
    'titleSmall' => '',
    'titleLarge' => '',
    'description' => '',
    'align' => 'center', // 'center' or 'left'
])

@php
    $alignClass = $align === 'center' ? 'items-center text-center' : 'items-start text-left';
@endphp

<div class="w-full flex flex-col gap-5 {{ $alignClass }}">
    @if($image)
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            class="w-full h-[176px] md:h-[430px] object-contain mix-blend-darken"
        >
    @endif

    <div class="flex flex-col gap-5 {{ $alignClass }}">
        @if($titleSmall)
            <h2 class="font-display text-[40px] leading-[44px] md:text-[64px] md:leading-[66px] text-tasty-blue-black tracking-[-1.6px] md:tracking-[-2.56px]">
                {{ $titleSmall }}
            </h2>
        @endif

        @if($titleLarge)
            <h2 class="font-display text-[60px] leading-[50px] md:text-[100px] md:leading-[86px] text-tasty-blue-black tracking-[-2.4px] md:tracking-[-4px] uppercase">
                {{ $titleLarge }}
            </h2>
        @endif

        @if($description)
            <p class="font-sans text-[20px] leading-[26px] md:text-[24px] md:leading-[26px] text-tasty-blue-black max-w-xl">
                {{ $description }}
            </p>
        @endif
    </div>
</div>
