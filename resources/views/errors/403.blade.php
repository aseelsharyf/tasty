@extends('errors.layout')

@section('title', "That table's reserved")
@section('code', '403')

@section('actions')
    <span class="text-sm text-tasty-blue-black/60">
        Check your access
    </span>
    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-tasty-yellow text-tasty-blue-black text-sm font-medium rounded-full hover:bg-tasty-yellow/90 transition-colors">
        Return to the homepage
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
        </svg>
    </a>
    <a href="{{ url('/') }}" class="text-sm text-tasty-blue-black hover:text-tasty-blue-black/70 transition-colors underline underline-offset-4 decoration-tasty-yellow decoration-2">
        Explore public stories
    </a>
@endsection

@section('tagline', "Not every door opens, but there's plenty to enjoy.")
