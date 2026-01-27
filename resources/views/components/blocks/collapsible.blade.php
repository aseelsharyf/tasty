{{-- EditorJS Collapsible Block --}}
{{-- A collapsible section with heading and nested blocks --}}

@props([
    'title' => '',
    'content' => [],
    'defaultExpanded' => true,
    'isRtl' => false,
])

<div
    x-data="{ expanded: {{ $defaultExpanded ? 'true' : 'false' }} }"
    class="w-full"
>
    {{-- Header --}}
    <button
        @click="expanded = !expanded"
        :aria-expanded="expanded"
        class="w-full flex items-center justify-between text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-tasty-blue-black/20 group"
    >
        <h3 class="text-h4 text-tasty-blue-black {{ $isRtl ? 'font-dhivehi text-right' : '' }}">
            {!! $title !!}
        </h3>
        <span class="flex items-center justify-center w-8 h-8 text-tasty-blue-black/60 group-hover:text-tasty-blue-black transition-colors flex-shrink-0 {{ $isRtl ? 'mr-4' : 'ml-4' }}">
            {{-- Minus icon when expanded --}}
            <svg
                x-show="expanded"
                xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
            </svg>
            {{-- Plus icon when collapsed --}}
            <svg
                x-show="!expanded"
                x-cloak
                xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
        </span>
    </button>

    {{-- Horizontal line under heading - black border --}}
    <div class="border-t border-tasty-blue-black"></div>

    {{-- Content --}}
    <div
        x-show="expanded"
        x-collapse
        x-cloak
    >
        <div class="py-2">
            {{-- Check for slot content first, then fall back to EditorJS blocks --}}
            @if($slot->isNotEmpty())
                {{ $slot }}
            @elseif(!empty($content['blocks']))
                {{-- Render nested blocks recursively --}}
                @include('templates.posts.partials.content-blocks', [
                    'blocks' => $content['blocks'],
                    'isRtl' => $isRtl,
                    'contentWidth' => 'w-full',
                    'fullWidth' => 'w-full',
                ])
            @endif
        </div>
    </div>
</div>
