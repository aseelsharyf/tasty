{{-- resources/views/components/post/author-date.blade.php --}}

@props([
    'author',
    'authorUrl' => '#',
    'date',
    'size' => 'sm', // Options: 'xs' (text-xs), 'sm' (text-sm)
    'layout' => 'horizontal', // Options: 'horizontal', 'vertical md:horizontal'
    'align' => 'left', // Options: 'left', 'center'
    'hideSeparatorOnMobile' => false,
    'color' => 'text-stone-900',
])

@php
    // Size classes
    $textSize = $size === 'xs' ? 'text-xs' : 'text-sm';

    // Layout classes
    $layoutClasses = match($layout) {
        'horizontal' => 'inline-flex justify-start items-start gap-5',
        'vertical md:horizontal' => 'w-full flex flex-col md:flex-row items-start md:items-center justify-start md:justify-center md:space-x-5',
        default => 'inline-flex justify-start items-start gap-5',
    };

    // Adjust layout classes for center alignment
    if ($align === 'center' && $layout === 'horizontal') {
        $layoutClasses = 'inline-flex justify-center items-center gap-5';
    }

    // Separator visibility
    $separatorClasses = $hideSeparatorOnMobile ? 'hidden md:block' : '';

    // Container classes
    $containerClasses = trim("{$layoutClasses} {$textSize} {$color}");
@endphp

<div class="{{ $containerClasses }}">
    <a href="{{ $authorUrl }}" class="hover:text-stone-600 transition-colors duration-200 uppercase underline {{ $layout === 'vertical md:horizontal' ? 'mb-2 md:mb-0' : '' }}">
        BY {{ $author }}
    </a>

    {{-- Dot separator --}}
    <span class="color-tasty-blue-black {{ $separatorClasses }}">•</span>

    {{-- Date --}}
    <span class="color-tasty-blue-black {{ $layout === 'horizontal' ? '' : 'truncate' }} {{ $layout === 'horizontal' ? '' : 'whitespace-nowrap' }}">
        {{ strtoupper($date) }}
    </span>
</div>
