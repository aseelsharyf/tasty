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
    class="block no-underline border border-tasty-blue-black/10 overflow-hidden hover:border-tasty-blue-black/30 transition-colors"
>
    <div class="flex">
        @if($image)
            <div class="w-32 md:w-48 flex-shrink-0">
                <img
                    src="{{ $image }}"
                    alt="{{ $title }}"
                    class="w-full h-full object-cover"
                />
            </div>
        @endif
        <div class="p-4 md:p-6 flex-1">
            <p class="text-body-md font-medium text-tasty-blue-black mb-1">
                {{ $title }}
            </p>
            @if($description)
                <p class="text-body-sm text-tasty-blue-black/60 line-clamp-2">
                    {{ $description }}
                </p>
            @endif
            <p class="text-body-sm text-tasty-blue-black/40 mt-2 truncate">
                {{ $domain }}
            </p>
        </div>
    </div>
</a>
