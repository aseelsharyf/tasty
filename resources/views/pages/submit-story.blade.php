@extends('layouts.app')

@section('content')

<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-32 bg-tasty-off-white">
    <div class="max-w-5xl mx-auto">
        <div class="flex flex-col gap-16 lg:gap-24">

            <!-- Hero Section -->
            <div class="text-center max-w-3xl mx-auto">
                <x-ui.heading
                    level="h1"
                    text="Submit a Story"
                    align="center"
                />
                <p class="text-body-lg text-tasty-blue-black/60 mt-6">
                    Got a food story worth telling? We want to hear it.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16">
                <!-- What We're Looking For -->
                <div class="lg:col-span-5 flex flex-col gap-8">
                    <div class="flex flex-col gap-6">
                        <div>
                            <span class="text-xs font-medium uppercase tracking-wider text-tasty-blue-black/40">Topics</span>
                            <h2 class="text-h4 text-tasty-blue-black mt-2">What we're looking for</h2>
                        </div>
                        <p class="text-body-md text-tasty-blue-black/70 leading-relaxed">
                            We publish stories about Maldivian food culture in all its forms. If you have a compelling angle on any of these topics, pitch it to us:
                        </p>

                        <div class="flex flex-col gap-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-sm font-semibold text-tasty-blue-black font-sans">Restaurant Reviews</span>
                                    <p class="text-xs text-tasty-blue-black/60">New openings, hidden gems, or fresh takes on established spots.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-sm font-semibold text-tasty-blue-black font-sans">Chef & Cook Profiles</span>
                                    <p class="text-xs text-tasty-blue-black/60">Stories about the people behind the food.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-sm font-semibold text-tasty-blue-black font-sans">Ingredient Deep-Dives</span>
                                    <p class="text-xs text-tasty-blue-black/60">History and cultural significance of Maldivian ingredients.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-sm font-semibold text-tasty-blue-black font-sans">Recipes</span>
                                    <p class="text-xs text-tasty-blue-black/60">Traditional family recipes or modern interpretations.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-sm font-semibold text-tasty-blue-black font-sans">Food Travel</span>
                                    <p class="text-xs text-tasty-blue-black/60">Culinary experiences from the atolls or travel stories.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-sm font-semibold text-tasty-blue-black font-sans">Opinion & Essays</span>
                                    <p class="text-xs text-tasty-blue-black/60">Thoughtful takes on food trends and culture.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="w-full h-px bg-tasty-blue-black/10"></div>

                    <!-- Submission Guidelines -->
                    <div class="rounded-2xl bg-white border border-tasty-blue-black/5 p-6">
                        <div class="mb-4">
                            <span class="text-xs font-medium uppercase tracking-wider text-tasty-blue-black/40">Guidelines</span>
                            <h3 class="text-h4 text-tasty-blue-black mt-2">Submission guidelines</h3>
                        </div>
                        <ul class="flex flex-col gap-3">
                            <li class="flex items-start gap-3">
                                <div class="w-5 h-5 rounded-full bg-tasty-yellow/20 flex items-center justify-center mt-0.5 shrink-0">
                                    <svg class="w-3 h-3 text-tasty-blue-black" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-sm text-tasty-blue-black/70">Pitches should be 100-200 words maximum</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-5 h-5 rounded-full bg-tasty-yellow/20 flex items-center justify-center mt-0.5 shrink-0">
                                    <svg class="w-3 h-3 text-tasty-blue-black" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-sm text-tasty-blue-black/70">We respond to all pitches within 2 weeks</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-5 h-5 rounded-full bg-tasty-yellow/20 flex items-center justify-center mt-0.5 shrink-0">
                                    <svg class="w-3 h-3 text-tasty-blue-black" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-sm text-tasty-blue-black/70">Please don't submit completed articles — pitch first</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-5 h-5 rounded-full bg-tasty-yellow/20 flex items-center justify-center mt-0.5 shrink-0">
                                    <svg class="w-3 h-3 text-tasty-blue-black" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-sm text-tasty-blue-black/70">Accepted stories are paid upon publication</span>
                            </li>
                        </ul>
                        <div class="mt-5 pt-5 border-t border-tasty-blue-black/10">
                            <p class="text-xs text-tasty-blue-black/40 uppercase tracking-wider mb-1">Prefer email?</p>
                            <a href="mailto:pitch@tasty.mv" class="text-sm text-tasty-blue-black font-medium hover:text-tasty-blue-black/70 transition-colors">
                                pitch@tasty.mv
                            </a>
                        </div>
                    </div>

                    <!-- Submit Recipe CTA -->
                    <div class="rounded-xl border border-tasty-blue-black/5 bg-white p-5 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-tasty-blue-black font-sans">Have a recipe to share?</h3>
                            <p class="text-xs text-tasty-blue-black/60 mt-0.5">Submit your recipe directly with our easy form.</p>
                        </div>
                        <a href="{{ route('recipes.submit') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-tasty-yellow text-tasty-blue-black text-sm font-medium rounded-full hover:bg-tasty-yellow/90 transition-colors">
                            Submit
                        </a>
                    </div>
                </div>

                <!-- Pitch Form -->
                <div class="lg:col-span-7">
                    <div class="rounded-2xl border border-tasty-blue-black/5 bg-white p-6 lg:p-8 h-fit">
                        <div class="mb-6">
                            <span class="text-xs font-medium uppercase tracking-wider text-tasty-blue-black/40">Pitch</span>
                            <h2 class="text-h4 text-tasty-blue-black mt-2">Pitch your story</h2>
                        </div>

                        <form class="flex flex-col gap-5" x-data="{ submitting: false, submitted: false }" @submit.prevent="submitting = true; setTimeout(() => { submitted = true; submitting = false; }, 1500)">
                            <div class="flex flex-col gap-1.5">
                                <label for="name" class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-wider">Your Name *</label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    class="w-full px-4 py-3 border border-tasty-blue-black/10 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black/30 focus:ring-1 focus:ring-tasty-blue-black/10 transition-all"
                                    placeholder="Full name"
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
                                <label for="category" class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-wider">Story Category *</label>
                                <select
                                    id="category"
                                    name="category"
                                    class="w-full px-4 py-3 border border-tasty-blue-black/10 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black/30 focus:ring-1 focus:ring-tasty-blue-black/10 transition-all bg-white"
                                    required
                                >
                                    <option value="">Select a category</option>
                                    <option value="review">Restaurant Review</option>
                                    <option value="profile">Chef/Cook Profile</option>
                                    <option value="ingredient">Ingredient Story</option>
                                    <option value="recipe">Recipe</option>
                                    <option value="travel">Food Travel</option>
                                    <option value="opinion">Opinion/Essay</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="headline" class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-wider">Proposed Headline *</label>
                                <input
                                    type="text"
                                    id="headline"
                                    name="headline"
                                    class="w-full px-4 py-3 border border-tasty-blue-black/10 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black/30 focus:ring-1 focus:ring-tasty-blue-black/10 transition-all"
                                    placeholder="Your story headline"
                                    required
                                />
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="pitch" class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-wider">Your Pitch *</label>
                                <textarea
                                    id="pitch"
                                    name="pitch"
                                    rows="5"
                                    class="w-full px-4 py-3 border border-tasty-blue-black/10 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black/30 focus:ring-1 focus:ring-tasty-blue-black/10 transition-all resize-none"
                                    placeholder="Tell us about your story idea in 2-3 paragraphs. What's the angle? Why now? Why you?"
                                    required
                                ></textarea>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="samples" class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-wider">Writing Samples (optional)</label>
                                <input
                                    type="url"
                                    id="samples"
                                    name="samples"
                                    class="w-full px-4 py-3 border border-tasty-blue-black/10 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black/30 focus:ring-1 focus:ring-tasty-blue-black/10 transition-all"
                                    placeholder="Link to your portfolio or published work"
                                />
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
                                    <p class="text-sm text-green-700">Thank you! We'll review your pitch and get back to you within 2 weeks.</p>
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="submitting || submitted"
                                class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-tasty-yellow text-tasty-blue-black text-sm font-medium rounded-full hover:bg-tasty-yellow/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed self-start"
                            >
                                <template x-if="!submitting && !submitted">
                                    <span class="flex items-center gap-2">
                                        Submit Pitch
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
                                        Submitting...
                                    </span>
                                </template>
                                <template x-if="submitted && !submitting">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Submitted!
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
