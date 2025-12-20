{{-- resources/views/components/blocks/table.blade.php --}}
{{-- EditorJS Table Block --}}

@props([
    'content' => [],
    'withHeadings' => false,
    'isRtl' => false,
])

@php
    $alignClass = $isRtl ? 'text-right' : '';
@endphp

<div class="overflow-x-auto">
    <table class="w-full border-collapse {{ $alignClass }}">
        @if($withHeadings && count($content) > 0)
            <thead>
                <tr>
                    @foreach($content[0] as $cell)
                        <th class="text-body-md font-semibold text-tasty-blue-black border-b-2 border-tasty-blue-black/10 px-4 py-3 text-left">
                            {!! $cell !!}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($content, 1) as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td class="text-body-md text-tasty-blue-black/90 border-b border-tasty-blue-black/10 px-4 py-3">
                                {!! $cell !!}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        @else
            <tbody>
                @foreach($content as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td class="text-body-md text-tasty-blue-black/90 border-b border-tasty-blue-black/10 px-4 py-3">
                                {!! $cell !!}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        @endif
    </table>
</div>
