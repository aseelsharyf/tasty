@extends('layouts.app')

@section('content')

<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-32 bg-tasty-off-white">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col gap-10">
            <div class="flex flex-col gap-6">
                <x-ui.heading
                    level="h1"
                    text="Submit a Story"
                    align="center"
                />
                <p class="text-body-lg text-tasty-blue-black text-center">
                    Got a food story worth telling? We want to hear it.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div class="flex flex-col gap-8">
                    <div class="flex flex-col gap-4">
                        <x-ui.heading
                            level="h3"
                            text="What We're Looking For"
                        />
                        <p class="text-body-md text-tasty-blue-black">
                            We publish stories about Maldivian food culture in all its forms. If you have a compelling angle on any of these topics, pitch it to us:
                        </p>
                    </div>

                    <div class="flex flex-col gap-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-tasty-yellow rounded-full flex items-center justify-center shrink-0 mt-1">
                                <i class="fas fa-utensils text-tasty-blue-black text-sm"></i>
                            </div>
                            <div>
                                <span class="text-body-md text-tasty-blue-black font-medium">Restaurant Reviews</span>
                                <p class="text-body-sm text-tasty-blue-black mt-1">New openings, hidden gems, or fresh takes on established spots.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-tasty-yellow rounded-full flex items-center justify-center shrink-0 mt-1">
                                <i class="fas fa-user-chef text-tasty-blue-black text-sm"></i>
                            </div>
                            <div>
                                <span class="text-body-md text-tasty-blue-black font-medium">Chef & Cook Profiles</span>
                                <p class="text-body-sm text-tasty-blue-black mt-1">Stories about the people behind the food, from professional chefs to home cooks.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-tasty-yellow rounded-full flex items-center justify-center shrink-0 mt-1">
                                <i class="fas fa-seedling text-tasty-blue-black text-sm"></i>
                            </div>
                            <div>
                                <span class="text-body-md text-tasty-blue-black font-medium">Ingredient Deep-Dives</span>
                                <p class="text-body-sm text-tasty-blue-black mt-1">The history, sourcing, and cultural significance of Maldivian ingredients.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-tasty-yellow rounded-full flex items-center justify-center shrink-0 mt-1">
                                <i class="fas fa-book-open text-tasty-blue-black text-sm"></i>
                            </div>
                            <div>
                                <span class="text-body-md text-tasty-blue-black font-medium">Recipes</span>
                                <p class="text-body-sm text-tasty-blue-black mt-1">Traditional family recipes, modern interpretations, or cooking guides.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-tasty-yellow rounded-full flex items-center justify-center shrink-0 mt-1">
                                <i class="fas fa-plane text-tasty-blue-black text-sm"></i>
                            </div>
                            <div>
                                <span class="text-body-md text-tasty-blue-black font-medium">Food Travel</span>
                                <p class="text-body-sm text-tasty-blue-black mt-1">Culinary experiences from the atolls or food-focused travel stories.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-tasty-yellow rounded-full flex items-center justify-center shrink-0 mt-1">
                                <i class="fas fa-lightbulb text-tasty-blue-black text-sm"></i>
                            </div>
                            <div>
                                <span class="text-body-md text-tasty-blue-black font-medium">Opinion & Essays</span>
                                <p class="text-body-sm text-tasty-blue-black mt-1">Thoughtful takes on food trends, culture, and the future of Maldivian cuisine.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 lg:p-8">
                    <form class="flex flex-col gap-6">
                        <x-ui.heading
                            level="h4"
                            text="Pitch Your Story"
                        />

                        <div class="flex flex-col gap-2">
                            <label for="name" class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Your Name</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg text-body-md focus:outline-none focus:border-tasty-blue-black transition-colors"
                                placeholder="Full name"
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="email" class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg text-body-md focus:outline-none focus:border-tasty-blue-black transition-colors"
                                placeholder="your@email.com"
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="category" class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Story Category</label>
                            <select
                                id="category"
                                name="category"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg text-body-md focus:outline-none focus:border-tasty-blue-black transition-colors bg-white"
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

                        <div class="flex flex-col gap-2">
                            <label for="headline" class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Proposed Headline</label>
                            <input
                                type="text"
                                id="headline"
                                name="headline"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg text-body-md focus:outline-none focus:border-tasty-blue-black transition-colors"
                                placeholder="Your story headline"
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="pitch" class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Your Pitch</label>
                            <textarea
                                id="pitch"
                                name="pitch"
                                rows="5"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg text-body-md focus:outline-none focus:border-tasty-blue-black transition-colors resize-none"
                                placeholder="Tell us about your story idea in 2-3 paragraphs. What's the angle? Why now? Why you?"
                            ></textarea>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="samples" class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Writing Samples (optional)</label>
                            <input
                                type="url"
                                id="samples"
                                name="samples"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg text-body-md focus:outline-none focus:border-tasty-blue-black transition-colors"
                                placeholder="Link to your portfolio or published work"
                            />
                        </div>

                        <x-ui.button
                            url="#"
                            text="Submit Pitch"
                            icon="arrow-right"
                            class="self-start"
                        />
                    </form>
                </div>
            </div>

            <div class="bg-tasty-yellow rounded-xl p-6 lg:p-8">
                <div class="flex flex-col lg:flex-row gap-6 lg:gap-10 items-center">
                    <div class="flex-1">
                        <x-ui.heading
                            level="h4"
                            text="Submission Guidelines"
                        />
                        <ul class="text-body-md text-tasty-blue-black mt-4 space-y-2 list-disc list-inside">
                            <li>Pitches should be 100-200 words maximum</li>
                            <li>We respond to all pitches within 2 weeks</li>
                            <li>Please don't submit completed articles — pitch first</li>
                            <li>Accepted stories are paid upon publication</li>
                        </ul>
                    </div>
                    <div class="text-center lg:text-right">
                        <p class="text-body-sm text-tasty-blue-black">Prefer email?</p>
                        <a href="mailto:pitch@tasty.mv" class="text-body-md text-tasty-blue-black underline hover:opacity-70">
                            pitch@tasty.mv
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
