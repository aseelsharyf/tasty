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
            <p class="text-body-sm text-blue-black/50 text-center mb-10">
                {{ $totalCount }} result{{ $totalCount !== 1 ? 's' : '' }} for "{{ $query }}"
            </p>

            @if($totalCount > 0)
                {{-- Products --}}
                @if($results['products']->isNotEmpty())
                    <div class="mb-16">
                        <h2 class="text-h3 text-blue-black mb-6">Products</h2>
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-6">
                            @foreach($results['products'] as $product)
                                <x-cards.product :product="$product" />
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Articles --}}
                @if($results['posts']->isNotEmpty())
                    <div class="mb-16">
                        <h2 class="text-h3 text-blue-black mb-6">Articles</h2>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10">
                            @foreach($results['posts'] as $post)
                                <x-cards.horizontal :post="$post" />
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <p class="text-body-md text-blue-black/50 text-center">No results found. Try a different search term.</p>
            @endif
        @endif
    </div>
</div>
@endsection
