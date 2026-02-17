{{-- EditorJS Content Blocks Renderer --}}
{{-- Uses component-based blocks from resources/views/components/blocks/ --}}

@props([
    'blocks' => [],
    'isRtl' => false,
    'contentWidth' => 'max-w-[894px]', // Content width from Figma (894px)
    'fullWidth' => 'w-full max-w-[1146px]', // Full width for media (1146px)
    'adCodeAfterFirstParagraph' => null, // Ad code to insert after first paragraph
])

@php
    $paragraphCount = 0;
    $adInserted = false;
@endphp

<div class="flex flex-col items-center gap-9">
    @foreach($blocks as $block)
        @php
            $type = $block['type'] ?? null;
            $data = $block['data'] ?? [];
        @endphp

        @switch($type)
            {{-- Paragraph --}}
            @case('paragraph')
                <div class="{{ $contentWidth }} w-full px-4 lg:px-0">
                    <x-blocks.paragraph
                        :text="$data['text'] ?? ''"
                        :isRtl="$isRtl"
                    />
                </div>
                @php $paragraphCount++; @endphp
                {{-- Ad Slot: After first paragraph --}}
                @if($paragraphCount === 1 && !$adInserted && $adCodeAfterFirstParagraph)
                    <div class="w-full bg-off-white pt-8 pb-4">
                        <div class="ad-slot flex items-center justify-center">
                            {!! $adCodeAfterFirstParagraph !!}
                        </div>
                    </div>
                    @php $adInserted = true; @endphp
                @endif
                @break

            {{-- Header --}}
            @case('header')
                <div class="{{ $contentWidth }} w-full px-4 lg:px-0">
                    <x-blocks.header
                        :text="$data['text'] ?? ''"
                        :level="$data['level'] ?? 2"
                        :isRtl="$isRtl"
                    />
                </div>
                @break

            {{-- List --}}
            @case('list')
                <div class="{{ $contentWidth }} w-full px-4 lg:px-0">
                    <x-blocks.list
                        :items="$data['items'] ?? []"
                        :style="$data['style'] ?? 'unordered'"
                        :isRtl="$isRtl"
                    />
                </div>
                @break

            {{-- Checklist (legacy) --}}
            @case('checklist')
                <div class="{{ $contentWidth }} w-full px-4 lg:px-0">
                    <x-blocks.list
                        :items="$data['items'] ?? []"
                        style="checklist"
                        :isRtl="$isRtl"
                    />
                </div>
                @break

            {{-- Quote --}}
            @case('quote')
                @php
                    $quoteDisplayType = $data['displayType'] ?? 'default';
                    // Large and small quotes handle their own container width (1440px)
                    // Default quotes use content width (894px)
                @endphp
                @if(in_array($quoteDisplayType, ['large', 'small']))
                    {{-- Large/Small quotes are full-width and manage their own container --}}
                    <div class="w-full">
                        <x-blocks.quote
                            :text="$data['text'] ?? ''"
                            :caption="$data['caption'] ?? ''"
                            :alignment="$data['alignment'] ?? 'left'"
                            :displayType="$quoteDisplayType"
                            :author="$data['author'] ?? null"
                            :isRtl="$isRtl"
                        />
                    </div>
                @else
                    {{-- Default quotes use content width --}}
                    <div class="{{ $contentWidth }} w-full px-4 lg:px-0">
                        <x-blocks.quote
                            :text="$data['text'] ?? ''"
                            :caption="$data['caption'] ?? ''"
                            :alignment="$data['alignment'] ?? 'left'"
                            :displayType="$quoteDisplayType"
                            :author="$data['author'] ?? null"
                            :isRtl="$isRtl"
                        />
                    </div>
                @endif
                @break

            {{-- Collapsible Block --}}
            @case('collapsible')
                <div class="{{ $contentWidth }} w-full px-4 lg:px-0">
                    <x-blocks.collapsible
                        :title="$data['title'] ?? ''"
                        :content="$data['content'] ?? ['blocks' => []]"
                        :defaultExpanded="$data['defaultExpanded'] ?? true"
                        :isRtl="$isRtl"
                    />
                </div>
                @break

            {{-- Media Block --}}
            @case('media')
                @php
                    $items = $data['items'] ?? [];
                    $layout = $data['layout'] ?? 'single';
                    $displayWidth = $data['displayWidth'] ?? 'default';
                    $singleImageDisplay = $data['singleImageDisplay'] ?? 'fullWidth';
                    $isSingleImage = count($items) === 1 || $layout === 'single';
                    // Check if first item is a video
                    $firstItemIsVideo = isset($items[0]) && ($items[0]['is_video'] ?? false);
                    // Single images: fullWidth mode uses edge-to-edge, contained/portrait use their own max-width
                    // Videos always use content width, multi-image respects displayWidth setting
                    $isFullScreen = $displayWidth === 'fullScreen' || ($isSingleImage && !$firstItemIsVideo && $singleImageDisplay === 'fullWidth');
                @endphp
                @if($isSingleImage && !$firstItemIsVideo && in_array($singleImageDisplay, ['contained', 'portrait']))
                    {{-- Contained/Portrait: centered with their own max-width, slightly wider than body text --}}
                    <div class="w-full py-9">
                        <x-blocks.media
                            :items="$items"
                            :layout="$layout"
                            :gridColumns="$data['gridColumns'] ?? 3"
                            :gap="$data['gap'] ?? 'md'"
                            :isRtl="$isRtl"
                            :fullWidth="false"
                            :singleImageDisplay="$singleImageDisplay"
                        />
                    </div>
                @elseif($isFullScreen)
                    {{-- Full width --}}
                    <div class="w-full py-9">
                        <x-blocks.media
                            :items="$items"
                            :layout="$layout"
                            :gridColumns="$data['gridColumns'] ?? 3"
                            :gap="$data['gap'] ?? 'md'"
                            :isRtl="$isRtl"
                            :fullWidth="true"
                            :singleImageDisplay="$singleImageDisplay"
                        />
                    </div>
                @else
                    {{-- Default content width --}}
                    <div class="w-full flex justify-center px-4 py-9 lg:px-0">
                        <x-blocks.media
                            :items="$items"
                            :layout="$layout"
                            :gridColumns="$data['gridColumns'] ?? 3"
                            :gap="$data['gap'] ?? 'md'"
                            :isRtl="$isRtl"
                            :fullWidth="false"
                            :singleImageDisplay="$singleImageDisplay"
                        />
                    </div>
                @endif
                @break

            {{-- Legacy Image block --}}
            @case('image')
                <div class="{{ $fullWidth }} w-full px-4 lg:px-0 py-9">
                    <x-blocks.media
                        :items="[[
                            'url' => $data['file']['url'] ?? $data['url'] ?? '',
                            'caption' => $data['caption'] ?? null,
                            'alt_text' => $data['caption'] ?? '',
                            'is_video' => false,
                        ]]"
                        layout="single"
                        :isRtl="$isRtl"
                        :fullWidth="true"
                    />
                </div>
                @break

            {{-- Delimiter --}}
            @case('delimiter')
                <div class="{{ $contentWidth }} w-full">
                    <x-blocks.delimiter />
                </div>
                @break

            {{-- Code --}}
            @case('code')
                <div class="{{ $contentWidth }} w-full px-4 lg:px-0">
                    <x-blocks.code :code="$data['code'] ?? ''" />
                </div>
                @break

            {{-- Table --}}
            @case('table')
                <div class="{{ $contentWidth }} w-full px-4 lg:px-0">
                    <x-blocks.table
                        :content="$data['content'] ?? []"
                        :withHeadings="$data['withHeadings'] ?? false"
                        :isRtl="$isRtl"
                    />
                </div>
                @break

            {{-- Link Tool --}}
            @case('linkTool')
                <div class="{{ $contentWidth }} w-full px-4 lg:px-0">
                    <x-blocks.link
                        :link="$data['link'] ?? '#'"
                        :meta="$data['meta'] ?? []"
                    />
                </div>
                @break

            {{-- Warning/Alert --}}
            @case('warning')
                <div class="{{ $contentWidth }} w-full px-4 lg:px-0">
                    <div class="p-6 bg-tasty-yellow/20 border border-tasty-yellow">
                        @if($data['title'] ?? null)
                            <p class="text-body-md font-semibold text-tasty-blue-black mb-2">{{ $data['title'] }}</p>
                        @endif
                        <p class="text-body-md text-tasty-blue-black/80">{!! $data['message'] ?? '' !!}</p>
                    </div>
                </div>
                @break

            {{-- Raw HTML --}}
            @case('raw')
                <div class="{{ $contentWidth }} w-full px-4 lg:px-0">
                    {!! $data['html'] ?? '' !!}
                </div>
                @break

            {{-- HTML Embed --}}
            @case('htmlEmbed')
                <div class="{{ $contentWidth }} w-full px-4 lg:px-0">
                    {!! $data['html'] ?? '' !!}
                </div>
                @break

            {{-- Embed --}}
            @case('embed')
                <div class="{{ $contentWidth }} w-full px-4 lg:px-0 py-10">
                    <div class="aspect-video">
                        <iframe
                            src="{{ $data['embed'] ?? '' }}"
                            class="w-full h-full"
                            frameborder="0"
                            allowfullscreen
                        ></iframe>
                    </div>
                    @if($data['caption'] ?? null)
                        <p class="text-caption text-tasty-blue-black/40 mt-0 text-left">{!! $data['caption'] !!}</p>
                    @endif
                </div>
                @break

            {{-- Attaches/Files --}}
            @case('attaches')
                <div class="{{ $contentWidth }} w-full px-4 lg:px-0">
                    <a href="{{ $data['file']['url'] ?? '#' }}" target="_blank" rel="noopener" class="flex items-center gap-4 p-4 border border-tasty-blue-black/10 hover:border-tasty-blue-black/30 transition-colors">
                        <div class="w-12 h-12 bg-tasty-light-gray flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-tasty-blue-black/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-body-md font-medium text-tasty-blue-black truncate">{{ $data['title'] ?? $data['file']['name'] ?? 'File' }}</p>
                            @if($data['file']['size'] ?? null)
                                <p class="text-body-sm text-tasty-blue-black/50">{{ number_format($data['file']['size'] / 1024, 1) }} KB</p>
                            @endif
                        </div>
                    </a>
                </div>
                @break

            {{-- Posts Block --}}
            @case('posts')
                <div class="w-full">
                    <x-blocks.posts
                        :posts="$data['posts'] ?? []"
                        :layout="$data['layout'] ?? 'single'"
                        :isRtl="$isRtl"
                    />
                </div>
                @break

            @default
                {{-- Unknown block type - render as debug in dev --}}
                @if(config('app.debug'))
                    <div class="{{ $contentWidth }} w-full px-4 lg:px-0">
                        <div class="p-4 bg-tasty-yellow/20 text-xs">
                            <p class="font-mono text-tasty-blue-black/60">Unknown block: {{ $type }}</p>
                            <pre class="mt-2 text-tasty-blue-black/60 overflow-auto">{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                @endif
        @endswitch
    @endforeach
</div>
