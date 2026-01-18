@props([
    'post',
    'parentId' => null,
    'isReply' => false,
])

@php
    $user = auth()->user();
    $formId = $parentId ? "reply-form-content-{$parentId}" : 'comment-form';
@endphp

<form
    {{ $attributes->merge(['class' => 'comment-form']) }}
    action="{{ route('comments.store') }}"
    method="POST"
    x-data="{
        submitting: false,
        content: '',
        name: '{{ $user?->name ?? '' }}',
        email: '{{ $user?->email ?? '' }}',
        errors: {},
        success: false,
        async submit(e) {
            e.preventDefault();
            this.submitting = true;
            this.errors = {};
            this.success = false;

            try {
                const response = await fetch(this.$el.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        post_id: {{ $post->id }},
                        parent_id: {{ $parentId ?? 'null' }},
                        content: this.content,
                        author_name: this.name,
                        author_email: this.email,
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    this.errors = data.errors || { general: [data.message || 'Something went wrong'] };
                } else {
                    this.success = true;
                    this.content = '';
                    @if($isReply)
                    // Hide reply form after successful submission
                    this.$el.closest('[id^=reply-form-]').classList.add('hidden');
                    @endif
                }
            } catch (error) {
                this.errors = { general: ['An error occurred. Please try again.'] };
            } finally {
                this.submitting = false;
            }
        }
    }"
    @submit="submit"
>
    @csrf

    {{-- Success Message --}}
    <div
        x-show="success"
        x-transition
        class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm"
    >
        <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Thank you! Your comment has been submitted and is awaiting moderation.</span>
        </div>
    </div>

    {{-- Error Message --}}
    <template x-if="errors.general">
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            <template x-for="error in errors.general" :key="error">
                <p x-text="error"></p>
            </template>
        </div>
    </template>

    <div class="bg-white rounded-xl p-6 {{ $isReply ? 'border border-tasty-blue-black/10' : '' }}">
        @if($isReply)
            <p class="text-sm text-tasty-blue-black/60 mb-4">Replying to comment</p>
        @else
            <h3 class="text-lg font-medium text-tasty-blue-black mb-4">Leave a Comment</h3>
        @endif

        {{-- Guest fields (only show if not logged in) --}}
        @guest
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                {{-- Name --}}
                <div>
                    <label for="{{ $formId }}-name" class="block text-sm font-medium text-tasty-blue-black mb-1.5">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="{{ $formId }}-name"
                        x-model="name"
                        required
                        class="w-full px-4 py-3 rounded-lg border border-tasty-blue-black/20 focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black outline-none transition-colors text-tasty-blue-black placeholder:text-tasty-blue-black/40"
                        placeholder="Your name"
                        :class="{ 'border-red-500': errors.author_name }"
                    >
                    <template x-if="errors.author_name">
                        <p class="mt-1 text-sm text-red-600" x-text="errors.author_name[0]"></p>
                    </template>
                </div>

                {{-- Email --}}
                <div>
                    <label for="{{ $formId }}-email" class="block text-sm font-medium text-tasty-blue-black mb-1.5">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="email"
                        id="{{ $formId }}-email"
                        x-model="email"
                        required
                        class="w-full px-4 py-3 rounded-lg border border-tasty-blue-black/20 focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black outline-none transition-colors text-tasty-blue-black placeholder:text-tasty-blue-black/40"
                        placeholder="your@email.com"
                        :class="{ 'border-red-500': errors.author_email }"
                    >
                    <p class="mt-1 text-xs text-tasty-blue-black/50">Your email won't be published</p>
                    <template x-if="errors.author_email">
                        <p class="mt-1 text-sm text-red-600" x-text="errors.author_email[0]"></p>
                    </template>
                </div>
            </div>
        @else
            <div class="flex items-center gap-3 mb-4 p-3 bg-tasty-off-white rounded-lg">
                <img
                    src="https://www.gravatar.com/avatar/{{ md5(strtolower(trim($user->email))) }}?d=mp&s=40"
                    alt="{{ $user->name }}"
                    class="w-8 h-8 rounded-full"
                >
                <span class="text-sm text-tasty-blue-black">
                    Commenting as <strong>{{ $user->name }}</strong>
                </span>
            </div>
        @endguest

        {{-- Comment Content --}}
        <div class="mb-4">
            <label for="{{ $formId }}-content" class="block text-sm font-medium text-tasty-blue-black mb-1.5">
                {{ $isReply ? 'Your Reply' : 'Your Comment' }} <span class="text-red-500">*</span>
            </label>
            <textarea
                id="{{ $formId }}-content"
                x-model="content"
                required
                rows="{{ $isReply ? 3 : 5 }}"
                class="w-full px-4 py-3 rounded-lg border border-tasty-blue-black/20 focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black outline-none transition-colors resize-none text-tasty-blue-black placeholder:text-tasty-blue-black/40"
                placeholder="{{ $isReply ? 'Write your reply...' : 'Share your thoughts...' }}"
                :class="{ 'border-red-500': errors.content }"
            ></textarea>
            <template x-if="errors.content">
                <p class="mt-1 text-sm text-red-600" x-text="errors.content[0]"></p>
            </template>
        </div>

        {{-- Submit Button --}}
        <div class="flex items-center justify-between gap-4">
            @if($isReply)
                <button
                    type="button"
                    class="text-sm text-tasty-blue-black/60 hover:text-tasty-blue-black transition-colors"
                    onclick="this.closest('[id^=reply-form-]').classList.add('hidden')"
                >
                    Cancel
                </button>
            @else
                <p class="text-xs text-tasty-blue-black/50">Comments are moderated before appearing.</p>
            @endif

            <button
                type="submit"
                :disabled="submitting || !content.trim()"
                class="inline-flex items-center gap-2 px-6 py-3 bg-tasty-blue-black text-white text-sm font-medium rounded-full hover:bg-tasty-blue-black/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
                <span x-show="!submitting">{{ $isReply ? 'Post Reply' : 'Post Comment' }}</span>
                <span x-show="submitting" class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Submitting...
                </span>
            </button>
        </div>
    </div>
</form>
