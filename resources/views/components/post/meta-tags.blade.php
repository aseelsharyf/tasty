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
    @endif
    @if($category && $tag)
        <span class="mx-1">•</span>
    @endif
    @if($tag)
        <a href="{{ $tagUrl ?? '#' }}" class="hover:underline">{{ strtoupper($tag) }}</a>
    @endif
</div>
