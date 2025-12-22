<div class="w-full max-w-[800px] rounded-[12px] overflow-hidden">
    {{-- Top: Image with tag overlay --}}
    <div class="relative h-[380px] max-md:h-[280px] p-8 max-md:p-5 flex flex-col justify-between">
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            class="absolute inset-0 w-full h-full object-cover"
        >
        @if(count($tags) > 0)
            <span class="tag relative z-10 self-start">{{ implode(' • ', $tags) }}</span>
        @endif
        <div class="relative z-10 text-{{ $textColor }}">
            <h3 class="text-h4 uppercase max-md:text-h5">{{ $title }}</h3>
            @if($subtitle)
                <p class="text-h5 max-md:text-body-md">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    {{-- Bottom: Configurable color section with description + button --}}
    <div class="{{ $bgColorClass }} flex items-end justify-between gap-8 px-8 pt-2 pb-8 max-md:flex-col max-md:items-start max-md:gap-5 max-md:px-5" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
        <div class="flex flex-col gap-4 flex-1">
            @if($description)
                <p class="text-body-md text-{{ $textColor }} max-md:text-body-sm">{{ $description }}</p>
            @endif
            <div class="flex items-center gap-4 text-caption uppercase text-{{ $textColor }}">
                <span>BY {{ $author }}</span>
                <span>•</span>
                <span>{{ $date }}</span>
            </div>
        </div>
        <a href="{{ $buttonUrl }}" class="btn btn-{{ $buttonVariant }} shrink-0">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <polygon points="5,3 19,12 5,21" fill="currentColor"/>
            </svg>
            <span>{{ $buttonText }}</span>
        </a>
    </div>
</div>
