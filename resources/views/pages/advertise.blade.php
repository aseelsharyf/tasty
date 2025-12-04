@extends('layouts.app')

@section('content')

<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-32 bg-tasty-off-white">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col gap-10">
            <div class="flex flex-col gap-6">
                <x-ui.heading
                    level="h1"
                    text="Advertise With Us"
                    align="center"
                />
                <p class="text-body-lg text-tasty-blue-black text-center">
                    Connect your brand with the Maldives' most engaged food audience.
                </p>
            </div>

            <div class="w-full aspect-[16/9] rounded-xl overflow-hidden">
                <img
                    src="{{ Vite::asset('resources/images/image-02.png') }}"
                    alt="Tasty audience"
                    class="w-full h-full object-cover"
                />
            </div>

            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="Why Advertise on Tasty?"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        Tasty is the Maldives' leading food and culinary publication. Our readers are passionate food lovers, home cooks, restaurant-goers, and hospitality professionals who trust us for honest reviews, compelling stories, and culinary inspiration.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-xl p-6 flex flex-col gap-3 text-center">
                        <div class="w-16 h-16 bg-tasty-yellow rounded-full flex items-center justify-center mx-auto">
                            <i class="fas fa-users text-tasty-blue-black text-2xl"></i>
                        </div>
                        <span class="text-h3 text-tasty-blue-black">50K+</span>
                        <span class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Monthly Readers</span>
                    </div>

                    <div class="bg-white rounded-xl p-6 flex flex-col gap-3 text-center">
                        <div class="w-16 h-16 bg-tasty-yellow rounded-full flex items-center justify-center mx-auto">
                            <i class="fas fa-heart text-tasty-blue-black text-2xl"></i>
                        </div>
                        <span class="text-h3 text-tasty-blue-black">85%</span>
                        <span class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Engagement Rate</span>
                    </div>

                    <div class="bg-white rounded-xl p-6 flex flex-col gap-3 text-center">
                        <div class="w-16 h-16 bg-tasty-yellow rounded-full flex items-center justify-center mx-auto">
                            <i class="fas fa-bullseye text-tasty-blue-black text-2xl"></i>
                        </div>
                        <span class="text-h3 text-tasty-blue-black">100%</span>
                        <span class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Food-Focused</span>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="Our Audience"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        Our readers include:
                    </p>
                    <ul class="text-body-md text-tasty-blue-black space-y-3">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-tasty-yellow mt-1"></i>
                            <span>Food enthusiasts and home cooks looking for recipes and inspiration</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-tasty-yellow mt-1"></i>
                            <span>Restaurant-goers seeking trusted reviews and recommendations</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-tasty-yellow mt-1"></i>
                            <span>Hospitality professionals and F&B industry insiders</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-tasty-yellow mt-1"></i>
                            <span>Tourists and expats exploring Maldivian cuisine</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-tasty-yellow mt-1"></i>
                            <span>Health-conscious consumers interested in quality ingredients</span>
                        </li>
                    </ul>
                </div>

                <div class="flex flex-col gap-6">
                    <x-ui.heading
                        level="h3"
                        text="Advertising Options"
                    />

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-xl p-6 flex flex-col gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-tasty-yellow rounded-full flex items-center justify-center">
                                    <i class="fas fa-rectangle-ad text-tasty-blue-black"></i>
                                </div>
                                <x-ui.heading level="h4" text="Display Advertising" />
                            </div>
                            <p class="text-body-md text-tasty-blue-black">
                                Premium banner placements across our website, including homepage, article pages, and category sections. Multiple sizes and formats available.
                            </p>
                        </div>

                        <div class="bg-white rounded-xl p-6 flex flex-col gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-tasty-yellow rounded-full flex items-center justify-center">
                                    <i class="fas fa-pen-fancy text-tasty-blue-black"></i>
                                </div>
                                <x-ui.heading level="h4" text="Sponsored Content" />
                            </div>
                            <p class="text-body-md text-tasty-blue-black">
                                Native articles written by our editorial team that highlight your brand, product, or establishment while providing value to our readers.
                            </p>
                        </div>

                        <div class="bg-white rounded-xl p-6 flex flex-col gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-tasty-yellow rounded-full flex items-center justify-center">
                                    <i class="fas fa-envelope text-tasty-blue-black"></i>
                                </div>
                                <x-ui.heading level="h4" text="Newsletter Sponsorship" />
                            </div>
                            <p class="text-body-md text-tasty-blue-black">
                                Reach our engaged subscriber base directly through sponsored placements in our weekly newsletter.
                            </p>
                        </div>

                        <div class="bg-white rounded-xl p-6 flex flex-col gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-tasty-yellow rounded-full flex items-center justify-center">
                                    <i class="fas fa-handshake text-tasty-blue-black"></i>
                                </div>
                                <x-ui.heading level="h4" text="Brand Partnerships" />
                            </div>
                            <p class="text-body-md text-tasty-blue-black">
                                Custom collaborations including events, recipe development, video content, and integrated campaigns tailored to your goals.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="Ideal Partners"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        We work with brands across the food and lifestyle spectrum:
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <span class="px-4 py-2 bg-white rounded-full text-body-sm text-tasty-blue-black">Restaurants & Cafés</span>
                        <span class="px-4 py-2 bg-white rounded-full text-body-sm text-tasty-blue-black">Hotels & Resorts</span>
                        <span class="px-4 py-2 bg-white rounded-full text-body-sm text-tasty-blue-black">Food & Beverage Brands</span>
                        <span class="px-4 py-2 bg-white rounded-full text-body-sm text-tasty-blue-black">Kitchen Equipment</span>
                        <span class="px-4 py-2 bg-white rounded-full text-body-sm text-tasty-blue-black">Grocery & Specialty Foods</span>
                        <span class="px-4 py-2 bg-white rounded-full text-body-sm text-tasty-blue-black">Cooking Classes</span>
                        <span class="px-4 py-2 bg-white rounded-full text-body-sm text-tasty-blue-black">Food Delivery Services</span>
                        <span class="px-4 py-2 bg-white rounded-full text-body-sm text-tasty-blue-black">Event Venues</span>
                    </div>
                </div>
            </div>

            <div class="bg-tasty-yellow rounded-xl p-8 lg:p-10">
                <div class="flex flex-col lg:flex-row gap-8 items-center">
                    <div class="flex-1 flex flex-col gap-4">
                        <x-ui.heading
                            level="h3"
                            text="Let's Talk"
                        />
                        <p class="text-body-md text-tasty-blue-black">
                            Ready to reach the Maldives' most passionate food audience? Get in touch to discuss how we can help your brand connect with our readers.
                        </p>
                        <div class="flex flex-col gap-2">
                            <a href="mailto:ads@tasty.mv" class="text-body-md text-tasty-blue-black underline hover:opacity-70">
                                ads@tasty.mv
                            </a>
                        </div>
                    </div>
                    <div class="flex flex-col gap-4">
                        <x-ui.button
                            url="mailto:ads@tasty.mv"
                            text="Contact Us"
                            icon="arrow-right"
                        />
                        <x-ui.button
                            url="/contact"
                            text="General Inquiries"
                            icon="arrow-right"
                            bgColor="bg-white"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
