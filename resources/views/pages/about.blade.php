@extends('layouts.app')

@section('content')

<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-32 bg-tasty-off-white">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col gap-10">
            <!-- Header -->
            <div class="text-center">
                <x-ui.heading
                    level="h1"
                    text="About Tasty"
                    align="center"
                />
                <p class="text-body-lg text-tasty-blue-black/70 mt-4 max-w-2xl mx-auto">
                    Your guide to food, culture, and the people behind the plate
                </p>
            </div>

            <!-- Hero Image -->
            <div class="w-full aspect-[16/9] rounded-2xl overflow-hidden">
                <img
                    src="{{ Vite::asset('resources/images/image-01.png') }}"
                    alt="Tasty - Celebrating flavours and stories"
                    class="w-full h-full object-cover"
                />
            </div>

            <!-- Intro -->
            <div class="bg-white rounded-2xl p-6 lg:p-8">
                <p class="text-body-lg text-tasty-blue-black text-center">
                    Tasty is a modern food and culture platform celebrating the real stories, flavours, and people from the Maldives and the region. We're here for discovery, connection, and real cravings.
                </p>
            </div>

            <!-- Our Story -->
            <div class="bg-white rounded-2xl p-6 lg:p-8 flex flex-col gap-5">
                <h2 class="text-h4 text-tasty-blue-black">Our Story</h2>
                <div class="flex flex-col gap-4">
                    <p class="text-body-md text-tasty-blue-black/80">
                        Born from a love for flavour and culture, Tasty is a premier digital destination that celebrates Maldivian food culture while thoughtfully spotlighting exceptional culinary destinations across South Asia and the Middle East.
                    </p>
                    <p class="text-body-md text-tasty-blue-black/80">
                        We're more than just a content site. Tasty is built as a living archive of people, places, and tastes—a platform that weaves rich editorial storytelling with seamless product discovery and a recipe-led creative hub that transforms inspiration into experience.
                    </p>
                    <p class="text-body-md text-tasty-blue-black/80">
                        Our mission is to create long-term value and genuine engagement, offering meaningful opportunities for creators and brand partners to collaborate within a refined ecosystem that blends discovery with direct commerce. We're building the definitive home for anyone seeking to explore, learn from, and participate in the Maldives' evolving food culture.
                    </p>
                </div>
            </div>

            <!-- What We Cover -->
            <div class="flex flex-col gap-6">
                <h2 class="text-h4 text-tasty-blue-black text-center">What We Cover</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Places to Eat -->
                    <div class="bg-white rounded-2xl p-6 flex flex-col gap-3">
                        <div class="w-12 h-12 bg-tasty-yellow/20 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-tasty-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-h5 text-tasty-blue-black">Places to Eat</h3>
                        <p class="text-sm text-tasty-blue-black/70">
                            New openings, trending spots, hidden gems, and trusted reviews that help you decide quickly and confidently.
                        </p>
                    </div>

                    <!-- People Behind the Plate -->
                    <div class="bg-white rounded-2xl p-6 flex flex-col gap-3">
                        <div class="w-12 h-12 bg-tasty-yellow/20 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-tasty-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-h5 text-tasty-blue-black">People Behind the Plate</h3>
                        <p class="text-sm text-tasty-blue-black/70">
                            Stories of chefs, home cooks, bakers, mixologists, café owners, and food creators shaping contemporary food culture.
                        </p>
                    </div>

                    <!-- Recipes and Home Cooking -->
                    <div class="bg-white rounded-2xl p-6 flex flex-col gap-3">
                        <div class="w-12 h-12 bg-tasty-yellow/20 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-tasty-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h3 class="text-h5 text-tasty-blue-black">Recipes & Home Cooking</h3>
                        <p class="text-sm text-tasty-blue-black/70">
                            From quick comfort food to bold experiments, alongside a growing archive of family recipes and heirloom dishes worth keeping.
                        </p>
                    </div>

                    <!-- Culture and Heritage -->
                    <div class="bg-white rounded-2xl p-6 flex flex-col gap-3">
                        <div class="w-12 h-12 bg-tasty-yellow/20 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-tasty-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h3 class="text-h5 text-tasty-blue-black">Culture & Heritage</h3>
                        <p class="text-sm text-tasty-blue-black/70">
                            The history of dishes, island traditions, pantry secrets, and everyday food rituals—including stories from the bandahage and the roots of shared flavours.
                        </p>
                    </div>

                    <!-- Travel and Food Destinations -->
                    <div class="bg-white rounded-2xl p-6 flex flex-col gap-3">
                        <div class="w-12 h-12 bg-tasty-yellow/20 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-tasty-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-h5 text-tasty-blue-black">Travel & Food Destinations</h3>
                        <p class="text-sm text-tasty-blue-black/70">
                            Culinary journeys near and far, for travellers who explore culture through meals, markets, and local stories.
                        </p>
                    </div>

                    <!-- Food Meets Art -->
                    <div class="bg-white rounded-2xl p-6 flex flex-col gap-3">
                        <div class="w-12 h-12 bg-tasty-yellow/20 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-tasty-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                        </div>
                        <h3 class="text-h5 text-tasty-blue-black">Food Meets Art</h3>
                        <p class="text-sm text-tasty-blue-black/70">
                            Where food and creativity intersect, from plating and design to the dialogue between tradition and modern expression.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Our Approach -->
            <div class="bg-white rounded-2xl p-6 lg:p-8 flex flex-col gap-5">
                <h2 class="text-h4 text-tasty-blue-black">Our Approach</h2>
                <div class="flex flex-col gap-4">
                    <p class="text-body-md text-tasty-blue-black/80">
                        Tasty is built around curated series and a balanced editorial mix, ensuring you always have familiar touchpoints while discovering something new.
                    </p>
                    <p class="text-body-md text-tasty-blue-black/80">
                        Our recurring series include <strong>Local Legends</strong>, <strong>Island Kitchens</strong>, <strong>Tasty Talks</strong>, <strong>Editors' Picks</strong>, and <strong>The Tasty List</strong>—creating clear entry points and encouraging return visits.
                    </p>
                    <p class="text-body-md text-tasty-blue-black/80">
                        Our editorial mix spans interviews, features, explainers, photo essays, reviews, how-tos, product spotlights, and app reviews. We also welcome recipe and tip submissions from our community, carefully reviewing each contribution before publication.
                    </p>
                </div>
            </div>

            <!-- The Team -->
            <div class="bg-tasty-yellow/20 rounded-2xl p-6 lg:p-8 flex flex-col gap-5">
                <h2 class="text-h4 text-tasty-blue-black">The Team</h2>
                <div class="flex flex-col gap-4">
                    <p class="text-body-md text-tasty-blue-black/80">
                        Tasty is developed under the flag of <strong>Greyscale Creative</strong>, one of the Maldives' leading marketing firms, trusted by international brands and local market leaders alike.
                    </p>
                    <p class="text-body-md text-tasty-blue-black/80">
                        Our team brings proven experience from shaping some of the country's leading media brands, with several members still actively driving their growth and operations. Tasty's editorial desk is led by seasoned, multi award-winning editors, supported by award-winning writers recognized for their craft and storytelling.
                    </p>
                </div>
            </div>

            <!-- Join Our Community -->
            <div class="bg-white rounded-2xl p-6 lg:p-8 flex flex-col gap-5">
                <h2 class="text-h4 text-tasty-blue-black">Join Our Community</h2>
                <p class="text-body-md text-tasty-blue-black/80">
                    Whether you're a passionate home cook, a curious traveler, a food professional, or someone who simply loves a good meal and a great story—Tasty is for you.
                </p>
                <p class="text-body-md text-tasty-blue-black/80">
                    Follow us on our journey as we celebrate flavours and stories, one plate at a time.
                </p>
                <div class="flex flex-wrap gap-3 pt-2">
                    <a href="/contact" class="btn btn-yellow px-6 py-2.5">
                        <span class="flex items-center gap-2">
                            Contact Us
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </span>
                    </a>
                    <a href="/submit-story" class="btn btn-outline px-6 py-2.5">
                        Submit a Story
                    </a>
                    <a href="{{ route('recipes.submit') }}" class="btn btn-outline px-6 py-2.5">
                        Submit a Recipe
                    </a>
                </div>
            </div>

            <!-- Tagline -->
            <div class="text-center">
                <p class="text-body-md text-tasty-blue-black/50 italic">
                    Tasty — Celebrating flavours and stories
                </p>
            </div>
        </div>
    </div>
</div>

@endsection
