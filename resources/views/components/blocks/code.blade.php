{{-- resources/views/components/blocks/code.blade.php --}}
{{-- EditorJS Code Block --}}

@props([
    'code' => '',
    'language' => null,
])

<pre class="bg-tasty-light-gray p-6 overflow-x-auto font-mono text-sm text-tasty-blue-black"><code>{{ $code }}</code></pre>
