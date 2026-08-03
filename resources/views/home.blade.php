@extends('layouts.app')

@section('content')

<main class="flex flex-col flex-1">
    <x-sections.renderer :sections="$sections" />
</main>

@endsection
