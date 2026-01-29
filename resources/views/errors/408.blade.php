@extends('errors.layout')

@section('title', 'Still waiting on the order')
@section('code', '408')

@section('actions')
    <button onclick="location.reload()" class="inline-flex items-center gap-2 px-6 py-3 bg-tasty-yellow text-tasty-blue-black text-sm font-medium rounded-full hover:bg-tasty-yellow/90 transition-colors">
        Try reloading the page
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
    </button>
    <span class="text-sm text-tasty-blue-black/60">
        Check your connection
    </span>
    <a href="{{ url('/') }}" class="text-sm text-tasty-blue-black hover:text-tasty-blue-black/70 transition-colors underline underline-offset-4 decoration-tasty-yellow decoration-2">
        Head back to the homepage
    </a>
@endsection

@section('tagline', "Good things take time — sometimes a little too much.")
