@extends('layouts.app')

@section('content')
<div class="pt-[96px] md:pt-[112px] min-h-screen bg-off-white">
    <div class="max-w-[1440px] mx-auto px-5 lg:px-10 py-16 lg:py-24">
        {{-- Search Header --}}
        <div class="max-w-[800px] mx-auto mb-16">
            <h1 class="text-h1 text-blue-black text-center mb-8">Search</h1>

            <form action="{{ route('search') }}" method="GET">
                <input
                    type="text"
                    name="q"
                    value="{{ $query }}"
                    placeholder="What are you looking for?"
                    class="w-full px-6 py-5 text-xl bg-white rounded-lg border border-blue-black/10 focus:border-blue-black/30 focus:outline-none transition-colors text-blue-black placeholder-blue-black/40"
                    autofocus
                />
            </form>
        </div>

        {{-- Results --}}
        @if($query)
            @php
                $posts = $results['posts'] ?? collect();
                $count = $posts->count();
            @endphp

            <p class="text-body-sm text-blue-black/50 text-center mb-10">
                {{ $count }} result{{ $count !== 1 ? 's' : '' }} for "{{ $query }}"
            </p>

            @if($count > 0)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10">
                    @foreach($posts as $post)
                        <x-cards.horizontal :post="$post" />
                    @endforeach
                </div>
            @else
                <p class="text-body-md text-blue-black/50 text-center">No articles found. Try a different search term.</p>
            @endif
        @endif
    </div>
</div>
@endsection
