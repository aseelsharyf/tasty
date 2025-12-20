{{-- Default Template: Clean article layout based on Figma design --}}
{{-- Content width: 894px, Media full width: 1146px --}}

<article class="w-full mx-auto py-16">
    {{-- Content --}}
    <div class="{{ $isRtl ? 'text-right' : '' }}">
        @include('templates.posts.partials.content-blocks', [
            'blocks' => $content['blocks'] ?? [],
            'isRtl' => $isRtl,
        ])
    </div>

    {{-- Tags --}}
    @if(!empty($post['tags']))
        <div class="max-w-[894px] mx-auto px-4 lg:px-0 mt-16 pt-8 border-t border-tasty-blue-black/10">
            <div class="flex flex-wrap items-center gap-2 {{ $isRtl ? 'justify-end' : '' }}">
                @foreach($post['tags'] as $tag)
                    <span class="text-body-sm uppercase px-3 py-1 bg-tasty-light-gray text-tasty-blue-black/60">
                        {{ $tag }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</article>
