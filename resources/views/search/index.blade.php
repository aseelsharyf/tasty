@extends('layouts.app')

@section('content')
<div class="pt-[96px] md:pt-[112px]">
    {{-- Search Header --}}
    <div class="bg-tasty-yellow py-12 lg:py-16">
        <div class="max-w-[1200px] mx-auto px-4 lg:px-10">
            <h1 class="text-h2 text-tasty-blue-black text-center mb-8">Search</h1>

            {{-- Search Form --}}
            <form action="{{ route('search') }}" method="GET" class="max-w-[600px] mx-auto">
                <div class="relative">
                    <input
                        type="text"
                        name="q"
                        value="{{ $query }}"
                        placeholder="Search articles, categories, tags..."
                        class="w-full px-6 py-4 pr-14 text-lg bg-white rounded-full border-2 border-tasty-blue-black/10 focus:border-tasty-blue-black/30 focus:outline-none transition-colors"
                        autofocus
                    />
                    <button type="submit" class="absolute right-4 top-1/2 -translate-y-1/2 p-2 text-tasty-blue-black hover:text-tasty-blue-black/70 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                    </button>
                </div>

                {{-- Type Filter --}}
                <div class="flex items-center justify-center gap-4 mt-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="type" value="all" {{ $type === 'all' ? 'checked' : '' }} class="w-4 h-4 accent-tasty-blue-black" onchange="this.form.submit()">
                        <span class="text-sm text-tasty-blue-black">All</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="type" value="posts" {{ $type === 'posts' ? 'checked' : '' }} class="w-4 h-4 accent-tasty-blue-black" onchange="this.form.submit()">
                        <span class="text-sm text-tasty-blue-black">Articles</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="type" value="categories" {{ $type === 'categories' ? 'checked' : '' }} class="w-4 h-4 accent-tasty-blue-black" onchange="this.form.submit()">
                        <span class="text-sm text-tasty-blue-black">Categories</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="type" value="tags" {{ $type === 'tags' ? 'checked' : '' }} class="w-4 h-4 accent-tasty-blue-black" onchange="this.form.submit()">
                        <span class="text-sm text-tasty-blue-black">Tags</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="type" value="authors" {{ $type === 'authors' ? 'checked' : '' }} class="w-4 h-4 accent-tasty-blue-black" onchange="this.form.submit()">
                        <span class="text-sm text-tasty-blue-black">Authors</span>
                    </label>
                </div>
            </form>
        </div>
    </div>

    {{-- Search Results --}}
    <div class="bg-off-white py-12 lg:py-16">
        <div class="max-w-[1200px] mx-auto px-4 lg:px-10">
            @if($query)
                <p class="text-sm text-tasty-blue-black/60 mb-8 text-center">
                    Found {{ $totalCount }} result{{ $totalCount !== 1 ? 's' : '' }} for "{{ $query }}"
                </p>

                @if($totalCount === 0)
                    <div class="text-center py-12">
                        <p class="text-lg text-tasty-blue-black/70">No results found for your search.</p>
                        <p class="text-sm text-tasty-blue-black/50 mt-2">Try different keywords or browse our categories.</p>
                    </div>
                @else
                    {{-- Posts Results --}}
                    @if($results['posts']->isNotEmpty())
                        <div class="mb-12">
                            <h2 class="text-h4 text-tasty-blue-black mb-6">Articles</h2>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10">
                                @foreach($results['posts'] as $post)
                                    <x-cards.horizontal :post="$post" />
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Categories Results --}}
                    @if($results['categories']->isNotEmpty())
                        <div class="mb-12">
                            <h2 class="text-h4 text-tasty-blue-black mb-6">Categories</h2>
                            <div class="flex flex-wrap gap-3">
                                @foreach($results['categories'] as $category)
                                    <a href="{{ route('category.show', $category->slug) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-tasty-yellow rounded-full transition-colors">
                                        <span class="font-medium text-tasty-blue-black">{{ $category->name }}</span>
                                        <span class="text-sm text-tasty-blue-black/50">{{ $category->posts_count }} posts</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Tags Results --}}
                    @if($results['tags']->isNotEmpty())
                        <div class="mb-12">
                            <h2 class="text-h4 text-tasty-blue-black mb-6">Tags</h2>
                            <div class="flex flex-wrap gap-3">
                                @foreach($results['tags'] as $tag)
                                    <a href="{{ route('tag.show', $tag->slug) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-tasty-yellow rounded-full transition-colors">
                                        <span class="font-medium text-tasty-blue-black">{{ $tag->name }}</span>
                                        <span class="text-sm text-tasty-blue-black/50">{{ $tag->posts_count }} posts</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Authors Results --}}
                    @if($results['authors']->isNotEmpty())
                        <div class="mb-12">
                            <h2 class="text-h4 text-tasty-blue-black mb-6">Authors</h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($results['authors'] as $author)
                                    <a href="{{ route('author.show', $author->username) }}" class="flex items-center gap-4 p-4 bg-white hover:bg-tasty-yellow/20 rounded-lg transition-colors">
                                        @if($author->avatar_url)
                                            <img src="{{ $author->avatar_url }}" alt="{{ $author->name }}" class="w-12 h-12 rounded-full object-cover">
                                        @else
                                            <div class="w-12 h-12 rounded-full bg-tasty-blue-black/10 flex items-center justify-center">
                                                <span class="text-lg font-medium text-tasty-blue-black">{{ substr($author->name, 0, 1) }}</span>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="font-medium text-tasty-blue-black">{{ $author->name }}</p>
                                            <p class="text-sm text-tasty-blue-black/50">Author</p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            @else
                <div class="text-center py-12">
                    <p class="text-lg text-tasty-blue-black/70">Enter a search term to find articles, categories, tags, and authors.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
