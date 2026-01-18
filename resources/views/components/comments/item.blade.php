@props([
    'comment',
    'post',
    'depth' => 0,
])

@php
    $maxDepth = 1; // Allow only 2 levels (0 = root, 1 = reply)
    $canReply = $depth < $maxDepth && ($post->allow_comments ?? true);
    $isReply = $depth > 0;
@endphp

<article
    id="comment-{{ $comment->uuid }}"
    {{ $attributes->class([
        'group',
        'pl-8 border-l-2 border-tasty-blue-black/10' => $isReply,
    ]) }}
>
    <div class="bg-white rounded-xl p-6">
        {{-- Comment Header --}}
        <div class="flex items-start gap-4 mb-4">
            {{-- Avatar --}}
            <img
                src="{{ $comment->gravatar_url }}"
                alt="{{ $comment->author_display_name }}"
                class="w-10 h-10 rounded-full bg-tasty-light-gray flex-shrink-0"
            >

            {{-- Author Info --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-medium text-tasty-blue-black">
                        {{ $comment->author_display_name }}
                    </span>
                    @if($comment->is_registered_user)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] uppercase tracking-wide bg-tasty-yellow text-tasty-blue-black">
                            Member
                        </span>
                    @endif
                    @if($comment->is_edited)
                        <span class="text-xs text-tasty-blue-black/40">(edited)</span>
                    @endif
                </div>
                <time
                    datetime="{{ $comment->created_at->toIso8601String() }}"
                    class="text-sm text-tasty-blue-black/50"
                >
                    {{ $comment->created_at->diffForHumans() }}
                </time>
            </div>
        </div>

        {{-- Comment Content --}}
        <div class="prose prose-sm max-w-none text-tasty-blue-black/90 mb-4">
            {!! nl2br(e($comment->content)) !!}
        </div>

        {{-- Actions --}}
        @if($canReply)
            <div class="flex items-center gap-4">
                <button
                    type="button"
                    class="text-sm text-tasty-blue-black/60 hover:text-tasty-blue-black transition-colors inline-flex items-center gap-1.5"
                    onclick="toggleReplyForm('{{ $comment->uuid }}')"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 17 4 12 9 7"></polyline>
                        <path d="M20 18v-2a4 4 0 0 0-4-4H4"></path>
                    </svg>
                    Reply
                </button>
            </div>

            {{-- Reply Form (hidden by default) --}}
            <div id="reply-form-{{ $comment->uuid }}" class="hidden mt-4">
                <x-comments.form :post="$post" :parent-id="$comment->id" :is-reply="true" />
            </div>
        @endif
    </div>

    {{-- Nested Replies (up to 2 levels) --}}
    @if($comment->replies->isNotEmpty() && $depth < $maxDepth)
        <div class="mt-4 space-y-4">
            @foreach($comment->replies as $reply)
                <x-comments.item :comment="$reply" :post="$post" :depth="$depth + 1" />
            @endforeach
        </div>
    @endif
</article>
