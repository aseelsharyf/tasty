@extends('layouts.app')

@section('title', $title)

@section('content')

<main class="flex flex-col flex-1">
    <x-sections.renderer :sections="$sections" />
</main>

@endsection
