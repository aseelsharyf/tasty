<div class="w-full max-w-[800px] rounded-[12px] overflow-hidden">
    {{-- Top: Image with tag overlay --}}
    <div class="relative h-[380px] max-md:h-[280px] p-8 max-md:p-5 flex flex-col justify-between">
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            class="absolute inset-0 w-full h-full object-cover"
        >
        @if($hasVideo)
            <div class="absolute inset-0 z-10 flex items-center justify-center pointer-events-none">
                <div class="w-14 h-14 border-2 border-white rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                </div>
            </div>
        @endif
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
        <a href="{{ $buttonUrl }}" class="btn btn-{{ $buttonVariant }} shrink-0" aria-label="{{ $buttonText }}: {{ $title }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <polygon points="5,3 19,12 5,21" fill="currentColor"/>
            </svg>
            <span>{{ $buttonText }}<span class="sr-only">: {{ $title }}</span></span>
        </a>
    </div>
</div>
