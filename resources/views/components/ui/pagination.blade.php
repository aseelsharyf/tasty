@props([
    'paginator',
])

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center gap-2">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 text-gray-400 cursor-not-allowed">
                <i class="fa-solid fa-chevron-left text-sm"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white text-stone-900 hover:bg-tasty-yellow transition-colors duration-200 shadow-sm">
                <i class="fa-solid fa-chevron-left text-sm"></i>
            </a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if ($page == $paginator->currentPage())
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-tasty-yellow text-stone-900 font-bold text-sm">
                    {{ $page }}
                </span>
            @else
                <a href="{{ $url }}" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white text-stone-900 hover:bg-tasty-yellow transition-colors duration-200 shadow-sm font-medium text-sm">
                    {{ $page }}
                </a>
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white text-stone-900 hover:bg-tasty-yellow transition-colors duration-200 shadow-sm">
                <i class="fa-solid fa-chevron-right text-sm"></i>
            </a>
        @else
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 text-gray-400 cursor-not-allowed">
                <i class="fa-solid fa-chevron-right text-sm"></i>
            </span>
        @endif
    </nav>
@endif
