@extends('layouts.app')

@section('content')

<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-32 bg-tasty-off-white">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col gap-10">
            <div class="flex flex-col gap-6">
                <x-ui.heading
                    level="h1"
                    text="About Tasty"
                    align="center"
                />
                <p class="text-body-lg text-tasty-blue-black text-center">
                    Celebrating the flavors, stories, and people behind Maldivian food culture.
                </p>
            </div>

            <div class="w-full aspect-[16/9] rounded-xl overflow-hidden">
                <img
                    src="{{ Vite::asset('resources/images/image-01.png') }}"
                    alt="Tasty team"
                    class="w-full h-full object-cover"
                />
            </div>

            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="Our Story"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        Tasty was born from a simple belief: that the Maldives has a food story worth telling. Beyond the resort buffets and tourist menus lies a rich culinary heritage shaped by generations of island living, trade winds, and the sea.
                    </p>
                    <p class="text-body-md text-tasty-blue-black">
                        We started as a small team of food lovers, writers, and photographers passionate about documenting the dishes, ingredients, and cooks that define Maldivian cuisine. From the smoky aroma of mas huni at dawn to the sweet tang of fresh toddy, every flavor has a story.
                    </p>
                </div>

                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="What We Do"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        We write about food with depth and curiosity. Our coverage spans restaurant reviews, chef profiles, ingredient deep-dives, home cooking guides, and food travel features. We believe good food writing should make you hungry, curious, and connected to the people behind every plate.
                    </p>
                </div>

                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="Our Team"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        We're a collective of writers, photographers, and editors based across the Maldives. Our contributors include professional chefs, home cooks, food historians, and curious eaters who share our obsession with good food and great stories.
                    </p>
                </div>

                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="Get in Touch"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        Have a story tip, want to collaborate, or just want to say hello? We'd love to hear from you.
                    </p>
                    <div class="flex gap-4">
                        <x-ui.button
                            url="/contact"
                            text="Contact Us"
                            icon="arrow-right"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
