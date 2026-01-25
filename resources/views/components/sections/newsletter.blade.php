{{-- Newsletter Section --}}
<section
    class="w-full"
    style="background-color: {{ $bgColor }}"
    x-data="newsletterForm()"
>
    {{-- Desktop Layout --}}
    <div class="hidden lg:flex w-full max-w-[1440px] mx-auto px-10 pt-16 pb-32 flex-col items-center gap-10">
        <h2 class="font-display text-[50px] leading-[75px] tracking-[-0.04em] text-blue-black text-center max-w-[900px]">{{ $title }}</h2>

        {{-- Subscribe Form - Inline on desktop --}}
        <form @submit.prevent="submit" class="w-full max-w-[500px]">
            <div class="flex items-center bg-white rounded-full p-3 backdrop-blur-[10px]">
                <div class="flex-1 px-5">
                    <input
                        type="email"
                        x-model="email"
                        placeholder="{{ $placeholder }}"
                        class="w-full bg-transparent text-[24px] leading-[26px] text-blue-black placeholder:text-blue-black/50 outline-none font-sans"
                        :disabled="loading || success"
                    >
                </div>
                <button
                    type="submit"
                    class="btn btn-yellow"
                    :disabled="loading || success"
                    :class="{ 'opacity-50 cursor-not-allowed': loading || success }"
                >
                    <span x-text="loading ? 'Subscribing...' : (success ? 'Subscribed!' : '{{ $buttonText }}')"></span>
                    <x-ui.icons.arrow-right x-show="!loading && !success" />
                    <x-ui.icons.spinner x-show="loading" x-cloak />
                    <x-ui.icons.check x-show="success" x-cloak />
                </button>
            </div>

            {{-- Message --}}
            <div
                x-show="message"
                x-cloak
                class="mt-3 text-center text-[16px] font-sans"
                :class="success ? 'text-green-600' : 'text-red-600'"
                x-text="message"
            ></div>
        </form>
    </div>

    {{-- Mobile Layout --}}
    <div class="lg:hidden w-full px-5 pt-8 pb-16 flex flex-col items-center gap-5">
        <h2 class="text-[32px] leading-[32px] tracking-[-1.28px] font-display text-blue-black text-center">{{ $title }}</h2>

        {{-- Subscribe Form - Stacked on mobile --}}
        <form @submit.prevent="submit" class="w-full flex flex-col gap-2.5 p-3 rounded-full backdrop-blur-[10px]">
            <input
                type="email"
                x-model="email"
                placeholder="{{ $placeholder }}"
                class="w-full h-12 bg-white rounded-full px-5 py-3 text-[18px] leading-[24px] text-blue-black placeholder:text-blue-black/50 outline-none font-sans"
                :disabled="loading || success"
            >
            <button
                type="submit"
                class="btn btn-yellow w-full justify-center"
                :disabled="loading || success"
                :class="{ 'opacity-50 cursor-not-allowed': loading || success }"
            >
                <span x-text="loading ? 'Subscribing...' : (success ? 'Subscribed!' : '{{ $buttonText }}')"></span>
                <x-ui.icons.arrow-right x-show="!loading && !success" />
                <x-ui.icons.spinner x-show="loading" x-cloak />
                <x-ui.icons.check x-show="success" x-cloak />
            </button>

            {{-- Message --}}
            <div
                x-show="message"
                x-cloak
                class="text-center text-[14px] font-sans"
                :class="success ? 'text-green-600' : 'text-red-600'"
                x-text="message"
            ></div>
        </form>
    </div>
</section>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('newsletterForm', () => ({
        email: '',
        loading: false,
        success: false,
        message: '',

        async submit() {
            if (this.loading || this.success) return;

            if (!this.email || !this.email.includes('@')) {
                this.message = 'Please enter a valid email address.';
                return;
            }

            this.loading = true;
            this.message = '';

            try {
                const response = await fetch('{{ route('api.subscribe') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: this.email })
                });

                const data = await response.json();

                if (data.success) {
                    this.success = true;
                    this.message = data.message;
                } else {
                    this.message = data.message || 'Something went wrong. Please try again.';
                }
            } catch (error) {
                console.error('Subscribe error:', error);
                this.message = 'Something went wrong. Please try again.';
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>
