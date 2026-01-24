@extends('layouts.app')

@section('content')

<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-32 bg-tasty-off-white">
    <div class="max-w-5xl mx-auto">
        <div class="flex flex-col gap-16 lg:gap-24">

            <!-- Hero Section -->
            <div class="flex flex-col gap-8">
                <div class="text-center max-w-3xl mx-auto">
                    <x-ui.heading
                        level="h1"
                        text="About Tasty"
                        align="center"
                    />
                    <p class="text-body-lg text-tasty-blue-black/60 mt-6">
                        Your guide to food, culture, and the people behind the plate
                    </p>
                </div>

                <!-- Hero Image -->
                <div class="w-full aspect-[2.5/1] rounded-2xl overflow-hidden">
                    <img
                        src="{{ Vite::asset('resources/images/image-01.png') }}"
                        alt="Tasty - Celebrating flavours and stories"
                        class="w-full h-full object-cover"
                    />
                </div>
            </div>

            <!-- Mission Statement -->
            <div class="max-w-3xl mx-auto text-center">
                <p class="text-[20px] lg:text-[24px] leading-relaxed text-tasty-blue-black font-light">
                    Tasty is a modern food and culture platform celebrating the real stories, flavours, and people from the Maldives and the region. We're here for discovery, connection, and real cravings.
                </p>
            </div>

            <!-- Our Story Section -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-16 items-start">
                <div class="lg:col-span-4">
                    <span class="text-xs font-medium uppercase tracking-wider text-tasty-blue-black/40">Our Story</span>
                    <h2 class="text-h4 text-tasty-blue-black mt-2">Why we started Tasty</h2>
                </div>
                <div class="lg:col-span-8 flex flex-col gap-5">
                    <p class="text-body-md text-tasty-blue-black/70 leading-relaxed">
                        Born from a love for flavour and culture, Tasty is a premier digital destination that celebrates Maldivian food culture while thoughtfully spotlighting exceptional culinary destinations across South Asia and the Middle East.
                    </p>
                    <p class="text-body-md text-tasty-blue-black/70 leading-relaxed">
                        We're more than just a content site. Tasty is built as a living archive of people, places, and tastes—a platform that weaves rich editorial storytelling with seamless product discovery and a recipe-led creative hub that transforms inspiration into experience.
                    </p>
                    <p class="text-body-md text-tasty-blue-black/70 leading-relaxed">
                        Our mission is to create long-term value and genuine engagement, offering meaningful opportunities for creators and brand partners to collaborate within a refined ecosystem that blends discovery with direct commerce.
                    </p>
                </div>
            </div>

            <!-- Divider -->
            <div class="w-full h-px bg-tasty-blue-black/10"></div>

            <!-- What We Cover Section -->
            <div class="flex flex-col gap-10">
                <div class="text-center max-w-2xl mx-auto">
                    <span class="text-xs font-medium uppercase tracking-wider text-tasty-blue-black/40">Coverage</span>
                    <h2 class="text-h4 text-tasty-blue-black mt-2">What we cover</h2>
                    <p class="text-body-md text-tasty-blue-black/60 mt-4">
                        From local eateries to culinary traditions, we explore every aspect of food culture.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Places to Eat -->
                    <div class="group p-6 rounded-xl border border-tasty-blue-black/5 bg-white hover:border-tasty-yellow/50 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center mb-4 group-hover:bg-tasty-yellow/20 transition-colors">
                            <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-tasty-blue-black mb-2 font-sans">Places to Eat</h3>
                        <p class="text-sm text-tasty-blue-black/60 leading-relaxed">
                            New openings, trending spots, hidden gems, and trusted reviews.
                        </p>
                    </div>

                    <!-- People Behind the Plate -->
                    <div class="group p-6 rounded-xl border border-tasty-blue-black/5 bg-white hover:border-tasty-yellow/50 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center mb-4 group-hover:bg-tasty-yellow/20 transition-colors">
                            <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-tasty-blue-black mb-2 font-sans">People Behind the Plate</h3>
                        <p class="text-sm text-tasty-blue-black/60 leading-relaxed">
                            Stories of chefs, home cooks, and food creators shaping food culture.
                        </p>
                    </div>

                    <!-- Recipes -->
                    <div class="group p-6 rounded-xl border border-tasty-blue-black/5 bg-white hover:border-tasty-yellow/50 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center mb-4 group-hover:bg-tasty-yellow/20 transition-colors">
                            <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-tasty-blue-black mb-2 font-sans">Recipes & Home Cooking</h3>
                        <p class="text-sm text-tasty-blue-black/60 leading-relaxed">
                            From quick comfort food to a growing archive of family recipes.
                        </p>
                    </div>

                    <!-- Culture -->
                    <div class="group p-6 rounded-xl border border-tasty-blue-black/5 bg-white hover:border-tasty-yellow/50 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center mb-4 group-hover:bg-tasty-yellow/20 transition-colors">
                            <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-tasty-blue-black mb-2 font-sans">Culture & Heritage</h3>
                        <p class="text-sm text-tasty-blue-black/60 leading-relaxed">
                            Island traditions, pantry secrets, and stories from the bandahage.
                        </p>
                    </div>

                    <!-- Travel -->
                    <div class="group p-6 rounded-xl border border-tasty-blue-black/5 bg-white hover:border-tasty-yellow/50 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center mb-4 group-hover:bg-tasty-yellow/20 transition-colors">
                            <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-tasty-blue-black mb-2 font-sans">Travel & Destinations</h3>
                        <p class="text-sm text-tasty-blue-black/60 leading-relaxed">
                            Culinary journeys for those who explore culture through food.
                        </p>
                    </div>

                    <!-- Art -->
                    <div class="group p-6 rounded-xl border border-tasty-blue-black/5 bg-white hover:border-tasty-yellow/50 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 rounded-lg bg-tasty-yellow/10 flex items-center justify-center mb-4 group-hover:bg-tasty-yellow/20 transition-colors">
                            <svg class="w-5 h-5 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-tasty-blue-black mb-2 font-sans">Food Meets Art</h3>
                        <p class="text-sm text-tasty-blue-black/60 leading-relaxed">
                            Where food and creativity intersect, from plating to expression.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="w-full h-px bg-tasty-blue-black/10"></div>

            <!-- Editorial Approach -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-16 items-start">
                <div class="lg:col-span-4">
                    <span class="text-xs font-medium uppercase tracking-wider text-tasty-blue-black/40">Approach</span>
                    <h2 class="text-h4 text-tasty-blue-black mt-2">How we tell stories</h2>
                </div>
                <div class="lg:col-span-8">
                    <div class="flex flex-col gap-6">
                        <p class="text-body-md text-tasty-blue-black/70 leading-relaxed">
                            Tasty is built around curated series and a balanced editorial mix, ensuring you always have familiar touchpoints while discovering something new.
                        </p>

                        <div class="flex flex-wrap gap-2">
                            <span class="px-3 py-1.5 text-xs font-medium bg-tasty-yellow/10 text-tasty-blue-black rounded-full">Local Legends</span>
                            <span class="px-3 py-1.5 text-xs font-medium bg-tasty-yellow/10 text-tasty-blue-black rounded-full">Island Kitchens</span>
                            <span class="px-3 py-1.5 text-xs font-medium bg-tasty-yellow/10 text-tasty-blue-black rounded-full">Tasty Talks</span>
                            <span class="px-3 py-1.5 text-xs font-medium bg-tasty-yellow/10 text-tasty-blue-black rounded-full">Editors' Picks</span>
                            <span class="px-3 py-1.5 text-xs font-medium bg-tasty-yellow/10 text-tasty-blue-black rounded-full">The Tasty List</span>
                        </div>

                        <p class="text-body-md text-tasty-blue-black/70 leading-relaxed">
                            Our editorial mix spans interviews, features, explainers, photo essays, reviews, how-tos, product spotlights, and app reviews. We also welcome recipe and tip submissions from our community.
                        </p>
                    </div>
                </div>
            </div>

            <!-- The Team Section -->
            <div class="rounded-2xl bg-tasty-yellow/5 border border-tasty-yellow/20 p-8 lg:p-12">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-16 items-start">
                    <div class="lg:col-span-4">
                        <span class="text-xs font-medium uppercase tracking-wider text-tasty-blue-black/40">Team</span>
                        <h2 class="text-h4 text-tasty-blue-black mt-2">Who we are</h2>
                    </div>
                    <div class="lg:col-span-8 flex flex-col gap-5">
                        <p class="text-body-md text-tasty-blue-black/70 leading-relaxed">
                            Tasty is developed under the flag of <span class="font-medium text-tasty-blue-black">Greyscale Creative</span>, one of the Maldives' leading marketing firms, trusted by international brands and local market leaders alike.
                        </p>
                        <p class="text-body-md text-tasty-blue-black/70 leading-relaxed">
                            Our team brings proven experience from shaping some of the country's leading media brands. Tasty's editorial desk is led by seasoned, multi award-winning editors, supported by award-winning writers recognized for their craft and storytelling.
                        </p>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-xs font-medium uppercase tracking-wider text-tasty-blue-black/40">Get Involved</span>
                <h2 class="text-h4 text-tasty-blue-black mt-2">Join our community</h2>
                <p class="text-body-md text-tasty-blue-black/60 mt-4 mb-8">
                    Whether you're a passionate home cook, a curious traveler, a food professional, or someone who simply loves a good meal and a great story—Tasty is for you.
                </p>

                <div class="flex flex-wrap justify-center gap-3">
                    <a href="/contact" class="inline-flex items-center gap-2 px-6 py-3 bg-tasty-yellow text-tasty-blue-black text-sm font-medium rounded-full hover:bg-tasty-yellow/90 transition-colors">
                        Contact Us
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                    <a href="/submit-story" class="inline-flex items-center gap-2 px-6 py-3 border border-tasty-blue-black/20 text-tasty-blue-black text-sm font-medium rounded-full hover:border-tasty-blue-black/40 transition-colors">
                        Submit a Story
                    </a>
                    <a href="{{ route('recipes.submit') }}" class="inline-flex items-center gap-2 px-6 py-3 border border-tasty-blue-black/20 text-tasty-blue-black text-sm font-medium rounded-full hover:border-tasty-blue-black/40 transition-colors">
                        Submit a Recipe
                    </a>
                </div>
            </div>

            <!-- Footer tagline -->
            <div class="text-center pt-8 border-t border-tasty-blue-black/5">
                <p class="text-sm text-tasty-blue-black/40">
                    Tasty — Celebrating flavours and stories
                </p>
            </div>

        </div>
    </div>
</div>

@endsection
