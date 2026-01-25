@props(['post', 'comments', 'totalCount', 'allowComments'])

<section class="bg-off-white py-16" x-data>
    <script>
        function toggleReplyForm(uuid) {
            const form = document.getElementById('reply-form-' + uuid);
            if (form) {
                form.classList.toggle('hidden');
                if (!form.classList.contains('hidden')) {
                    const textarea = form.querySelector('textarea');
                    if (textarea) textarea.focus();
                }
            }
        }
    </script>
    <div class="max-w-[894px] mx-auto px-4 lg:px-0">
        {{-- Section Header --}}
        <div class="flex items-center justify-between mb-10">
            <h2 class="text-h4 text-tasty-blue-black">
                Comments
                @if($totalCount > 0)
                    <span class="text-tasty-blue-black/50">({{ $totalCount }})</span>
                @endif
            </h2>
        </div>

        @if($allowComments)
            {{-- Comment Form --}}
            <x-comments.form :post="$post" class="mb-12" />

            {{-- Comments List --}}
            @if($comments->isNotEmpty())
                <div class="space-y-8">
                    @foreach($comments as $comment)
                        <x-comments.item :comment="$comment" :post="$post" :depth="0" />
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-tasty-blue-black/60">No comments yet. Be the first to share your thoughts!</p>
                </div>
            @endif
        @else
            <div class="text-center py-12 bg-white rounded-xl">
                <p class="text-tasty-blue-black/60">Comments are closed for this article.</p>
            </div>
        @endif
    </div>
</section>
