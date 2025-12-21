@extends('layouts.app')

@section('content')

<main class="flex flex-col flex-1">
        {{-- Hero Section --}}
        <x-sections.hero
            alignment="center"
            action="recent"
            bgColor="yellow"
            buttonText="Read More"
        />

        {{-- Latest Updates Section --}}
        <x-sections.latest-updates
            introImage="{{ Vite::asset('resources/images/latest-updates-transparent.png') }}"
            introImageAlt="Latest Updates"
            titleSmall="Latest"
            titleLarge="Updates"
            description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
            :featuredPostId="1"
            :postIds="[2, 3, 4, 5]"
            buttonText="More Updates"
            loadAction="recent"
        />

        {{-- Featured Person Section --}}
        <x-sections.featured-person
            :postId="6"
            bgColor="yellow"
            topBgColor="red-500"
            overlayColor="yellow"
            tag1="TASTY FEATURE"
            buttonText="Read More"
        />

        {{-- The Spread Section --}}
        <x-sections.spread
            introImage="{{ Vite::asset('resources/images/image-07.png') }}"
            introImageAlt="The Spread"
            titleSmall="The"
            titleLarge="SPREAD"
            description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
            bgColor="yellow"
            mobileLayout="scroll"
            :showDividers="true"
            dividerColor="white"
            :postIds="[1, 2, 3, 4, 5]"
        />

        <!-- Feature Review Section -->
        <section class="w-full max-w-[1880px] mx-auto py-16 px-10 max-md:px-5 max-md:py-8" style="background: linear-gradient(180deg, #FFE762 0%, rgba(255, 231, 98, 0.5) 20%, transparent 40%), white;">
            <div class="container-main flex justify-center">
                <x-cards.review
                    :post="[
                        'image' => 'https://images.unsplash.com/photo-1579027989536-b7b1f875659b?w=1000&h=600&fit=crop',
                        'imageAlt' => 'Nami at Reveli',
                        'tags' => ['ON THE MENU', 'REVIEW'],
                        'title' => 'Nami at Reveli',
                        'subtitle' => 'Sushi and steak by the sea',
                        'description' => 'Nami\'s Japanese-inspired plates land big flavors in a sleek dining room. Come for the sushi; stay because you forgot you were in Malé for a second.',
                        'author' => 'Mohamed Ashraf',
                        'date' => 'JANUARY 8, 2025',
                        'buttonText' => 'Watch',
                        'buttonUrl' => '#',
                    ]"
                    bgColor="tasty-yellow"
                    textColor="blue-black"
                    buttonVariant="white"
                />
            </div>
        </section>

        {{-- On the Menu Section --}}
        <x-sections.on-the-menu
            introImage="{{ Vite::asset('resources/images/image-19.png') }}"
            introImageAlt="On the Menu"
            titleSmall="On the"
            titleLarge="Menu"
            description="Restaurant reviews, chef crushes, and the dishes we can't stop talking about."
            :restaurants="[
                [
                    'image' => 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?w=500&h=500&fit=crop',
                    'name' => 'Bianco',
                    'tagline' => 'Minimalist coffee, quality bites.',
                    'description' => 'A serene all-white interior in Malé, noted for filter & specialty coffees plus French-toast and pasta dishes.',
                    'rating' => 4,
                    'url' => '#',
                ],
                [
                    'image' => 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=500&h=500&fit=crop',
                    'name' => 'Island Patisserie',
                    'tagline' => 'Classic pastries and clean flavours.',
                    'description' => 'Popular for its pastries, tarts, and cakes, this patisserie offers a menu built on quality butter, technique, & clean flavours.',
                    'rating' => 4,
                    'url' => '#',
                ],
                [
                    'image' => 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=400&h=460&fit=crop',
                    'name' => 'Tawa',
                    'tagline' => 'Elevated local bites in Hulhumalé.',
                    'description' => 'In Hulhumalé, Tawa blends café relaxation with inventive small plates—ideal for a pause or a casual dinner.',
                    'rating' => 3,
                    'url' => '#',
                ],
                [
                    'image' => 'https://images.unsplash.com/photo-1559329007-40df8a9345d8?w=400&h=460&fit=crop',
                    'name' => 'Holm Deli',
                    'tagline' => 'Italian sandwiches, beautiful decor.',
                    'description' => 'A new deli known for focaccia sandwiches layered with fresh mozzarella, roasted vegetables, and house-made spreads.',
                    'rating' => 4,
                    'url' => '#',
                ],
                [
                    'image' => 'https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?w=400&h=460&fit=crop',
                    'name' => 'Soho',
                    'tagline' => 'Modern comfort food.',
                    'description' => 'A lively Malé spot known for stacked burgers, hearty bowls, and strong coffee. Built for casual meet-ups.',
                    'rating' => 3,
                    'url' => '#',
                ],
            ]"
        />

        <!-- Location Destination Feature -->
        <x-sections.featured-location
            image="https://images.unsplash.com/photo-1552465011-b4e21bf6e79a?w=1500&h=1000&fit=crop"
            imageAlt="Ceylon Sri Lanka"
            name="CEYLON"
            tagline="Where the air smells like spice, surf, and something softly familiar."
            tag1="TASTY FEATURE"
            tag2="FOOD DESTINATIONS"
            description="Two weeks in Lanka, documenting dishes and cooks who give the island its food identity."
            buttonText="Read More"
            buttonUrl="#"
            bgColor="tasty-yellow"
            textColor="blue-black"
        />

        {{-- Ceylon Spread Cards - Horizontal Scroll --}}
        <x-sections.destination-spread
            :postIds="[6, 7, 8, 9, 10]"
            bgColor="yellow"
            :showDividers="true"
            dividerColor="white"
        />

        {{-- Everyday Cooking Section --}}
        <x-sections.everyday-cooking
            introImage="{{ Vite::asset('resources/images/image-07.png') }}"
            introImageAlt="Everyday Cooking"
            titleSmall="Everyday"
            titleLarge="Cooking"
            description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
            :featuredPostId="11"
            :postIds="[12, 13, 14]"
        />

        <!-- Container for remaining sections (1440px max-width) -->
        <div class="w-full bg-white ">
            <!-- Add to Cart Section -->
            <section class="container-main  py-16 px-10 max-md:px-5 max-md:py-8">
                <div class="flex flex-col gap-16">
                    <!-- Section Header -->
                    <div class="flex flex-col items-center gap-5 text-center">
                        <h2 class="text-h1 text-blue-black uppercase">ADD TO CART</h2>
                        <p class="text-body-large text-blue-black">Ingredients, tools, and staples we actually use.</p>
                    </div>
                    <!-- Product Cards Grid -->
                    <div class="review-grid">
                        <article class="card-vertical bg-off-white rounded-[12px] overflow-hidden p-1 pb-10">
                            <div class="relative aspect-square bg-white rounded-lg flex items-end justify-center p-6 mb-8">
                                <img src="https://images.unsplash.com/photo-1570222094114-d054a817e56b?w=500&h=400&fit=crop&bg=white" alt="Vitamix Blender" class="absolute inset-0 w-full h-full object-contain p-5">
                                <span class="tag relative z-10">PANTRY • APPLIANCE</span>
                            </div>
                            <div class="flex flex-col items-center gap-6 px-8 text-center text-blue-black">
                                <h3 class="text-h4 line-clamp-2">Vitamix Ascent X2</h3>
                                <p class="text-body-medium line-clamp-3">Powerful, precise, and fast — a blender that handles anything you throw at it.</p>
                            </div>
                        </article>
                        <article class="card-vertical bg-off-white rounded-[12px] overflow-hidden p-1 pb-10">
                            <div class="relative aspect-square bg-white rounded-lg flex items-end justify-center p-6 mb-8">
                                <img src="https://images.unsplash.com/photo-1513104890138-7c749659a591?w=500&h=400&fit=crop&bg=white" alt="Gozney Arc XL" class="absolute inset-0 w-full h-full object-contain p-5">
                                <span class="tag relative z-10">PANTRY • APPLIANCE</span>
                            </div>
                            <div class="flex flex-col items-center gap-6 px-8 text-center text-blue-black">
                                <h3 class="text-h4 line-clamp-2">Gozney Arc XL</h3>
                                <p class="text-body-medium line-clamp-3">A compact oven with real flame power — crisp, blistered pizza every time.</p>
                            </div>
                        </article>
                        <article class="card-vertical bg-off-white rounded-[12px] overflow-hidden p-1 pb-10">
                            <div class="relative aspect-square bg-white rounded-lg flex items-end justify-center p-6 mb-8">
                                <img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=500&h=400&fit=crop&bg=white" alt="Ratio Eight Coffee Maker" class="absolute inset-0 w-full h-full object-contain p-5">
                                <span class="tag relative z-10">PANTRY • APPLIANCE</span>
                            </div>
                            <div class="flex flex-col items-center gap-6 px-8 text-center text-blue-black">
                                <h3 class="text-h4 line-clamp-2">Ratio Eight Series 2 Coffee Maker</h3>
                                <p class="text-body-medium line-clamp-3">Beautiful design, foolproof brewing, and café-level coffee at home.</p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>
        </div>

        <!-- Newsletter Section -->
        <div class="w-full bg-gray-100">
            <section class="container-main py-16 px-10 max-md:px-5 max-md:py-8">
                <div class="flex flex-col items-center gap-10">
                    <h2 class="text-h2 text-blue-black text-center max-w-[900px] max-md:text-h4">COME HUNGRY, LEAVE INSPIRED. SIGN UP FOR TASTY UPDATES.</h2>
                    <div class="flex items-center gap-0 bg-white rounded-[100px] p-3 w-full max-w-[500px] max-md:flex-col max-md:bg-transparent max-md:gap-2.5">
                        <input type="email" placeholder="Enter your Email" class="flex-1 bg-transparent px-5 py-0 text-body-large text-blue-black placeholder:text-blue-black/50 outline-none max-md:bg-white max-md:rounded-[100px] max-md:py-3 max-md:text-body-small">
                        <button class="btn btn-yellow max-md:w-full max-md:justify-center">
                            <span>SUBSCRIBE</span>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="#0A0924" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </main>

@endsection
