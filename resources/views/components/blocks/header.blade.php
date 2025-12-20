{{-- resources/views/components/blocks/header.blade.php --}}
{{-- EditorJS Header Block --}}

@props([
    'text' => '',
    'level' => 2,
    'isRtl' => false,
])

@php
    $level = min(6, max(1, (int) $level));

    // Map heading levels to typography classes (from Figma)
    $headingClasses = [
        1 => 'text-h1',
        2 => 'text-h2',
        3 => 'text-h3',
        4 => 'text-h4',
        5 => 'text-h4',
        6 => 'text-h4',
    ];

    $typeClass = $headingClasses[$level] ?? 'text-h3';
    $alignClass = $isRtl ? 'text-right' : '';
@endphp

<h{{ $level }} class="{{ $typeClass }} text-tasty-blue-black {{ $alignClass }}">
    {!! $text !!}
</h{{ $level }}>
