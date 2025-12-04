@props([
    'mode' => 'scroll',
    'mobileMode' => null,
    'columns' => 3,
    'mobileColumns' => 1,
    'gap' => 'gap-5 lg:gap-10',
    'paddingX' => '',
    'itemWidth' => 'full',
    'align' => 'stretch',
])

@php
    $mobileMode = $mobileMode ?? $mode;

    $mobileColsMap = [
        1 => 'grid-cols-1',
        2 => 'grid-cols-2',
        3 => 'grid-cols-3',
    ];

    $desktopColsMap = [
        1 => 'lg:grid-cols-1',
        2 => 'lg:grid-cols-2',
        3 => 'lg:grid-cols-3',
        4 => 'lg:grid-cols-4',
        5 => 'lg:grid-cols-5',
    ];

    $alignMap = [
        'stretch' => 'justify-items-stretch',
        'center' => 'justify-items-center',
        'start' => 'justify-items-start',
        'end' => 'justify-items-end',
    ];

    $mobileCols = $mobileColsMap[$mobileColumns] ?? 'grid-cols-1';
    $desktopCols = $desktopColsMap[$columns] ?? 'lg:grid-cols-3';
    $alignClass = $alignMap[$align] ?? 'justify-items-stretch';

    if ($mode === 'scroll' && $mobileMode === 'scroll') {
        $containerClasses = "flex {$gap} overflow-x-auto scrollbar-hide scroll-smooth {$paddingX}";
    } elseif ($mode === 'grid' && $mobileMode === 'grid') {
        $containerClasses = "grid {$mobileCols} {$desktopCols} {$alignClass} {$gap} {$paddingX}";
    } elseif ($mode === 'grid' && $mobileMode === 'scroll') {
        $containerClasses = "flex lg:grid {$desktopCols} lg:{$alignClass} items-start {$gap} overflow-x-auto lg:overflow-visible scrollbar-hide scroll-smooth {$paddingX}";
    } elseif ($mode === 'scroll' && $mobileMode === 'grid') {
        $containerClasses = "grid lg:flex {$mobileCols} {$alignClass} {$gap} lg:overflow-x-auto lg:scrollbar-hide lg:scroll-smooth {$paddingX}";
    }
@endphp

<div {{ $attributes->merge(['class' => $containerClasses]) }}>
    {{ $slot }}
</div>
