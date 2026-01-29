@extends('errors.layout')

@section('title', "This dish isn't on the menu")
@section('code', '404')

@section('actions')
    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-tasty-yellow text-tasty-blue-black text-sm font-medium rounded-full hover:bg-tasty-yellow/90 transition-colors">
        Head back to the homepage
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
        </svg>
    </a>
    <a href="{{ url('/') }}" class="text-sm text-tasty-blue-black hover:text-tasty-blue-black/70 transition-colors underline underline-offset-4 decoration-tasty-yellow decoration-2">
        Browse our latest stories
    </a>
    <a href="{{ url('/search') }}" class="text-sm text-tasty-blue-black hover:text-tasty-blue-black/70 transition-colors underline underline-offset-4 decoration-tasty-yellow decoration-2">
        Search for what you were looking for
    </a>
@endsection

@section('tagline', "Some things sell out — the rest are still worth tasting.")
