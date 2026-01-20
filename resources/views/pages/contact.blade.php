@extends('layouts.app')

@section('content')

<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-32 bg-tasty-off-white">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col gap-10">
            <!-- Header -->
            <div class="text-center">
                <x-ui.heading
                    level="h1"
                    text="Contact Us"
                    align="center"
                />
                <p class="text-body-md text-tasty-blue-black/70 mt-4">
                    We'd love to hear from you. Reach out with questions, tips, or just to say hello.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <!-- Contact Information -->
                <div class="flex flex-col gap-8">
                    <div class="bg-white rounded-2xl p-6 flex flex-col gap-5">
                        <h2 class="text-h5 text-tasty-blue-black">Get in Touch</h2>
                        <p class="text-body-md text-tasty-blue-black/70">
                            Whether you have a story tip, feedback on our coverage, or a collaboration idea, we're all ears.
                        </p>

                        <div class="flex flex-col gap-5">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-tasty-yellow/20 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-tasty-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">General Inquiries</span>
                                    <a href="mailto:hello@tasty.mv" class="text-sm text-tasty-blue-black hover:text-tasty-yellow transition-colors">
                                        hello@tasty.mv
                                    </a>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-tasty-yellow/20 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-tasty-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Editorial</span>
                                    <a href="mailto:editor@tasty.mv" class="text-sm text-tasty-blue-black hover:text-tasty-yellow transition-colors">
                                        editor@tasty.mv
                                    </a>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-tasty-yellow/20 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-tasty-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Advertising</span>
                                    <a href="mailto:ads@tasty.mv" class="text-sm text-tasty-blue-black hover:text-tasty-yellow transition-colors">
                                        ads@tasty.mv
                                    </a>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-tasty-yellow/20 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-tasty-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Location</span>
                                    <p class="text-sm text-tasty-blue-black">
                                        Malé, Maldives
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div class="bg-white rounded-2xl p-6 flex flex-col gap-4">
                        <h3 class="text-h5 text-tasty-blue-black">Follow Us</h3>
                        <div class="flex gap-3">
                            <a href="#" class="w-10 h-10 bg-tasty-yellow/20 rounded-full flex items-center justify-center text-tasty-blue-black hover:bg-tasty-yellow transition-colors">
                                <i class="fab fa-instagram text-lg"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-tasty-yellow/20 rounded-full flex items-center justify-center text-tasty-blue-black hover:bg-tasty-yellow transition-colors">
                                <i class="fab fa-tiktok text-lg"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-tasty-yellow/20 rounded-full flex items-center justify-center text-tasty-blue-black hover:bg-tasty-yellow transition-colors">
                                <i class="fab fa-youtube text-lg"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-tasty-yellow/20 rounded-full flex items-center justify-center text-tasty-blue-black hover:bg-tasty-yellow transition-colors">
                                <i class="fab fa-x-twitter text-lg"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="bg-white rounded-2xl p-6 flex flex-col gap-5">
                    <h2 class="text-h5 text-tasty-blue-black">Send us a Message</h2>

                    <form class="flex flex-col gap-5" x-data="{ submitting: false, submitted: false }" @submit.prevent="submitting = true; setTimeout(() => { submitted = true; submitting = false; }, 1500)">
                        <div class="flex flex-col gap-1.5">
                            <label for="name" class="text-xs font-medium text-gray-500">Name *</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all"
                                placeholder="Your name"
                                required
                            />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="email" class="text-xs font-medium text-gray-500">Email *</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all"
                                placeholder="your@email.com"
                                required
                            />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="subject" class="text-xs font-medium text-gray-500">Subject *</label>
                            <select
                                id="subject"
                                name="subject"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all bg-white"
                                required
                            >
                                <option value="">Select a topic</option>
                                <option value="general">General Inquiry</option>
                                <option value="story">Story Tip</option>
                                <option value="feedback">Feedback</option>
                                <option value="collaboration">Collaboration</option>
                                <option value="advertising">Advertising</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="message" class="text-xs font-medium text-gray-500">Message *</label>
                            <textarea
                                id="message"
                                name="message"
                                rows="5"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all resize-none"
                                placeholder="Your message..."
                                required
                            ></textarea>
                        </div>

                        <!-- Success Message -->
                        <div
                            x-show="submitted"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 transform -translate-y-2"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            class="bg-green-50 border border-green-200 rounded-xl p-4"
                        >
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm text-green-700">Thank you! Your message has been sent successfully.</p>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="submitting || submitted"
                            class="btn btn-yellow self-start px-6 py-2.5 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <template x-if="!submitting && !submitted">
                                <span class="flex items-center gap-2">
                                    Send Message
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </span>
                            </template>
                            <template x-if="submitting">
                                <span class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Sending...
                                </span>
                            </template>
                            <template x-if="submitted && !submitting">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Sent!
                                </span>
                            </template>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
