@extends('layouts.app')

@section('title', $title)

@section('content')

<main class="flex flex-col flex-1">
    @php
        $isArchive = in_array($entityType ?? null, ['category', 'tag'], true);
        $firstSectionType = $sections[0]['type'] ?? null;
        $promotesFirstSectionHeading = $isArchive && $firstSectionType === 'latest-updates';
        $firstSectionAlreadyHasHeading = $firstSectionType === 'hero';
    @endphp
    @if($isArchive && !$promotesFirstSectionHeading && !$firstSectionAlreadyHasHeading)
        <h1 class="sr-only">{{ $title }}</h1>
    @endif
    <x-sections.renderer :sections="$sections" :primaryHeading="$promotesFirstSectionHeading" />
</main>

@endsection
