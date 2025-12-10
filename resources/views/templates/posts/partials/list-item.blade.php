{{-- Recursive List Item - supports nested lists from EditorJS List v2.x --}}
@php
    // Handle both old format (string) and new format (object with content/items)
    $content = is_array($item) ? ($item['content'] ?? '') : $item;
    $nestedItems = is_array($item) ? ($item['items'] ?? []) : [];
@endphp

<li>
    <span>{!! $content !!}</span>
    @if(count($nestedItems) > 0)
        @if($ordered ?? false)
            <ol class="list-decimal list-inside space-y-1 mt-1 ml-4">
                @foreach($nestedItems as $nestedItem)
                    @include('templates.posts.partials.list-item', ['item' => $nestedItem, 'ordered' => true])
                @endforeach
            </ol>
        @else
            <ul class="list-disc list-inside space-y-1 mt-1 ml-4">
                @foreach($nestedItems as $nestedItem)
                    @include('templates.posts.partials.list-item', ['item' => $nestedItem, 'ordered' => false])
                @endforeach
            </ul>
        @endif
    @endif
</li>
