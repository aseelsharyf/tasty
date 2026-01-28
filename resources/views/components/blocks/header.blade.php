{{-- EditorJS Header Block --}}

@props([
    'text' => '',
    'level' => 2,
    'isRtl' => false,
])

@php
    $level = min(6, max(1, (int) $level));

    // Map heading levels to article block heading classes
    $headingClasses = [
        1 => 'text-block-h1',
        2 => 'text-block-h2',
        3 => 'text-block-h3',
        4 => 'text-block-h4',
        5 => 'text-block-h4',
        6 => 'text-block-h4',
    ];

    $typeClass = $headingClasses[$level] ?? 'text-block-h2';
    $alignClass = $isRtl ? 'text-right' : '';
@endphp

<h{{ $level }} class="{{ $typeClass }} text-tasty-blue-black {{ $alignClass }} break-words">
    {!! $text !!}
</h{{ $level }}>
