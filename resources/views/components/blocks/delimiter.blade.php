{{-- resources/views/components/blocks/delimiter.blade.php --}}
{{-- EditorJS Delimiter Block --}}

@props([
    'variant' => 'dots', // dots, line, space
])

@if($variant === 'line')
    <hr class="border-t border-tasty-blue-black/10 my-16" />
@elseif($variant === 'space')
    <div class="my-16"></div>
@else
    {{-- Dots (default) --}}
    <div class="flex items-center justify-center my-16">
        <span class="text-2xl text-tasty-blue-black/30 tracking-[0.5em]">***</span>
    </div>
@endif
