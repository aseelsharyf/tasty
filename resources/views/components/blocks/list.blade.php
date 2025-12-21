{{-- resources/views/components/blocks/list.blade.php --}}
{{-- EditorJS List Block --}}

@props([
    'items' => [],
    'style' => 'unordered', // unordered, ordered, checklist
    'isRtl' => false,
])

@php
    $alignClass = $isRtl ? 'text-right' : '';

    // Helper to extract content from list item (handles both string and nested format)
    if (!function_exists('getListItemContent')) {
        function getListItemContent($item) {
            if (is_string($item)) {
                return $item;
            }
            return $item['content'] ?? '';
        }
    }

    if (!function_exists('getListItemChildren')) {
        function getListItemChildren($item) {
            if (is_array($item) && isset($item['items'])) {
                return $item['items'];
            }
            return [];
        }
    }

    if (!function_exists('isItemChecked')) {
        function isItemChecked($item) {
            if (is_array($item)) {
                return $item['meta']['checked'] ?? $item['checked'] ?? false;
            }
            return false;
        }
    }
@endphp

@if($style === 'ordered')
    <ol class="text-body-lg text-tasty-blue-black/90 list-decimal {{ $isRtl ? 'pr-6' : 'pl-6' }} space-y-2 {{ $alignClass }}">
        @foreach($items as $item)
            <li>
                {!! getListItemContent($item) !!}
                @if(count(getListItemChildren($item)) > 0)
                    <x-blocks.list :items="getListItemChildren($item)" style="ordered" :isRtl="$isRtl" />
                @endif
            </li>
        @endforeach
    </ol>
@elseif($style === 'checklist')
    <ul class="text-body-lg text-tasty-blue-black/90 space-y-3 {{ $alignClass }}">
        @foreach($items as $item)
            @php
                $content = is_array($item) ? ($item['content'] ?? $item['text'] ?? '') : $item;
                $checked = isItemChecked($item);
            @endphp
            <li class="flex items-start gap-3">
                <span class="mt-1.5 flex-shrink-0">
                    @if($checked)
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    @else
                        <svg class="w-5 h-5 text-tasty-blue-black/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"></rect>
                        </svg>
                    @endif
                </span>
                <span class="{{ $checked ? 'line-through text-tasty-blue-black/50' : '' }}">{!! $content !!}</span>
            </li>
        @endforeach
    </ul>
@else
    {{-- Unordered list --}}
    <ul class="text-body-lg text-tasty-blue-black/90 list-disc {{ $isRtl ? 'pr-6' : 'pl-6' }} space-y-2 {{ $alignClass }}">
        @foreach($items as $item)
            <li>
                {!! getListItemContent($item) !!}
                @if(count(getListItemChildren($item)) > 0)
                    <x-blocks.list :items="getListItemChildren($item)" style="unordered" :isRtl="$isRtl" />
                @endif
            </li>
        @endforeach
    </ul>
@endif
