{{-- EditorJS Content Blocks Renderer --}}
<div class="space-y-6">
@foreach($blocks as $block)
    @php
        $type = $block['type'] ?? null;
        $data = $block['data'] ?? [];
    @endphp

    @switch($type)
        {{-- Paragraph --}}
        @case('paragraph')
            <p class="leading-relaxed">{!! $data['text'] ?? '' !!}</p>
            @break

        {{-- Header --}}
        @case('header')
            @php $level = $data['level'] ?? 2; @endphp
            <h{{ $level }} class="mt-8 first:mt-0">{!! $data['text'] ?? '' !!}</h{{ $level }}>
            @break

        {{-- List (supports both old string format and new nested format) --}}
        @case('list')
            @php
                $style = $data['style'] ?? 'unordered';
                $items = $data['items'] ?? [];
            @endphp
            @if($style === 'ordered')
                <ol class="list-decimal list-inside space-y-2 pl-2">
                    @foreach($items as $item)
                        @include('templates.posts.partials.list-item', ['item' => $item, 'ordered' => true])
                    @endforeach
                </ol>
            @elseif($style === 'checklist')
                <ul class="space-y-2">
                    @foreach($items as $item)
                        @php
                            $content = is_array($item) ? ($item['content'] ?? '') : $item;
                            $checked = is_array($item) ? ($item['meta']['checked'] ?? false) : false;
                        @endphp
                        <li class="flex items-start gap-2">
                            <span class="mt-1 flex-shrink-0">
                                @if($checked)
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"></rect>
                                    </svg>
                                @endif
                            </span>
                            <span class="{{ $checked ? 'line-through text-gray-500' : '' }}">{!! $content !!}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <ul class="list-disc list-inside space-y-2 pl-2">
                    @foreach($items as $item)
                        @include('templates.posts.partials.list-item', ['item' => $item, 'ordered' => false])
                    @endforeach
                </ul>
            @endif
            @break

        {{-- Checklist (legacy separate block type) --}}
        @case('checklist')
            <ul class="space-y-2">
                @foreach($data['items'] ?? [] as $item)
                    @php
                        $text = is_array($item) ? ($item['text'] ?? '') : $item;
                        $checked = is_array($item) ? ($item['checked'] ?? false) : false;
                    @endphp
                    <li class="flex items-start gap-2">
                        <span class="mt-1 flex-shrink-0">
                            @if($checked)
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"></rect>
                                </svg>
                            @endif
                        </span>
                        <span class="{{ $checked ? 'line-through text-gray-500' : '' }}">{!! $text !!}</span>
                    </li>
                @endforeach
            </ul>
            @break

        {{-- Quote --}}
        @case('quote')
            <blockquote class="border-l-4 border-gray-300 dark:border-gray-600 pl-6 py-2 my-6 italic">
                <p class="text-lg">{!! $data['text'] ?? '' !!}</p>
                @if($data['caption'] ?? null)
                    <cite class="block mt-2 text-sm text-gray-500 not-italic">&mdash; {!! $data['caption'] !!}</cite>
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
                $gridColumns = min(12, max(1, (int)($data['gridColumns'] ?? 3)));
                $gap = $data['gap'] ?? 'md';

                // Gap classes
                $gapClasses = [
                    'none' => 'gap-0',
                    'xs' => 'gap-1',
                    'sm' => 'gap-2',
                    'md' => 'gap-4',
                    'lg' => 'gap-6',
                    'xl' => 'gap-8',
                ];
                $gapClass = $gapClasses[$gap] ?? 'gap-4';

                // For carousel, calculate item width percentage
                $carouselItemWidth = 100 / $gridColumns;
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
                {{-- Grid layout (supports 1-12 columns) - uses inline style for reliable column support --}}
                <div class="my-8 grid {{ $gapClass }}" style="grid-template-columns: repeat({{ $gridColumns }}, minmax(0, 1fr));">
                    @foreach($items as $item)
                        <figure class="m-0">
                            @if($item['is_video'] ?? false)
                                <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                                    <img
                                        src="{{ $item['thumbnail_url'] ?? '' }}"
                                        alt="{{ $item['alt_text'] ?? 'Video' }}"
                                        class="w-full h-full object-cover opacity-80"
                                    />
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div class="w-12 h-12 bg-white/90 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-900 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                                <polygon points="5 3 19 12 5 21 5 3"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <img
                                    src="{{ $item['url'] ?? $item['thumbnail_url'] ?? '' }}"
                                    alt="{{ $item['alt_text'] ?? '' }}"
                                    class="rounded-lg w-full h-full object-cover aspect-square"
                                />
                            @endif
                            @if($item['caption'] ?? null)
                                <figcaption class="text-center text-xs text-gray-500 mt-1">{{ $item['caption'] }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>

            @elseif($layout === 'carousel')
                {{-- Carousel layout (horizontal scroll) --}}
                <div class="my-8 overflow-x-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200">
                    <div class="flex {{ $gapClass }} pb-4" style="width: max-content;">
                        @foreach($items as $item)
                            <figure class="m-0 flex-shrink-0" style="width: calc({{ $carouselItemWidth }}vw - 2rem); min-width: 150px; max-width: 400px;">
                                @if($item['is_video'] ?? false)
                                    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                                        <img
                                            src="{{ $item['thumbnail_url'] ?? '' }}"
                                            alt="{{ $item['alt_text'] ?? 'Video' }}"
                                            class="w-full h-full object-cover opacity-80"
                                        />
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="w-12 h-12 bg-white/90 rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-900 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <img
                                        src="{{ $item['url'] ?? $item['thumbnail_url'] ?? '' }}"
                                        alt="{{ $item['alt_text'] ?? '' }}"
                                        class="rounded-lg w-full aspect-[4/3] object-cover"
                                    />
                                @endif
                                @if($item['caption'] ?? null)
                                    <figcaption class="text-center text-xs text-gray-500 mt-1">{{ $item['caption'] }}</figcaption>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                </div>

            @else
                {{-- Fallback: grid --}}
                <div class="my-8 grid {{ $gapClass }}" style="grid-template-columns: repeat({{ $gridColumns }}, minmax(0, 1fr));">
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
            <div class="flex items-center justify-center my-10">
                <span class="text-2xl text-gray-400 tracking-widest">* * *</span>
            </div>
            @break

        {{-- Code --}}
        @case('code')
            <pre class="rounded-lg bg-gray-100 dark:bg-gray-800 p-4 overflow-x-auto my-6 text-sm"><code>{{ $data['code'] ?? '' }}</code></pre>
            @break

        {{-- Table --}}
        @case('table')
            @php
                $withHeadings = $data['withHeadings'] ?? false;
                $content = $data['content'] ?? [];
            @endphp
            <div class="overflow-x-auto my-6">
                <table class="min-w-full border-collapse border border-gray-200 dark:border-gray-700">
                    @if($withHeadings && count($content) > 0)
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                @foreach($content[0] as $cell)
                                    <th class="border border-gray-200 dark:border-gray-700 px-4 py-2 text-left font-semibold">{!! $cell !!}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($content, 1) as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td class="border border-gray-200 dark:border-gray-700 px-4 py-2">{!! $cell !!}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    @else
                        <tbody>
                            @foreach($content as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td class="border border-gray-200 dark:border-gray-700 px-4 py-2">{!! $cell !!}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    @endif
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
                    class="block no-underline border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:border-primary-500 transition-colors"
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
                        <div class="p-4 flex-1">
                            <p class="font-medium text-gray-900 dark:text-gray-100 mb-1">
                                {{ $data['meta']['title'] ?? $data['link'] ?? 'Link' }}
                            </p>
                            @if($data['meta']['description'] ?? null)
                                <p class="text-sm text-gray-500 line-clamp-2">{{ $data['meta']['description'] }}</p>
                            @endif
                            <p class="text-xs text-gray-400 mt-2 truncate">{{ parse_url($data['link'] ?? '', PHP_URL_HOST) }}</p>
                        </div>
                    </div>
                </a>
            </div>
            @break

        {{-- Warning/Alert --}}
        @case('warning')
            <div class="my-6 p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                @if($data['title'] ?? null)
                    <p class="font-semibold text-yellow-800 dark:text-yellow-200 mb-1">{{ $data['title'] }}</p>
                @endif
                <p class="text-yellow-700 dark:text-yellow-300">{!! $data['message'] ?? '' !!}</p>
            </div>
            @break

        {{-- Raw HTML --}}
        @case('raw')
            <div class="my-6">{!! $data['html'] ?? '' !!}</div>
            @break

        {{-- Embed --}}
        @case('embed')
            <div class="my-8">
                <div class="aspect-video">
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
            </div>
            @break

        {{-- Attaches/Files --}}
        @case('attaches')
            <div class="my-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                <a href="{{ $data['file']['url'] ?? '#' }}" target="_blank" rel="noopener" class="flex items-center gap-3 no-underline hover:bg-gray-50 dark:hover:bg-gray-800 -m-4 p-4 rounded-lg transition-colors">
                    <div class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $data['title'] ?? $data['file']['name'] ?? 'File' }}</p>
                        @if($data['file']['size'] ?? null)
                            <p class="text-xs text-gray-500">{{ number_format($data['file']['size'] / 1024, 1) }} KB</p>
                        @endif
                    </div>
                </a>
            </div>
            @break

        @default
            {{-- Unknown block type - render as JSON for debugging --}}
            @if(config('app.debug'))
                <div class="my-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg text-xs">
                    <p class="font-mono text-yellow-600 dark:text-yellow-400">Unknown block: {{ $type }}</p>
                    <pre class="mt-2 text-yellow-600 dark:text-yellow-400 overflow-auto">{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
    @endswitch
@endforeach
</div>
