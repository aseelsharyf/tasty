{{-- Carousel Section --}}
{{-- Horizontal scroll carousel of posts without intro card --}}
@php
    $paddingTopClass = match($paddingTop) {
        'small' => 'pt-8 max-lg:pt-5',
        'medium' => 'pt-16 max-lg:pt-10',
        'large' => 'pt-24 max-lg:pt-16',
        default => '',
    };
    $paddingBottomClass = match($paddingBottom) {
        'small' => 'pb-8 max-lg:pb-5',
        'medium' => 'pb-16 max-lg:pb-10',
        'large' => 'pb-24 max-lg:pb-16',
        default => '',
    };
@endphp

@if($posts->isNotEmpty())
<section
    class="w-full {{ $bgColorClass }} {{ $paddingTopClass }} {{ $paddingBottomClass }}"
    @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif
>
    <div class="scroll-container pb-8 max-lg:pb-6 container-main">
        <div class="flex items-start px-10 min-w-max max-lg:px-5 max-lg:gap-8">
            @foreach($posts as $post)
                <div class="flex items-start shrink-0">
                    <x-cards.spread
                        :post="$post"
                        :reversed="$loop->even"
                    />
                    @if(!$loop->last && $showDividers)
                        <div class="w-px h-[600px] {{ $dividerColor }} shrink-0 max-lg:hidden"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
