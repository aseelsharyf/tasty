{{-- resources/views/components/ui/tag.blade.php --}}
@props([
    'items' => [], // Array of tag strings, e.g., ['recipe', 'best of']
    'separator' => '•',
    'bgColor' => 'bg-tasty-light-gray',
    'textColor' => 'text-tasty-blue-black',
])

<div class="{{ $bgColor }} {{ $textColor }} px-3 py-2.5 rounded-full inline-flex items-center gap-2.5">
    @foreach($items as $index => $item)
        <span class="font-sans text-sm uppercase leading-3 tracking-wide">{{ $item }}</span>
        @if($index < count($items) - 1)
            <span class="font-sans text-sm uppercase leading-3">{{ $separator }}</span>
        @endif
    @endforeach
</div>
