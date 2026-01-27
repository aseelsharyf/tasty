{{-- resources/views/components/blocks/link.blade.php --}}
{{-- EditorJS Link Tool Block --}}

@props([
    'link' => '#',
    'meta' => [],
])

@php
    $title = $meta['title'] ?? parse_url($link, PHP_URL_HOST) ?? 'Link';
    $description = $meta['description'] ?? null;
    $image = $meta['image']['url'] ?? null;
    $domain = parse_url($link, PHP_URL_HOST);
@endphp

<a
    href="{{ $link }}"
    target="_blank"
    rel="noopener"
    class="group block no-underline bg-white border border-tasty-blue-black/10 rounded-lg overflow-hidden hover:border-tasty-blue-black/20 transition-colors max-w-[540px] mx-auto"
>
    <div class="flex">
        @if($image)
            <div class="w-28 sm:w-32 flex-shrink-0 overflow-hidden">
                <img
                    src="{{ $image }}"
                    alt="{{ $title }}"
                    class="w-full h-full object-cover"
                />
            </div>
        @endif
        <div class="p-4 flex-1 flex flex-col justify-center min-w-0">
            <p class="text-body-md font-semibold text-tasty-blue-black mb-1 line-clamp-2">
                {{ $title }}
            </p>
            @if($description)
                <p class="text-body-sm text-tasty-blue-black/60 line-clamp-2 mb-2">
                    {{ $description }}
                </p>
            @endif
            <div class="flex items-center gap-1.5 text-caption text-tasty-blue-black/40">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                </svg>
                <span class="truncate">{{ $domain }}</span>
            </div>
        </div>
    </div>
</a>
