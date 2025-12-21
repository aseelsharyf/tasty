{{-- Article Body Wrapper --}}
@props(['blocks' => []])

<div class="article-body bg-off-white">
    @foreach($blocks as $block)
        @php
            $type = $block['type'] ?? 'paragraph';
        @endphp

        @switch($type)
            @case('paragraph')
                <x-article.paragraph :text="$block['data']['text'] ?? ''" />
                @break

            @case('header')
                <x-article.heading
                    :heading="$block['data']['text'] ?? ''"
                    text=""
                />
                @break

            @case('heading')
                <x-article.heading
                    :heading="$block['data']['heading'] ?? ''"
                    :text="$block['data']['text'] ?? ''"
                />
                @break

            @case('image')
                <x-article.image
                    :src="$block['data']['file']['url'] ?? $block['data']['url'] ?? ''"
                    :alt="$block['data']['caption'] ?? ''"
                    :caption="$block['data']['caption'] ?? ''"
                    :size="$block['data']['size'] ?? 'full'"
                />
                @break

            @case('image-text')
                <x-article.image-text
                    :imageSrc="$block['data']['image']['url'] ?? ''"
                    :imageAlt="$block['data']['image']['alt'] ?? ''"
                    :imageCaption="$block['data']['image']['caption'] ?? ''"
                    :text="$block['data']['text'] ?? ''"
                    :imagePosition="$block['data']['imagePosition'] ?? 'left'"
                />
                @break

            @case('gallery')
            @case('image-gallery')
                <x-article.image-gallery :images="$block['data']['images'] ?? []" />
                @break

            @case('quote')
            @case('pull-quote')
                <x-article.pull-quote :text="$block['data']['text'] ?? ''" />
                @break

            @case('list')
                <div class="article-content-narrow">
                    <div class="article-text">
                        @if(($block['data']['style'] ?? 'unordered') === 'ordered')
                            <ol class="list-decimal list-inside space-y-2">
                                @foreach($block['data']['items'] ?? [] as $item)
                                    <li>{!! $item !!}</li>
                                @endforeach
                            </ol>
                        @else
                            <ul class="list-disc list-inside space-y-2">
                                @foreach($block['data']['items'] ?? [] as $item)
                                    <li>{!! $item !!}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
                @break

            @default
                {{-- Fallback for unknown block types --}}
                @if(!empty($block['data']['text']))
                    <x-article.paragraph :text="$block['data']['text']" />
                @endif
        @endswitch
    @endforeach
</div>
