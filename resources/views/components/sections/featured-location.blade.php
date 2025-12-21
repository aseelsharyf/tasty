<div class="w-full max-w-[1880px] mx-auto location-section-container">
    <section class="location-section w-full relative flex flex-col justify-end">
        {{-- Background image --}}
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            class="absolute inset-0 w-full h-full object-cover"
        >

        {{-- Content box with top radius - configurable bg color --}}
        <div class="relative z-10 {{ $bgColorClass }} rounded-t-[5000px] pt-28 pb-0 max-md:pt-24" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
            <div class="container-main px-10 max-md:px-5 text-center flex flex-col items-center gap-10">
                {{-- Title and tagline grouped --}}
                <div class="flex flex-col items-center gap-4">
                    <h2 class="text-h1 text-{{ $textColor }}">{{ $name }}</h2>
                    @if($tagline)
                        <p class="text-h2 text-{{ $textColor }} max-w-[800px] max-md:text-h4">{{ $tagline }}</p>
                    @endif
                </div>
                {{-- Tags --}}
                <div class="flex items-center gap-5 text-caption uppercase text-{{ $textColor }}">
                    <span>{{ $tag1 }}</span>
                    <span>•</span>
                    <span>{{ $tag2 }}</span>
                </div>
                {{-- Description --}}
                @if($description)
                    <p class="text-body-lg text-{{ $textColor }} max-w-[600px]">{{ $description }}</p>
                @endif
                {{-- Button (optional) --}}
                @if($buttonText && $buttonUrl && $buttonUrl !== '#')
                    <a href="{{ $buttonUrl }}" class="btn btn-{{ $buttonVariant }}">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>{{ $buttonText }}</span>
                    </a>
                @endif
            </div>
        </div>
    </section>
</div>
