{{-- Destination Spread Section - Horizontal scrolling spread cards --}}
<section class="w-full max-w-[1880px] mx-auto {{ $bgColorClass }} py-16 max-md:py-8" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
    <div class="scroll-container pb-32 max-md:pb-16 container-main">
        <div class="flex gap-0 pl-10 min-w-max max-md:pl-5">
            @foreach($posts as $index => $post)
                {{-- Divider between cards --}}
                @if($showDividers && $index > 0)
                    <div class="w-px h-[889px] {{ $dividerColor }} mx-10 shrink-0"></div>
                @endif

                <div class="{{ $loop->last ? 'pr-10 max-md:pr-5' : '' }}">
                    <x-cards.spread
                        :post="$post"
                        :reversed="$loop->even"
                    />
                </div>
            @endforeach
        </div>
    </div>
</section>
