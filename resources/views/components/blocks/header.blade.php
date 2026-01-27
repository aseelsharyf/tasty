{{-- EditorJS Header Block --}}

@props([
    'text' => '',
    'level' => 2,
    'isRtl' => false,
])

@php
    $level = min(6, max(1, (int) $level));

    // Map heading levels to typography classes (REVERSED: h1=smallest, h4=largest)
    $headingClasses = [
        1 => 'text-h4',
        2 => 'text-h3',
        3 => 'text-h2',
        4 => 'text-h1',
        5 => 'text-h1',
        6 => 'text-h1',
    ];

    $typeClass = $headingClasses[$level] ?? 'text-h3';
    $alignClass = $isRtl ? 'text-right' : '';
@endphp

<h{{ $level }} class="{{ $typeClass }} text-tasty-blue-black {{ $alignClass }} break-words">
    {!! $text !!}
</h{{ $level }}>
