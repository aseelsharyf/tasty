{{-- resources/views/components/hero.blade.php
     Full-width hero section with image and content side-by-side
--}}
@props([
    'image',
    'imageAlt' => '',
    'tags' => [], // e.g., ['on culture', 'author name']
    'date' => '',
    'title' => '',
    'subtitle' => '',
    'subtitleItalic' => '',
    'buttonText' => 'Read More',
    'buttonUrl' => '#',
    'bgColor' => 'bg-tasty-yellow',
    'align' => 'left', // 'left' or 'center'
])

@php
    $alignClasses = $align === 'center'
        ? 'justify-center items-center text-center'
        : 'justify-end md:justify-center items-start text-left';
@endphp

<section class="flex flex-col md:flex-row w-full min-h-screen md:min-h-0 md:h-[854px]">
    {{-- Image Side --}}
    <div class="w-full h-[50vh] md:h-full md:w-1/2 relative overflow-hidden">
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            class="absolute inset-0 w-full h-full object-cover"
        >
    </div>

    {{-- Content Side --}}
    <div class="w-full min-h-[50vh] md:min-h-0 md:h-full md:w-1/2 {{ $bgColor }} flex flex-col px-8 py-10 md:p-24 {{ $alignClasses }}">
        {{-- Tags/Metadata --}}
        @if(count($tags) > 0 || $date)
            <div class="flex flex-wrap gap-2 md:gap-3 text-xs font-bold tracking-widest uppercase text-tasty-blue-black mb-4 md:mb-12">
                @foreach($tags as $index => $tag)
                    <span>{{ $tag }}</span>
                    @if($index < count($tags) - 1 || $date)
                        <span>•</span>
                    @endif
                @endforeach
                @if($date)
                    <span class="hidden md:inline">{{ $date }}</span>
                @endif
            </div>
        @endif

        {{-- Title --}}
        <h1 class="font-display text-6xl md:text-7xl lg:text-8xl text-tasty-blue-black mb-4 md:mb-6">
            {{ $title }}
        </h1>

        {{-- Subtitle --}}
        @if($subtitle || $subtitleItalic)
            <h2 class="font-display text-3xl md:text-4xl lg:text-5xl text-tasty-blue-black mb-8 md:mb-12 max-w-xl">
                {{ $subtitle }}
                @if($subtitleItalic)
                    <br><span class="italic">{{ $subtitleItalic }}</span>
                @endif
            </h2>
        @endif

        {{-- Button --}}
        @if($buttonText)
            <a
                href="{{ $buttonUrl }}"
                class="group inline-flex items-center gap-3 bg-white px-8 py-3 rounded-full shadow-sm hover:shadow-md hover:bg-gray-50 transition-all"
            >
                <span class="text-xl font-light group-hover:rotate-90 transition-transform duration-300">+</span>
                <span class="text-sm font-bold uppercase tracking-widest text-tasty-blue-black">{{ $buttonText }}</span>
            </a>
        @endif
    </div>
</section>
