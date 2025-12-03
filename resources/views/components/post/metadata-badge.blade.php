{{-- resources/views/components/post/metadata-badge.blade.php --}}

@props([
    'category' => '',
    'categoryUrl' => '#',
    'tag' => null,
    'tagUrl' => '#',
    'bgColor' => 'bg-tasty-off-white',
    'textSize' => 'text-xs',
    'padding' => 'px-4 py-2',
    'gap' => 'gap-2.5',
    'shadow' => '',
    'hoverBg' => '',
    'hideTagOnMobile' => false,
    'uppercase' => true
])

@php
    $tagClasses = $hideTagOnMobile ? 'hidden md:inline' : '';
    $textTransform = $uppercase ? 'uppercase' : '';
@endphp

<div class="{{ $bgColor }} {{ $padding }} {{ $gap }} {{ $shadow }} {{ $hoverBg }} rounded-full flex items-center justify-center transition-all duration-300 whitespace-nowrap">
    <a href="{{ $categoryUrl }}" class="{{ $textSize }} {{ $textTransform }} text-stone-900 hover:opacity-70 transition-opacity duration-200">
        {{ $category }}
    </a>

    @if($tag)
        <span class="{{ $textSize }} text-stone-900 {{ $tagClasses }}">•</span>
        <a href="{{ $tagUrl }}" class="{{ $textSize }} {{ $textTransform }} text-stone-900 {{ $tagClasses }} hover:opacity-70 transition-opacity duration-200">
            {{ $tag }}
        </a>
    @endif
</div>
