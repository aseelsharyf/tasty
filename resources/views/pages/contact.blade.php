@extends('layouts.app')

@section('content')

<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-32 bg-tasty-off-white">
    <div class="max-w-5xl mx-auto">
        <div class="flex flex-col gap-16 lg:gap-24">

            <!-- Hero Section -->
            <div class="text-center max-w-3xl mx-auto">
                <x-ui.heading
                    level="h1"
                    text="Contact Us"
                    align="center"
                />
                <p class="text-body-lg text-tasty-blue-black/60 mt-6">
                    We'd love to hear from you. Reach out with questions, tips, or just to say hello.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16">
                <!-- Contact Information -->
                <div class="lg:col-span-5 flex flex-col gap-8">
                    <!-- Get in Touch -->
                    <div class="flex flex-col gap-6">
                        <div>
                            <span class="text-xs font-medium uppercase tracking-wider text-tasty-blue-black/40">Contact</span>
                            <h2 class="text-h4 text-tasty-blue-black mt-2">Get in touch</h2>
                        </div>
                        <p class="text-body-md text-tasty-blue-black/70 leading-relaxed">
                            Whether you have a story tip, feedback on our coverage, or a collaboration idea, we're all ears.
                        </p>

                        <div class="flex flex-col gap-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-wider">General Inquiries</span>
                                    <a href="mailto:hello@tasty.mv" class="text-sm text-tasty-blue-black hover:text-tasty-blue-black/70 transition-colors">
                                        hello@tasty.mv
                                    </a>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-wider">Editorial</span>
                                    <a href="mailto:editor@tasty.mv" class="text-sm text-tasty-blue-black hover:text-tasty-blue-black/70 transition-colors">
                                        editor@tasty.mv
                                    </a>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-wider">Advertising</span>
                                    <a href="mailto:ads@tasty.mv" class="text-sm text-tasty-blue-black hover:text-tasty-blue-black/70 transition-colors">
                                        ads@tasty.mv
                                    </a>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-wider">Location</span>
                                    <p class="text-sm text-tasty-blue-black">
                                        Malé, Maldives
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="w-full h-px bg-tasty-blue-black/10"></div>

                    <!-- Social Links -->
                    <div class="flex flex-col gap-4">
                        <div>
                            <span class="text-xs font-medium uppercase tracking-wider text-tasty-blue-black/40">Social</span>
                            <h3 class="text-h4 text-tasty-blue-black mt-2">Follow us</h3>
                        </div>
                        <div class="flex gap-3">
                            <a href="#" class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center text-tasty-blue-black/70 hover:bg-tasty-yellow/20 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center text-tasty-blue-black/70 hover:bg-tasty-yellow/20 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z"/>
                                </svg>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center text-tasty-blue-black/70 hover:bg-tasty-yellow/20 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center text-tasty-blue-black/70 hover:bg-tasty-yellow/20 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="lg:col-span-7">
                    <div class="rounded-2xl border border-tasty-blue-black/5 bg-white p-6 lg:p-8">
                        <div class="mb-6">
                            <span class="text-xs font-medium uppercase tracking-wider text-tasty-blue-black/40">Message</span>
                            <h2 class="text-h4 text-tasty-blue-black mt-2">Send us a message</h2>
                        </div>

                        <form class="flex flex-col gap-5" x-data="{ submitting: false, submitted: false }" @submit.prevent="submitting = true; setTimeout(() => { submitted = true; submitting = false; }, 1500)">
                            <div class="flex flex-col gap-1.5">
                                <label for="name" class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-wider">Name *</label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    class="w-full px-4 py-3 border border-tasty-blue-black/10 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black/30 focus:ring-1 focus:ring-tasty-blue-black/10 transition-all"
                                    placeholder="Your name"
                                    required
                                />
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="email" class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-wider">Email *</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="w-full px-4 py-3 border border-tasty-blue-black/10 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black/30 focus:ring-1 focus:ring-tasty-blue-black/10 transition-all"
                                    placeholder="your@email.com"
                                    required
                                />
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="subject" class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-wider">Subject *</label>
                                <select
                                    id="subject"
                                    name="subject"
                                    class="w-full px-4 py-3 border border-tasty-blue-black/10 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black/30 focus:ring-1 focus:ring-tasty-blue-black/10 transition-all bg-white"
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
                                <label for="message" class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-wider">Message *</label>
                                <textarea
                                    id="message"
                                    name="message"
                                    rows="5"
                                    class="w-full px-4 py-3 border border-tasty-blue-black/10 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black/30 focus:ring-1 focus:ring-tasty-blue-black/10 transition-all resize-none"
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
                                class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-tasty-yellow text-tasty-blue-black text-sm font-medium rounded-full hover:bg-tasty-yellow/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed self-start"
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
</div>

@endsection
