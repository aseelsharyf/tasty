{{-- Hero Section --}}
@if($post)
@php
    $category = $post->categories->first();

    // Alignment classes - mobile is always center
    $alignmentClasses = $alignment === 'bottom'
        ? 'items-start justify-end text-left max-md:items-center max-md:justify-center max-md:text-center'
        : 'items-center justify-center text-center';
@endphp
{{-- Pull hero up behind the navbar --}}
<section class="w-full max-w-[1880px] mx-auto flex justify-center relative z-0 -mt-[96px] md:-mt-[112px]">
    <div class="flex max-md:flex-col md:max-h-[854px] w-full max-w-[1880px]">
        {{-- Hero Image - Left 50% --}}
        <a href="{{ $post->url }}" class="block relative w-1/2 h-[854px] max-md:w-full max-md:h-[400px] overflow-hidden group">
            <img
                src="{{ $post->featured_image_url }}"
                alt="{{ $post->title }}"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
            >
        </a>
        {{-- Hero Content - Right 50% --}}
        <div class="w-1/2 {{ $bgColorClass }} px-10 py-16 flex flex-col {{ $alignmentClasses }} gap-8 max-md:w-full max-md:px-5 max-md:py-10 relative" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
            <div class="flex flex-wrap items-center gap-2.5 text-tag uppercase text-blue-black {{ $alignment === 'bottom' ? 'justify-start max-md:justify-center' : 'justify-center' }}">
                @if($category)
                    <a href="{{ route('category.show', $category->slug) }}" class="hover:underline">{{ $category->name }}</a>
                    <span>•</span>
                @endif
                @if($post->author)
                    <a href="{{ $post->author->url }}" class="hover:underline">BY {{ $post->author->name }}</a>
                    <span>•</span>
                @endif
                <span>{{ $post->published_at?->format('F j, Y') }}</span>
            </div>
            <div class="flex flex-col gap-4 {{ $alignment === 'bottom' ? 'text-left max-md:text-center' : 'text-center' }}">
                <a href="{{ $post->url }}" class="hover:opacity-80 transition-opacity">
                    <h1 class="text-h1 text-blue-black uppercase">{{ $post->title }}</h1>
                </a>
                @if($post->subtitle)
                    <p class="text-h2 text-blue-black">{{ $post->subtitle }}</p>
                @endif
            </div>
            <a href="{{ $post->url }}" class="btn btn-white">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="#0A0924" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>{{ $buttonText }}</span>
            </a>
        </div>
    </div>
</section>
@endif
