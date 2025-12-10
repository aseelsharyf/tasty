{{-- EditorJS Content Blocks Renderer --}}
@foreach($blocks as $block)
    @php
        $type = $block['type'] ?? null;
        $data = $block['data'] ?? [];
    @endphp

    @switch($type)
        {{-- Paragraph --}}
        @case('paragraph')
            <p>{!! $data['text'] ?? '' !!}</p>
            @break

        {{-- Header --}}
        @case('header')
            @php $level = $data['level'] ?? 2; @endphp
            <h{{ $level }}>{!! $data['text'] ?? '' !!}</h{{ $level }}>
            @break

        {{-- List --}}
        @case('list')
            @php $style = $data['style'] ?? 'unordered'; @endphp
            @if($style === 'ordered')
                <ol>
                    @foreach($data['items'] ?? [] as $item)
                        <li>{!! is_array($item) ? ($item['content'] ?? '') : $item !!}</li>
                    @endforeach
                </ol>
            @else
                <ul>
                    @foreach($data['items'] ?? [] as $item)
                        <li>{!! is_array($item) ? ($item['content'] ?? '') : $item !!}</li>
                    @endforeach
                </ul>
            @endif
            @break

        {{-- Quote --}}
        @case('quote')
            <blockquote>
                <p>{!! $data['text'] ?? '' !!}</p>
                @if($data['caption'] ?? null)
                    <cite>{!! $data['caption'] !!}</cite>
                @endif
            </blockquote>
            @break

        {{-- Image (legacy) --}}
        @case('image')
            <figure class="my-8">
                <img
                    src="{{ $data['file']['url'] ?? $data['url'] ?? '' }}"
                    alt="{{ $data['caption'] ?? '' }}"
                    class="rounded-lg w-full"
                />
                @if($data['caption'] ?? null)
                    <figcaption class="text-center text-sm text-gray-500 mt-2">{!! $data['caption'] !!}</figcaption>
                @endif
            </figure>
            @break

        {{-- Media Block (supports multiple items with layout) --}}
        @case('media')
            @php
                $items = $data['items'] ?? [];
                $layout = $data['layout'] ?? 'single';
                $gridColumns = $data['gridColumns'] ?? 3;
            @endphp

            @if(count($items) === 1 || $layout === 'single')
                {{-- Single image --}}
                @if(count($items) > 0)
                    <figure class="my-8">
                        @if($items[0]['is_video'] ?? false)
                            <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                                <img
                                    src="{{ $items[0]['thumbnail_url'] ?? '' }}"
                                    alt="{{ $items[0]['alt_text'] ?? 'Video' }}"
                                    class="w-full h-full object-cover opacity-80"
                                />
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-16 h-16 bg-white/90 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-900 ml-1" fill="currentColor" viewBox="0 0 24 24">
                                            <polygon points="5 3 19 12 5 21 5 3"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @else
                            <img
                                src="{{ $items[0]['url'] ?? $items[0]['thumbnail_url'] ?? '' }}"
                                alt="{{ $items[0]['alt_text'] ?? '' }}"
                                class="rounded-lg w-full"
                            />
                        @endif
                        @if($items[0]['caption'] ?? null)
                            <figcaption class="text-center text-sm text-gray-500 mt-2">{{ $items[0]['caption'] }}</figcaption>
                        @endif
                        @if($items[0]['credit_display'] ?? null)
                            <p class="text-center text-xs text-gray-400 mt-1">
                                @if($items[0]['credit_display']['role'] ?? null)
                                    {{ $items[0]['credit_display']['role'] }}:
                                @endif
                                @if($items[0]['credit_display']['url'] ?? null)
                                    <a href="{{ $items[0]['credit_display']['url'] }}" target="_blank" rel="noopener" class="hover:underline">
                                        {{ $items[0]['credit_display']['name'] }}
                                    </a>
                                @else
                                    {{ $items[0]['credit_display']['name'] }}
                                @endif
                            </p>
                        @endif
                    </figure>
                @endif

            @elseif($layout === 'grid')
                {{-- Grid layout --}}
                <div class="my-8 grid gap-4 grid-cols-{{ $gridColumns }}">
                    @foreach($items as $item)
                        <figure class="m-0">
                            <img
                                src="{{ $item['url'] ?? $item['thumbnail_url'] ?? '' }}"
                                alt="{{ $item['alt_text'] ?? '' }}"
                                class="rounded-lg w-full h-full object-cover aspect-square"
                            />
                            @if($item['caption'] ?? null)
                                <figcaption class="text-center text-xs text-gray-500 mt-1">{{ $item['caption'] }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>

            @elseif($layout === 'carousel')
                {{-- Carousel layout --}}
                <div class="my-8 overflow-x-auto">
                    <div class="flex gap-4 pb-4" style="width: max-content;">
                        @foreach($items as $item)
                            <figure class="m-0 flex-shrink-0" style="width: 300px;">
                                <img
                                    src="{{ $item['url'] ?? $item['thumbnail_url'] ?? '' }}"
                                    alt="{{ $item['alt_text'] ?? '' }}"
                                    class="rounded-lg w-full h-48 object-cover"
                                />
                                @if($item['caption'] ?? null)
                                    <figcaption class="text-center text-xs text-gray-500 mt-1">{{ $item['caption'] }}</figcaption>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                </div>

            @else
                {{-- Fallback: grid --}}
                <div class="my-8 grid gap-4 grid-cols-3">
                    @foreach($items as $item)
                        <figure class="m-0">
                            <img
                                src="{{ $item['url'] ?? $item['thumbnail_url'] ?? '' }}"
                                alt="{{ $item['alt_text'] ?? '' }}"
                                class="rounded-lg w-full h-full object-cover aspect-square"
                            />
                        </figure>
                    @endforeach
                </div>
            @endif
            @break

        {{-- Delimiter --}}
        @case('delimiter')
            <hr class="my-8" />
            @break

        {{-- Code --}}
        @case('code')
            <pre class="rounded-lg bg-gray-100 dark:bg-gray-800 p-4 overflow-x-auto my-6"><code>{{ $data['code'] ?? '' }}</code></pre>
            @break

        {{-- Table --}}
        @case('table')
            <div class="overflow-x-auto my-6">
                <table class="min-w-full border-collapse">
                    <tbody>
                        @foreach($data['content'] ?? [] as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">{!! $cell !!}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @break

        {{-- Link Tool --}}
        @case('linkTool')
            <div class="my-6">
                <a
                    href="{{ $data['link'] ?? '#' }}"
                    target="_blank"
                    rel="noopener"
                    class="block no-underline border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:border-blue-500 transition-colors"
                >
                    <div class="flex">
                        @if($data['meta']['image']['url'] ?? null)
                            <div class="w-32 flex-shrink-0">
                                <img
                                    src="{{ $data['meta']['image']['url'] }}"
                                    alt="{{ $data['meta']['title'] ?? '' }}"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                        @endif
                        <div class="p-4">
                            <p class="font-medium text-gray-900 dark:text-gray-100 mb-1">
                                {{ $data['meta']['title'] ?? $data['link'] ?? 'Link' }}
                            </p>
                            @if($data['meta']['description'] ?? null)
                                <p class="text-sm text-gray-500 line-clamp-2">{{ $data['meta']['description'] }}</p>
                            @endif
                        </div>
                    </div>
                </a>
            </div>
            @break

        {{-- Raw HTML --}}
        @case('raw')
            <div class="my-6">{!! $data['html'] ?? '' !!}</div>
            @break

        {{-- Embed --}}
        @case('embed')
            <div class="my-8 aspect-video">
                <iframe
                    src="{{ $data['embed'] ?? '' }}"
                    class="w-full h-full rounded-lg"
                    frameborder="0"
                    allowfullscreen
                ></iframe>
            </div>
            @if($data['caption'] ?? null)
                <p class="text-center text-sm text-gray-500 mt-2">{!! $data['caption'] !!}</p>
            @endif
            @break

        @default
            {{-- Unknown block type - render as JSON for debugging --}}
            @if(config('app.debug'))
                <div class="my-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg text-xs">
                    <p class="font-mono text-yellow-600 dark:text-yellow-400">Unknown block: {{ $type }}</p>
                </div>
            @endif
    @endswitch
@endforeach
