@extends('layouts.app')

@section('content')
<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-20 bg-tasty-off-white min-h-screen">
    <div class="max-w-2xl mx-auto">
        <div class="flex flex-col gap-8">
            <!-- Header -->
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-tasty-yellow rounded-full mb-6">
                    <svg class="w-8 h-8 text-tasty-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <x-ui.heading
                    level="h1"
                    text="Recipe Submitted!"
                    align="center"
                />
                <p class="text-body-md text-tasty-blue-black/70 mt-4 max-w-md mx-auto">
                    Thank you for sharing your recipe. Our editorial team will review it shortly.
                </p>
            </div>

            <!-- What happens next -->
            <div class="bg-white rounded-2xl p-6">
                <h2 class="text-h5 text-tasty-blue-black mb-5">What happens next?</h2>

                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full bg-tasty-yellow/20 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-tasty-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-tasty-blue-black">Review within 3-5 business days</p>
                            <p class="text-sm text-gray-500 mt-0.5">Our team carefully reviews each submission</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full bg-tasty-yellow/20 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-tasty-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-tasty-blue-black">Email notification</p>
                            <p class="text-sm text-gray-500 mt-0.5">You'll hear from us once a decision is made</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full bg-tasty-yellow/20 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-tasty-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-tasty-blue-black">Published with credit</p>
                            <p class="text-sm text-gray-500 mt-0.5">Approved recipes go live with your name</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <x-ui.button :href="route('recipes.submit')" icon="plus">
                    Submit Another
                </x-ui.button>
                <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium text-tasty-blue-black hover:text-tasty-blue-black/70 transition-colors">
                    Back to Home
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
