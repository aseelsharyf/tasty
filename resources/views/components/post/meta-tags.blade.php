@props([
    'category' => null,
    'categoryUrl' => null,
    'tag' => null,
    'tagUrl' => null,
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'tag ' . $class]) }}>
    @if($category)
        <a href="{{ $categoryUrl ?? '#' }}" class="hover:underline">{{ strtoupper($category) }}</a>
        @if($tag)
            <span class="mx-1 max-lg:hidden">•</span>
            <a href="{{ $tagUrl ?? '#' }}" class="hover:underline max-lg:hidden">{{ strtoupper($tag) }}</a>
        @endif
    @elseif($tag)
        {{-- Show tag on all screens if no category --}}
        <a href="{{ $tagUrl ?? '#' }}" class="hover:underline">{{ strtoupper($tag) }}</a>
    @endif
</div>
