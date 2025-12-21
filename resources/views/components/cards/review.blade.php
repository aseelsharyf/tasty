<div class="w-full max-w-[966px] rounded-[12px] overflow-hidden">
    {{-- Top: Image with tag overlay --}}
    <div class="relative h-[544px] max-md:h-[350px] p-10 max-md:p-6 flex flex-col justify-between">
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            class="absolute inset-0 w-full h-full object-cover"
        >
        @if(count($tags) > 0)
            <span class="tag relative z-10 self-start">{{ implode(' • ', $tags) }}</span>
        @endif
        <div class="relative z-10 text-{{ $textColor }}">
            <h3 class="text-h3 uppercase max-md:text-h4">{{ $title }}</h3>
            @if($subtitle)
                <p class="text-h4 max-md:text-body-large">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    {{-- Bottom: Configurable color section with description + button --}}
    <div class="{{ $bgColorClass }} flex items-end justify-between gap-10 px-10 pt-2 pb-12 max-md:flex-col max-md:items-start max-md:gap-6 max-md:px-6" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
        <div class="flex flex-col gap-6 flex-1">
            @if($description)
                <p class="text-body-medium text-{{ $textColor }} max-md:text-body-small">{{ $description }}</p>
            @endif
            <div class="flex items-center gap-5 text-caption uppercase text-{{ $textColor }}">
                <span>BY {{ $author }}</span>
                <span>•</span>
                <span>{{ $date }}</span>
            </div>
        </div>
        <a href="{{ $buttonUrl }}" class="btn btn-{{ $buttonVariant }} shrink-0">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <polygon points="5,3 19,12 5,21" fill="currentColor"/>
            </svg>
            <span>{{ $buttonText }}</span>
        </a>
    </div>
</div>
