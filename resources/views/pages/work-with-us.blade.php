@extends('layouts.app')

@section('content')

<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-32 bg-tasty-off-white">
    <div class="max-w-5xl mx-auto">
        <div class="flex flex-col gap-16 lg:gap-24">

            <!-- Hero Section -->
            <div class="text-center max-w-3xl mx-auto">
                <x-ui.heading
                    level="h1"
                    text="Work With Us"
                    align="center"
                />
                <p class="text-body-lg text-tasty-blue-black/60 mt-6">
                    Join our team of food enthusiasts, writers, and creators.
                </p>
            </div>

            <!-- Who We're Looking For -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-16 items-start">
                <div class="lg:col-span-4">
                    <span class="text-xs font-medium uppercase tracking-wider text-tasty-blue-black/40">Opportunities</span>
                    <h2 class="text-h4 text-tasty-blue-black mt-2">Who we're looking for</h2>
                </div>
                <div class="lg:col-span-8">
                    <p class="text-body-md text-tasty-blue-black/70 leading-relaxed">
                        We're always on the lookout for talented individuals who share our passion for Maldivian food culture. Whether you're a seasoned food writer, an aspiring photographer, or someone with unique culinary expertise, we'd love to hear from you.
                    </p>
                </div>
            </div>

            <!-- Roles Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="group p-6 rounded-xl border border-tasty-blue-black/5 bg-white hover:border-tasty-yellow/50 hover:shadow-sm transition-all">
                    <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center mb-4 group-hover:bg-tasty-yellow/20 transition-colors">
                        <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-tasty-blue-black mb-2 font-sans">Writers</h3>
                    <p class="text-sm text-tasty-blue-black/60 leading-relaxed">
                        We need storytellers who can capture the essence of a dish, the personality of a chef, or the history behind an ingredient. Experience in food writing preferred but not required.
                    </p>
                </div>

                <div class="group p-6 rounded-xl border border-tasty-blue-black/5 bg-white hover:border-tasty-yellow/50 hover:shadow-sm transition-all">
                    <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center mb-4 group-hover:bg-tasty-yellow/20 transition-colors">
                        <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-tasty-blue-black mb-2 font-sans">Photographers</h3>
                    <p class="text-sm text-tasty-blue-black/60 leading-relaxed">
                        Food photography that makes people hungry. We're looking for photographers who can capture the beauty, texture, and story of Maldivian cuisine.
                    </p>
                </div>

                <div class="group p-6 rounded-xl border border-tasty-blue-black/5 bg-white hover:border-tasty-yellow/50 hover:shadow-sm transition-all">
                    <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center mb-4 group-hover:bg-tasty-yellow/20 transition-colors">
                        <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-tasty-blue-black mb-2 font-sans">Video Creators</h3>
                    <p class="text-sm text-tasty-blue-black/60 leading-relaxed">
                        From recipe videos to chef profiles, we're expanding our video content. Experience with food videography or documentary-style content is a plus.
                    </p>
                </div>

                <div class="group p-6 rounded-xl border border-tasty-blue-black/5 bg-white hover:border-tasty-yellow/50 hover:shadow-sm transition-all">
                    <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center mb-4 group-hover:bg-tasty-yellow/20 transition-colors">
                        <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-tasty-blue-black mb-2 font-sans">Regional Contributors</h3>
                    <p class="text-sm text-tasty-blue-black/60 leading-relaxed">
                        Based outside Malé? We need voices from across the atolls to cover local food scenes, traditional recipes, and regional specialties.
                    </p>
                </div>
            </div>

            <!-- Divider -->
            <div class="w-full h-px bg-tasty-blue-black/10"></div>

            <!-- What We Offer -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-16 items-start">
                <div class="lg:col-span-4">
                    <span class="text-xs font-medium uppercase tracking-wider text-tasty-blue-black/40">Benefits</span>
                    <h2 class="text-h4 text-tasty-blue-black mt-2">What we offer</h2>
                </div>
                <div class="lg:col-span-8">
                    <ul class="flex flex-col gap-4">
                        <li class="flex items-start gap-3">
                            <div class="w-5 h-5 rounded-full bg-tasty-yellow/20 flex items-center justify-center mt-0.5 shrink-0">
                                <svg class="w-3 h-3 text-tasty-blue-black" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="text-body-md text-tasty-blue-black/70">Competitive compensation for accepted work</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="w-5 h-5 rounded-full bg-tasty-yellow/20 flex items-center justify-center mt-0.5 shrink-0">
                                <svg class="w-3 h-3 text-tasty-blue-black" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="text-body-md text-tasty-blue-black/70">Byline credit and portfolio building opportunities</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="w-5 h-5 rounded-full bg-tasty-yellow/20 flex items-center justify-center mt-0.5 shrink-0">
                                <svg class="w-3 h-3 text-tasty-blue-black" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="text-body-md text-tasty-blue-black/70">Editorial support and feedback to help you grow</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="w-5 h-5 rounded-full bg-tasty-yellow/20 flex items-center justify-center mt-0.5 shrink-0">
                                <svg class="w-3 h-3 text-tasty-blue-black" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="text-body-md text-tasty-blue-black/70">Access to food events, restaurant openings, and industry connections</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="w-5 h-5 rounded-full bg-tasty-yellow/20 flex items-center justify-center mt-0.5 shrink-0">
                                <svg class="w-3 h-3 text-tasty-blue-black" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="text-body-md text-tasty-blue-black/70">Flexible, remote-friendly working arrangements</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Divider -->
            <div class="w-full h-px bg-tasty-blue-black/10"></div>

            <!-- How to Apply -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-16 items-start">
                <div class="lg:col-span-4">
                    <span class="text-xs font-medium uppercase tracking-wider text-tasty-blue-black/40">Apply</span>
                    <h2 class="text-h4 text-tasty-blue-black mt-2">How to apply</h2>
                </div>
                <div class="lg:col-span-8 flex flex-col gap-6">
                    <p class="text-body-md text-tasty-blue-black/70 leading-relaxed">
                        Send us an email with the following:
                    </p>
                    <ul class="flex flex-col gap-3">
                        <li class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full bg-tasty-blue-black/5 flex items-center justify-center text-xs font-medium text-tasty-blue-black/60">1</span>
                            <span class="text-body-md text-tasty-blue-black/70">A brief introduction about yourself</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full bg-tasty-blue-black/5 flex items-center justify-center text-xs font-medium text-tasty-blue-black/60">2</span>
                            <span class="text-body-md text-tasty-blue-black/70">The role you're interested in</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full bg-tasty-blue-black/5 flex items-center justify-center text-xs font-medium text-tasty-blue-black/60">3</span>
                            <span class="text-body-md text-tasty-blue-black/70">2-3 relevant work samples (links or attachments)</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full bg-tasty-blue-black/5 flex items-center justify-center text-xs font-medium text-tasty-blue-black/60">4</span>
                            <span class="text-body-md text-tasty-blue-black/70">Why you want to work with Tasty</span>
                        </li>
                    </ul>

                    <div class="pt-2">
                        <a href="mailto:careers@tasty.mv" class="inline-flex items-center gap-2 px-6 py-3 bg-tasty-yellow text-tasty-blue-black text-sm font-medium rounded-full hover:bg-tasty-yellow/90 transition-colors">
                            Apply Now
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
