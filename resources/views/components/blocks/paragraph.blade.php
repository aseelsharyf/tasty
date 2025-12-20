{{-- resources/views/components/blocks/paragraph.blade.php --}}
{{-- EditorJS Paragraph Block --}}

@props([
    'text' => '',
    'isRtl' => false,
])

<p class="text-body-lg text-tasty-blue-black/90 {{ $isRtl ? 'text-right' : '' }}">
    {!! $text !!}
</p>
