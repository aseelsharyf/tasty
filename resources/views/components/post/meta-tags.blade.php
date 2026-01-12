@props([
    'category' => null,
    'categoryUrl' => null,
    'tag' => null,
    'tagUrl' => null,
    'class' => '',
    'showBothOnMobile' => true,
])

<div {{ $attributes->merge(['class' => 'tag ' . $class]) }}>
    @if($category)
        <a href="{{ $categoryUrl ?? '#' }}" class="hover:underline">{{ strtoupper($category) }}</a>
        @if($tag)
            <span class="{{ $showBothOnMobile ? '' : 'max-lg:hidden' }} mx-1">•</span>
            <a href="{{ $tagUrl ?? '#' }}" class="{{ $showBothOnMobile ? '' : 'max-lg:hidden' }} hover:underline">{{ strtoupper($tag) }}</a>
        @endif
    @elseif($tag)
        <a href="{{ $tagUrl ?? '#' }}" class="hover:underline">{{ strtoupper($tag) }}</a>
    @endif
</div>
