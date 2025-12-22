{{-- Destination Spread Section - Horizontal scrolling spread cards --}}
<section class="w-full max-w-[1880px] mx-auto {{ $bgColorClass }} py-16 max-md:py-8" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
    <div class="scroll-container pb-8 max-md:pb-6 container-main">
        <div class="flex pl-10 min-w-max max-md:pl-5 max-md:gap-8">
            @foreach($posts as $index => $post)
                <div class="flex items-center shrink-0 {{ $loop->last ? 'pr-10 max-md:pr-5' : '' }}">
                    <x-cards.spread
                        :post="$post"
                        :reversed="$loop->even"
                    />
                    {{-- Divider after card (except last) --}}
                    @if(!$loop->last)
                        @if($showDividers)
                            <div class="w-px self-stretch {{ $dividerColor }} shrink-0 max-md:hidden mx-10"></div>
                        @else
                            <div class="shrink-0 max-md:hidden w-12"></div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
