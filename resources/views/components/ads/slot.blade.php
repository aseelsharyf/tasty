@props([
    'adSlot' => null,
    'width' => null,
    'height' => null,
    'class' => '',
])

<div {{ $attributes->merge(['class' => "ad-slot {$class}"]) }}>
    @if($adSlot)
        <center>
            <script src="https://traffyx.com/adserve/ad_serve.php?slot={{ $adSlot }}"></script>
        </center>
    @else
        {{-- Placeholder when no slot is configured --}}
        @if($width && $height)
            <div class="flex items-center justify-center bg-gray-100 rounded-lg border border-dashed border-gray-300 mx-auto" style="width: {{ $width }}px; height: {{ $height }}px;">
                <div class="text-center text-gray-400">
                    <span class="text-[10px] uppercase tracking-wider block mb-1">Advertisement</span>
                    <span class="text-xs">{{ $width }} x {{ $height }}</span>
                </div>
            </div>
        @else
            <div class="flex items-center justify-center bg-gray-100 rounded-lg border border-dashed border-gray-300 mx-auto py-8 px-12">
                <div class="text-center text-gray-400">
                    <span class="text-[10px] uppercase tracking-wider block mb-1">Advertisement</span>
                    <span class="text-xs">Auto</span>
                </div>
            </div>
        @endif
    @endif
</div>
