@props([
    'author',
    'authorUrl' => '#',
    'date',
    'size' => 'sm',
    'layout' => 'horizontal',
    'align' => 'left',
    'hideSeparatorOnMobile' => false,
    'hideDateOnMobile' => false,
    'color' => 'text-stone-900',
])

@php
    $textSize = $size === 'xs' ? 'text-xs' : 'text-sm';

    $layoutClasses = match($layout) {
        'horizontal' => 'inline-flex justify-start items-start gap-5',
        'vertical md:horizontal' => 'w-full flex flex-col md:flex-row items-start md:items-center justify-start md:justify-center md:space-x-5',
        default => 'inline-flex justify-start items-start gap-5',
    };

    if ($align === 'center' && $layout === 'horizontal') {
        $layoutClasses = 'inline-flex justify-center items-center gap-5';
    }

    $separatorClasses = $hideSeparatorOnMobile ? 'hidden md:block' : '';

    $containerClasses = trim("{$layoutClasses} {$textSize} {$color}");
@endphp

<div class="{{ $containerClasses }}">
    <a href="{{ $authorUrl }}" class="hover:text-stone-600 transition-colors duration-200 uppercase underline {{ $layout === 'vertical md:horizontal' ? 'mb-2 md:mb-0' : '' }}">
        BY {{ $author }}
    </a>

    <span class="color-tasty-blue-black {{ $separatorClasses }} {{ $hideDateOnMobile ? 'hidden md:block' : '' }}">•</span>

    <span class="color-tasty-blue-black {{ $layout === 'horizontal' ? '' : 'truncate' }} {{ $layout === 'horizontal' ? '' : 'whitespace-nowrap' }} {{ $hideDateOnMobile ? 'hidden md:block' : '' }}">
        {{ strtoupper($date) }}
    </span>
</div>
