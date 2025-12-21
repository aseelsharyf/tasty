{{-- Destination Spread Section - Horizontal scrolling spread cards --}}
<section class="w-full max-w-[1880px] mx-auto {{ $bgColorClass }} py-16 max-md:py-8" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
    <div class="scroll-container pb-32 max-md:pb-16 container-main">
        <div class="flex pl-10 min-w-max max-md:pl-5 max-md:gap-8">
            @foreach($posts as $index => $post)
                <div class="flex items-start shrink-0 {{ $loop->last ? 'pr-10 max-md:pr-5' : '' }}">
                    <x-cards.spread
                        :post="$post"
                        :reversed="$loop->even"
                    />
                    {{-- Divider after card (except last) --}}
                    @if(!$loop->last)
                        @if($showDividers)
                            <div class="w-px h-[889px] {{ $dividerColor }} shrink-0 max-md:hidden" style="margin-left: 40px; margin-right: 40px;"></div>
                        @else
                            <div class="shrink-0 max-md:hidden" style="width: 80px;"></div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
