{{-- resources/views/components/hero.blade.php --}}

@props([
    'image',
    'imageAlt' => 'Hero image',
    'category' => 'On Culture',
    'categoryUrl' => '#',
    'author' => 'Mohamed Ashraf',
    'authorUrl' => '#',
    'date' => 'November 12, 2025',
    'title' => 'BITE CLUB',
    'subtitle' => 'The ghost kitchen feeding',
    'subtitleItalic' => 'Malé after dark',
    'buttonText' => 'Read More',
    'buttonUrl' => '#',
    'alignment' => 'bottom-left', // Options: 'center', 'bottom-left'
    'bgColor' => 'bg-tasty-yellow' // Background color for content side (Tailwind color class)
])

@php
    // Determine if bgColor is a hex color or Tailwind class
    $isHexColor = str_starts_with($bgColor, '#');
    $styleAttr = $isHexColor ? "background-color: {$bgColor};" : '';
    $classColor = !$isHexColor ? $bgColor : '';

    // Alignment classes
    $alignmentClasses = $alignment === 'bottom-left'
        ? 'justify-end items-start text-left md:justify-center md:items-start md:text-left'
        : 'justify-center items-center text-center';

    $metadataAlign = $alignment === 'bottom-left'
        ? 'justify-start'
        : 'justify-center';
@endphp

<section class="flex flex-col md:flex-row w-full min-h-screen md:min-h-0">

    {{-- Image Side --}}
    <div class="w-full h-[50vh] md:h-auto md:w-1/2 min-h-screen overflow-hidden relative">
        {{-- <div class="absolute inset-0 hover:opacity-80 transition-opacity duration-200">
            <img src="{{ $image }}"
                 alt="{{ $imageAlt }}"
                 class="absolute inset-0 w-full h-full object-cover object-center">
        </div> --}}
        <div class="absolute inset-0 ">
            <img src="{{ $image }}"
                 alt="{{ $imageAlt }}"
                 class="absolute inset-0 w-full h-full object-cover object-center">
        </div>
    </div>

    {{-- Content Side --}}
    <div class="w-full min-h-[50vh] md:min-h-0 md:w-1/2 {{ $classColor }} flex flex-col px-8 py-10 md:p-24 {{ $alignmentClasses }}"
         @if($styleAttr) style="{{ $styleAttr }}" @endif>

        {{-- Metadata --}}
        <div class="flex flex-wrap gap-2 md:gap-3 text-[10px] md:text-xs font-bold tracking-[0.15em] uppercase text-stone-800 mb-4 md:mb-12 {{ $metadataAlign }}">
            <a href="{{ $categoryUrl }}" class="hover:text-stone-600 transition-colors duration-200">
                {{ $category }}
            </a>
            <span>•</span>
            <a href="{{ $authorUrl }}" class="hover:text-stone-600 transition-colors duration-200">
                {{ $author }}
            </a>
            <span class="hidden md:inline">•</span>
            <span class="hidden md:inline">{{ $date }}</span>
        </div>

        {{-- Main Title --}}
        <h1 class="font-serif text-6xl md:text-7xl lg:text-8xl leading-none text-stone-900 mb-4 md:mb-6">
            {{ $title }}
        </h1>

        {{-- Subtitle --}}
        <h2 class="font-serif text-3xl md:text-4xl lg:text-5xl leading-tight text-stone-900 mb-8 md:mb-12 max-w-xl">
            {{ $subtitle }}<br/>
            <span class="italic">{{ $subtitleItalic }}</span>
        </h2>

        {{-- Button --}}
        <a href="{{ $buttonUrl }}"
           class="group bg-white px-8 py-3 rounded-full flex items-center gap-3 shadow-sm hover:shadow-md hover:bg-stone-50 transition-all duration-300">
            <span class="text-xl font-light leading-none group-hover:rotate-90 transition-transform duration-300">+</span>
            <span class="text-xs md:text-sm font-bold uppercase tracking-widest text-stone-900">{{ $buttonText }}</span>
        </a>

    </div>

</section>
