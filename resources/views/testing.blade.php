@extends('layouts.app')

@section('content')

<main class="flex flex-col flex-1">
        {{-- Hero Section --}}
        <x-sections.hero
            image="{{Vite::asset('resources/images/image-01.png')}}"
            imageAlt="Delicious food spread"
            category="On culture"
            author="Mohamed Ashraf"
            date="NOVEMBER 12, 2025"
            title="BITE CLUB"
            subtitle="The ghost kitchen feeding Malé after dark"
            buttonText="Read More"
            buttonUrl="#"
        />

        {{-- Latest Updates Section --}}
        <x-sections.latest-updates
            introImage="https://images.unsplash.com/photo-1495195134817-aeb325a55b65?w=500&h=450&fit=crop"
            introImageAlt="Updates illustration"
            titleSmall="Latest"
            titleLarge="Updates"
            description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
            :featuredPost="[
                'image' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=600&h=600&fit=crop',
                'imageAlt' => 'Mexican Fiesta',
                'tags' => ['LATEST', 'FOOD'],
                'title' => 'Mexican Fiesta at Bianco',
                'description' => 'Bianco rolls out a short-run menu featuring quesadillas, nachos, rice bowls and pulled-beef tacos — available for a limited time only.',
                'author' => 'Author Name',
                'date' => 'NOVEMBER 12, 2025',
                'url' => '#',
            ]"
            :posts="[
                [
                    'image' => 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?w=400&h=400&fit=crop',
                    'imageAlt' => 'Jazz Cafe',
                    'tags' => ['LATEST', 'EVENT'],
                    'title' => 'Gig alert in Jazz Cafe! Haveeree Hingun Jazz Chronicles: Vol. 4 - Rumba in C on Sat, Dec 14th.',
                    'author' => 'Author Name',
                    'date' => 'December 20, 2024',
                    'url' => '#',
                ],
                [
                    'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400&h=400&fit=crop',
                    'imageAlt' => 'Sun Siyam Olhuveli',
                    'tags' => ['LATEST', 'EVENT'],
                    'title' => 'Celebrate Diwali and Culinary Excellence at Sun Siyam Olhuveli',
                    'author' => 'Author Name',
                    'date' => 'December 20, 2024',
                    'url' => '#',
                ],
                [
                    'image' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=400&h=400&fit=crop',
                    'imageAlt' => 'Weekend Brunch',
                    'tags' => ['LATEST', 'UPDATE'],
                    'title' => 'New Weekend Brunch Menu at Sala Restaurant',
                    'author' => 'Author Name',
                    'date' => 'December 18, 2024',
                    'url' => '#',
                ],
                [
                    'image' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=400&h=400&fit=crop',
                    'imageAlt' => 'Chef\'s Table',
                    'tags' => ['LATEST', 'EVENT'],
                    'title' => 'Chef\'s Table Experience at Ithaa Undersea Restaurant',
                    'author' => 'Author Name',
                    'date' => 'December 15, 2024',
                    'url' => '#',
                ],
            ]"
            buttonText="More Updates"
        />

        <!-- Featured Profile Section - Aminath Hameed -->
        <section class="w-full max-w-[1880px] mx-auto profile-section-container">
            <!-- Top part with off-white background -->
            <div class="bg-off-white flex flex-col items-center">
                <!-- Profile Photo Container with rounded top - background image -->
                <div class="profile-image-container w-full max-w-[1880px] flex flex-col justify-center items-center gap-10 max-md:gap-[10px] bg-cover bg-center bg-no-repeat relative overflow-hidden" style="background-image: url('https://univrse.sgp1.cdn.digitaloceanspaces.com/uploads/image.png');">
                    <!-- Gradient overlay - fades to yellow at bottom only -->
                    <div class="absolute inset-0 pointer-events-none" style="background: linear-gradient(to bottom, transparent 50%, rgba(255, 231, 98, 0.5) 75%, #FFE762 100%);"></div>
                </div>
            </div>
            <!-- Bottom part with yellow background for content -->
            <div class="bg-yellow pb-32 max-md:pb-16">
                <!-- Profile Content -->
                <div class="container-main flex flex-col items-center gap-10 text-center w-full px-10 max-md:px-5 max-md:gap-5">
                    <h2 class="text-h1 text-blue-black uppercase max-md:text-[60px] max-md:leading-[50px]">AMINATH HAMEED</h2>
                    <p class="text-h2 text-blue-black max-md:text-[40px] max-md:leading-[44px]">Chef and Owner of Maldivian Patisserie.</p>
                    <div class="flex items-center gap-5 text-caption uppercase text-blue-black">
                        <span>TASTY FEATURE</span>
                        <span>•</span>
                        <span>PEOPLE</span>
                    </div>
                    <p class="text-body-large text-blue-black max-w-[650px]">Two weeks in Lanka, documenting dishes and cooks who give the island its food identity.</p>
                    <a href="article.html" class="btn btn-white">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="#0A0924" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Read More</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- The Spread Section -->
        <section class="w-full max-w-[1880px] mx-auto bg-yellow py-16 max-md:py-8">
            <!-- Mobile Title - Shows only on mobile -->
            <div class="hidden max-md:flex flex-col items-center justify-center gap-5 px-5 pb-8">
                <div class="w-full h-[250px]">
                    <img src="https://images.unsplash.com/photo-1495195134817-aeb325a55b65?w=400&h=430&fit=crop" alt="The Spread" class="w-full h-full object-contain" style="mix-blend-mode: darken;">
                </div>
                <div class="flex flex-col items-center text-center text-blue-black">
                    <span class="text-h2">The</span>
                    <h2 class="text-h1 uppercase">SPREAD</h2>
                </div>
                <p class="text-body-large text-blue-black text-center">The flavors, characters, and tiny island obsessions that makes the Maldivian food culture.</p>
            </div>
            <!-- Horizontal Scroll with Title Inline -->
            <div class="scroll-container pb-32 container-main">
                <div class="flex gap-0 pl-10 min-w-max max-md:pl-5">
                    <!-- Title Column - Inline with cards (Desktop only) -->
                    <div class="flex flex-col items-center justify-center gap-5 w-[424px] h-[889px] shrink-0 max-md:hidden">
                        <div class="w-full h-[430px]">
                            <img src="https://images.unsplash.com/photo-1495195134817-aeb325a55b65?w=400&h=430&fit=crop" alt="The Spread" class="w-full h-full object-contain" style="mix-blend-mode: darken;">
                        </div>
                        <div class="flex flex-col items-center text-center text-blue-black">
                            <span class="text-h2">The</span>
                            <h2 class="text-h1 uppercase">SPREAD</h2>
                        </div>
                        <p class="text-body-large text-blue-black text-center">The flavors, characters, and tiny island obsessions that makes the Maldivian food culture.</p>
                    </div>
                    <!-- Divider (Desktop only) -->
                    <div class="w-px bg-white mx-10 shrink-0 max-md:hidden"></div>

                    <x-cards.spread
                        :post="[
                            'image' => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=500&h=400&fit=crop',
                            'imageAlt' => 'Salt, Smoke & Stories',
                            'tags' => ['THE SPREAD', 'ON INGREDIENTS'],
                            'title' => 'Salt, Smoke & Stories',
                            'description' => 'Malé\'s unofficial BBQ scene: backyard grills, midnight marinades, and home pitmasters turning small spaces into smoky flavor labs.',
                            'author' => 'Mohamed Ashraf',
                            'date' => 'JANUARY 8, 2025',
                            'url' => '#',
                        ]"
                        :reversed="false"
                    />
                    <x-cards.spread
                        :post="[
                            'image' => 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=500&h=400&fit=crop',
                            'imageAlt' => 'Job Fish',
                            'tags' => ['THE SPREAD', 'INGREDIENTS'],
                            'title' => 'Job Fish: The Underrated Catch Running the Whole Country',
                            'description' => 'Lean, shiny, firm as hell—job fish does everything except brag about itself. Here\'s why this humble catch is the quiet backbone of Maldivian \"freshness.\"',
                            'author' => 'Aminath Ahmed',
                            'date' => 'FEBRUARY 14, 2025',
                            'url' => '#',
                        ]"
                        :reversed="true"
                    />
                    <x-cards.spread
                        :post="[
                            'image' => 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?w=500&h=400&fit=crop',
                            'imageAlt' => 'Toddy Tenders',
                            'tags' => ['THE SPREAD', 'ON INGREDIENTS'],
                            'title' => 'Toddy Tenders: The Families Keeping Island Abundance Flowing',
                            'description' => 'Behind every good toddy is a family working before sunrise. A slow, sticky look at the people who climb, tap, wait, and keep one of our oldest island traditions alive.',
                            'author' => 'Hanan Saeed',
                            'date' => 'SEPTEMBER 22, 2025',
                            'url' => '#',
                        ]"
                        :reversed="false"
                    />
                    <x-cards.spread
                        :post="[
                            'image' => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=500&h=400&fit=crop',
                            'imageAlt' => 'Addu on a Plate',
                            'tags' => ['THE SPREAD', 'THE SOUTH'],
                            'title' => 'Addu on a Plate: Three Women Cooking a Region Back to Life',
                            'description' => 'Three Adduan home cooks, one shared mission: keep the island\'s grandma-level cooking alive. Expect smoky tuna, coconut memories, and recipes passed down like family secrets.',
                            'author' => 'Aminath Ahmed',
                            'date' => 'FEBRUARY 14, 2025',
                            'url' => '#',
                        ]"
                        :reversed="true"
                    />
                    <x-cards.spread
                        :post="[
                            'image' => 'https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?w=500&h=400&fit=crop',
                            'imageAlt' => 'On Rihaakuru',
                            'tags' => ['THE SPREAD', 'ON INGREDIENTS'],
                            'title' => 'On Rihaakuru',
                            'description' => 'A thick, salty, slow-cooked potion that tastes like home. How one sauce became the backbone of Maldivian comfort food.',
                            'author' => 'Aminath Ahmed',
                            'date' => 'SEPTEMBER 1, 2025',
                            'url' => '#',
                        ]"
                        :reversed="false"
                        :isLast="true"
                    />
                </div>
            </div>
        </section>

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

        <!-- On the Menu Section -->
        <div class="w-full py-16 max-md:py-8">
            <div class="container-main px-5 lg:px-10">
                <!-- Desktop: 2 rows of 3 columns with dividers -->
                <div class="max-md:hidden flex flex-col gap-10">
                <!-- Row 1: Title + Bianco + Island Patisserie -->
                <div class="flex gap-10">
                    <!-- Title Column -->
                    <div class="flex-1 flex flex-col gap-5 items-center justify-center">
                        <div class="w-full max-w-[280px]">
                            <img src="https://images.unsplash.com/photo-1495195134817-aeb325a55b65?w=600&h=430&fit=crop" alt="On the Menu" class="w-full h-auto object-contain" style="mix-blend-mode: darken;">
                        </div>
                        <div class="flex flex-col items-center text-center text-blue-black">
                            <span class="text-h2">On the</span>
                            <h2 class="text-h1 uppercase">Menu</h2>
                        </div>
                        <p class="text-body-large text-blue-black text-center">Restaurant reviews, chef crushes, and the dishes we can't stop talking about.</p>
                    </div>
                    <!-- Divider -->
                    <div class="w-0 self-stretch outline outline-1 outline-offset-[-0.5px] outline-white"></div>
                    <!-- Bianco -->
                    <article class="flex-1 flex flex-col gap-6">
                        <div class="h-[460px] rounded-xl overflow-hidden relative">
                            <img src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?w=500&h=500&fit=crop" alt="Bianco" class="absolute inset-0 w-full h-full object-cover">
                            <div class="absolute inset-0 flex items-end justify-center p-6">
                                <div class="tag tag-white flex items-center gap-2.5">
                                    <span>REVIEW</span>
                                    <span>•</span>
                                    <span>★★★★☆</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-5 text-center text-blue-black">
                            <div class="flex flex-col">
                                <h3 class="text-h3 uppercase">BIANCO</h3>
                                <p class="text-h4">Minimalist coffee, quality bites.</p>
                            </div>
                            <p class="text-body-medium line-clamp-3">A serene all-white interior in Malé, noted for filter & specialty coffees plus French-toast and pasta dishes.</p>
                        </div>
                    </article>
                    <!-- Divider -->
                    <div class="w-0 self-stretch outline outline-1 outline-offset-[-0.5px] outline-white"></div>
                    <!-- Island Patisserie -->
                    <article class="flex-1 flex flex-col gap-6">
                        <div class="h-[460px] rounded-xl overflow-hidden relative">
                            <img src="https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=500&h=500&fit=crop" alt="Island Patisserie" class="absolute inset-0 w-full h-full object-cover">
                            <div class="absolute inset-0 flex items-end justify-center p-6">
                                <div class="tag tag-white flex items-center gap-2.5">
                                    <span>REVIEW</span>
                                    <span>•</span>
                                    <span>★★★★☆</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-5 text-center text-blue-black">
                            <div class="flex flex-col">
                                <h3 class="text-h3 uppercase">ISLAND PATISSERIE</h3>
                                <p class="text-h4">Classic pastries and clean flavours.</p>
                            </div>
                            <p class="text-body-medium line-clamp-3">Popular for its pastries, tarts, and cakes, this patisserie offers a menu built on quality butter, technique, & clean flavours.</p>
                        </div>
                    </article>
                </div>
                <!-- Row 2: Tawa + Holm Deli + Soho -->
                <div class="flex gap-10">
                    <!-- Tawa -->
                    <article class="flex-1 flex flex-col gap-6">
                        <div class="h-[460px] rounded-xl overflow-hidden relative">
                            <img src="https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=400&h=460&fit=crop" alt="Tawa" class="absolute inset-0 w-full h-full object-cover">
                            <div class="absolute inset-0 flex items-end justify-center p-6">
                                <div class="tag tag-white flex items-center gap-2.5">
                                    <span>REVIEW</span>
                                    <span>•</span>
                                    <span>★★★☆☆</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-5 text-center text-blue-black">
                            <div class="flex flex-col">
                                <h3 class="text-h3 uppercase">TAWA</h3>
                                <p class="text-h4">Elevated local bites in Hulhumale'.</p>
                            </div>
                            <p class="text-body-medium line-clamp-3">In Hulhumalé, Tawa blends café relaxation with inventive small plates—ideal for a pause or a casual dinner.</p>
                        </div>
                    </article>
                    <!-- Divider -->
                    <div class="w-0 self-stretch outline outline-1 outline-offset-[-0.5px] outline-white"></div>
                    <!-- Holm Deli -->
                    <article class="flex-1 flex flex-col gap-6">
                        <div class="h-[460px] rounded-xl overflow-hidden relative">
                            <img src="https://images.unsplash.com/photo-1559329007-40df8a9345d8?w=400&h=460&fit=crop" alt="Holm Deli" class="absolute inset-0 w-full h-full object-cover">
                            <div class="absolute inset-0 flex items-end justify-center p-6">
                                <div class="tag tag-white flex items-center gap-2.5">
                                    <span>REVIEW</span>
                                    <span>•</span>
                                    <span>★★★★☆</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-5 text-center text-blue-black">
                            <div class="flex flex-col">
                                <h3 class="text-h3 uppercase">HOLM DELI</h3>
                                <p class="text-h4">Italian sandwiches, beautiful decor.</p>
                            </div>
                            <p class="text-body-medium line-clamp-3">A new deli known for focaccia sandwiches layered with fresh mozzarella, roasted vegetables, and house-made spreads.</p>
                        </div>
                    </article>
                    <!-- Divider -->
                    <div class="w-0 self-stretch outline outline-1 outline-offset-[-0.5px] outline-white"></div>
                    <!-- Soho -->
                    <article class="flex-1 flex flex-col gap-6">
                        <div class="h-[460px] rounded-xl overflow-hidden relative">
                            <img src="https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?w=400&h=460&fit=crop" alt="Soho" class="absolute inset-0 w-full h-full object-cover">
                            <div class="absolute inset-0 flex items-end justify-center p-6">
                                <div class="tag tag-white flex items-center gap-2.5">
                                    <span>REVIEW</span>
                                    <span>•</span>
                                    <span>★★★☆☆</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-5 text-center text-blue-black">
                            <div class="flex flex-col">
                                <h3 class="text-h3 uppercase">SOHO</h3>
                                <p class="text-h4">Modern comfort food.</p>
                            </div>
                            <p class="text-body-medium line-clamp-3">A lively Malé spot known for stacked burgers, hearty bowls, and strong coffee. Built for casual meet-ups.</p>
                        </div>
                    </article>
                    </div>
                </div>
                <!-- Mobile: Title Section (inside container) -->
                <div class="md:hidden flex flex-col gap-5 items-center justify-center">
                    <div class="w-full max-w-[200px]">
                        <img src="https://images.unsplash.com/photo-1495195134817-aeb325a55b65?w=600&h=430&fit=crop" alt="On the Menu" class="w-full h-auto object-contain" style="mix-blend-mode: darken;">
                    </div>
                    <div class="flex flex-col items-center text-center text-blue-black">
                        <span class="text-h2">On the</span>
                        <h2 class="text-h1 uppercase">Menu</h2>
                    </div>
                    <p class="text-body-large text-blue-black text-center">Restaurant reviews, chef crushes, and the dishes we can't stop talking about.</p>
                </div>
            </div>
            <!-- Mobile: Horizontal scroll (outside container for full-width) -->
            <div class="md:hidden scroll-container mt-8">
                <div class="flex gap-5 px-5 min-w-max">
                        <!-- Bianco -->
                        <article class="w-[310px] h-[531px] flex flex-col gap-8 shrink-0">
                            <div class="h-[362px] rounded-xl overflow-hidden relative">
                                <img src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?w=500&h=500&fit=crop" alt="Bianco" class="absolute inset-0 w-full h-full object-cover">
                                <div class="absolute inset-0 flex items-end justify-center p-6">
                                    <div class="tag tag-white flex items-center gap-2.5">
                                        <span>REVIEW</span>
                                        <span>•</span>
                                        <span>★★★★☆</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-5 text-center text-blue-black">
                                <div class="flex flex-col">
                                    <h3 class="text-h3 uppercase">BIANCO</h3>
                                    <p class="text-h4">Minimalist coffee, quality bites.</p>
                                </div>
                                <p class="text-body-medium line-clamp-3">A serene all-white interior in Malé, noted for filter & specialty coffees plus French-toast and pasta dishes.</p>
                            </div>
                        </article>
                        <!-- Island Patisserie -->
                        <article class="w-[310px] h-[531px] flex flex-col gap-8 shrink-0">
                            <div class="h-[362px] rounded-xl overflow-hidden relative">
                                <img src="https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=500&h=500&fit=crop" alt="Island Patisserie" class="absolute inset-0 w-full h-full object-cover">
                                <div class="absolute inset-0 flex items-end justify-center p-6">
                                    <div class="tag tag-white flex items-center gap-2.5">
                                        <span>REVIEW</span>
                                        <span>•</span>
                                        <span>★★★★☆</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-5 text-center text-blue-black">
                                <div class="flex flex-col">
                                    <h3 class="text-h3 uppercase">ISLAND PATISSERIE</h3>
                                    <p class="text-h4">Classic pastries and clean flavours.</p>
                                </div>
                                <p class="text-body-medium line-clamp-3">Popular for its pastries, tarts, and cakes, this patisserie offers a menu built on quality butter, technique, & clean flavours.</p>
                            </div>
                        </article>
                        <!-- Tawa -->
                        <article class="w-[310px] h-[531px] flex flex-col gap-8 shrink-0">
                            <div class="h-[362px] rounded-xl overflow-hidden relative">
                                <img src="https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=400&h=460&fit=crop" alt="Tawa" class="absolute inset-0 w-full h-full object-cover">
                                <div class="absolute inset-0 flex items-end justify-center p-6">
                                    <div class="tag tag-white flex items-center gap-2.5">
                                        <span>REVIEW</span>
                                        <span>•</span>
                                        <span>★★★☆☆</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-5 text-center text-blue-black">
                                <div class="flex flex-col">
                                    <h3 class="text-h3 uppercase">TAWA</h3>
                                    <p class="text-h4">Elevated local bites in Hulhumale'.</p>
                                </div>
                                <p class="text-body-medium line-clamp-3">In Hulhumalé, Tawa blends café relaxation with inventive small plates—ideal for a pause or a casual dinner.</p>
                            </div>
                        </article>
                        <!-- Holm Deli -->
                        <article class="w-[310px] h-[531px] flex flex-col gap-8 shrink-0">
                            <div class="h-[362px] rounded-xl overflow-hidden relative">
                                <img src="https://images.unsplash.com/photo-1559329007-40df8a9345d8?w=400&h=460&fit=crop" alt="Holm Deli" class="absolute inset-0 w-full h-full object-cover">
                                <div class="absolute inset-0 flex items-end justify-center p-6">
                                    <div class="tag tag-white flex items-center gap-2.5">
                                        <span>REVIEW</span>
                                        <span>•</span>
                                        <span>★★★★☆</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-5 text-center text-blue-black">
                                <div class="flex flex-col">
                                    <h3 class="text-h3 uppercase">HOLM DELI</h3>
                                    <p class="text-h4">Italian sandwiches, beautiful decor.</p>
                                </div>
                                <p class="text-body-medium line-clamp-3">A new deli known for focaccia sandwiches layered with fresh mozzarella, roasted vegetables, and house-made spreads.</p>
                            </div>
                        </article>
                        <!-- Soho -->
                        <article class="w-[310px] h-[531px] flex flex-col gap-8 shrink-0">
                            <div class="h-[362px] rounded-xl overflow-hidden relative">
                                <img src="https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?w=400&h=460&fit=crop" alt="Soho" class="absolute inset-0 w-full h-full object-cover">
                                <div class="absolute inset-0 flex items-end justify-center p-6">
                                    <div class="tag tag-white flex items-center gap-2.5">
                                        <span>REVIEW</span>
                                        <span>•</span>
                                        <span>★★★☆☆</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-5 text-center text-blue-black">
                                <div class="flex flex-col">
                                    <h3 class="text-h3 uppercase">SOHO</h3>
                                    <p class="text-h4">Modern comfort food.</p>
                                </div>
                                <p class="text-body-medium line-clamp-3">A lively Malé spot known for stacked burgers, hearty bowls, and strong coffee. Built for casual meet-ups.</p>
                            </div>
                        </article>
                    </div>
                </div>
        </div>

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

        <!-- Ceylon Spread Cards - Horizontal Scroll -->
        <section class="w-full max-w-[1880px] mx-auto bg-yellow py-16 max-md:py-8">
            <div class="scroll-container pb-32 container-main">
                <div class="flex gap-0 pl-10 min-w-max max-md:pl-5">
                    <!-- Card 1 -->
                    <article class="flex flex-col gap-8 w-[480px] max-md:w-[310px] shrink-0">
                        <div class="h-[580px] max-md:h-[350px] relative rounded-[12px] overflow-hidden shrink-0">
                            <img src="https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?w=500&h=400&fit=crop" alt="Ceylon Tea Hills" class="absolute inset-0 w-full h-full object-cover">
                            <div class="absolute inset-0 flex items-end justify-center p-6">
                                <span class="tag tag-white">THE SPREAD • ON INGREDIENTS</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-6 flex-1">
                            <h3 class="text-h3 text-blue-black max-md:text-h4 line-clamp-3">Where Ceylon Begins: Walking the Endless Green of Lanka's Tea Hills</h3>
                            <p class="text-body-medium text-blue-black max-md:text-body-small line-clamp-3">Walking through misty rows of emerald green, meeting the pickers and stories behind Sri Lanka's most iconic leaf.</p>
                            <div class="author-date text-tag uppercase text-blue-black">
                                <span class="underline underline-offset-4">BY Aminath Ahmed</span>
                                <span class="meta-separator">•</span>
                                <span>FEBRUARY 14, 2025</span>
                            </div>
                        </div>
                    </article>
                    <!-- Divider -->
                    <div class="w-px bg-white mx-10 shrink-0"></div>
                    <!-- Card 2 - Alt Layout -->
                    <article class="flex flex-col-reverse gap-8 w-[480px] max-md:w-[310px] shrink-0">
                        <div class="h-[580px] max-md:h-[350px] relative rounded-[12px] overflow-hidden shrink-0">
                            <img src="https://images.unsplash.com/photo-1559496417-e7f25cb247cd?w=500&h=400&fit=crop" alt="Colombo Heritage Café" class="absolute inset-0 w-full h-full object-cover">
                            <div class="absolute inset-0 flex items-end justify-center p-6">
                                <span class="tag tag-white">SECTION • TAG</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-5 flex-1">
                            <h3 class="text-h3 text-blue-black max-md:text-h4 line-clamp-3">A Morning at Colombo's Heritage Café, Where Time Moves Softer</h3>
                            <p class="text-body-medium text-blue-black max-md:text-body-small line-clamp-3">Quiet columns, slow mornings, and a café that pours history with every cup. A taste of Sri Lanka's timeless charm.</p>
                            <div class="author-date text-tag uppercase text-blue-black">
                                <span class="underline underline-offset-4">BY Mohamed Ashraf</span>
                                <span class="meta-separator">•</span>
                                <span>JANUARY 8, 2025</span>
                            </div>
                        </div>
                    </article>
                    <!-- Divider -->
                    <div class="w-px bg-white mx-10 shrink-0"></div>
                    <!-- Card 3 -->
                    <article class="flex flex-col gap-8 w-[480px] max-md:w-[310px] shrink-0">
                        <div class="h-[580px] max-md:h-[350px] relative rounded-[12px] overflow-hidden shrink-0">
                            <img src="https://images.unsplash.com/photo-1601050690597-df0568f70950?w=500&h=400&fit=crop" alt="Street Bites" class="absolute inset-0 w-full h-full object-cover">
                            <div class="absolute inset-0 flex items-end justify-center p-6">
                                <span class="tag tag-white">THE SPREAD • ON INGREDIENTS</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-6 flex-1">
                            <h3 class="text-h3 text-blue-black max-md:text-h4 line-clamp-3">Heat, Hustle & Street Bites</h3>
                            <p class="text-body-medium text-blue-black max-md:text-body-small line-clamp-3">From spicy short eats to crispy fritters made on the fly, Sri Lanka's street vendors turn sidewalks into open-air kitchens. Loud, fast, chaotic — and absolutely delicious.</p>
                            <div class="author-date text-tag uppercase text-blue-black">
                                <span class="underline underline-offset-4">BY Hanan Saeed</span>
                                <span class="meta-separator">•</span>
                                <span>SEPTEMBER 22, 2025</span>
                            </div>
                        </div>
                    </article>
                    <!-- Divider -->
                    <div class="w-px bg-white mx-10 shrink-0"></div>
                    <!-- Card 4 - Alt Layout -->
                    <article class="flex flex-col-reverse gap-8 w-[480px] max-md:w-[310px] shrink-0">
                        <div class="h-[580px] max-md:h-[350px] relative rounded-[12px] overflow-hidden shrink-0">
                            <img src="https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?w=500&h=400&fit=crop" alt="Bananas" class="absolute inset-0 w-full h-full object-cover">
                            <div class="absolute inset-0 flex items-end justify-center p-6">
                                <span class="tag tag-white">SECTION • TAG</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-5 flex-1">
                            <h3 class="text-h3 text-blue-black max-md:text-h4 line-clamp-3">Banana Country: Exploring the Wild, Colorful World of Lanka's Many Varieties</h3>
                            <p class="text-body-medium text-blue-black max-md:text-body-small line-clamp-3">Dozens of shapes, sizes, and sweetness levels — a colorful dive into Sri Lanka's banana farms and their wild variety.</p>
                            <div class="author-date text-tag uppercase text-blue-black">
                                <span class="underline underline-offset-4">BY Aishath Fathimath</span>
                                <span class="meta-separator">•</span>
                                <span>OCTOBER 27, 2025</span>
                            </div>
                        </div>
                    </article>
                    <!-- Divider -->
                    <div class="w-px bg-white mx-10 shrink-0"></div>
                    <!-- Card 5 -->
                    <article class="flex flex-col gap-8 w-[480px] max-md:w-[310px] shrink-0 pr-10 max-md:pr-5">
                        <div class="h-[580px] max-md:h-[350px] relative rounded-[12px] overflow-hidden shrink-0">
                            <img src="https://images.unsplash.com/photo-1567337710282-00832b415979?w=500&h=400&fit=crop" alt="Banana Leaf" class="absolute inset-0 w-full h-full object-cover">
                            <div class="absolute inset-0 flex items-end justify-center p-6">
                                <span class="tag tag-white">THE SPREAD • ON INGREDIENTS</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-6 flex-1">
                            <h3 class="text-h3 text-blue-black max-md:text-h4 line-clamp-3">Cooking on Green Gold: How the Banana Leaf Shapes Sri Lankan Flavor</h3>
                            <p class="text-body-medium text-blue-black max-md:text-body-small line-clamp-3">More than a natural plate, the banana leaf transforms aroma and texture. From wrapping curries to steaming rice, we explore how this simple leaf carries centuries of Lankan food tradition.</p>
                            <div class="author-date text-tag uppercase text-blue-black">
                                <span class="underline underline-offset-4">BY Aminath Ahmed</span>
                                <span class="meta-separator">•</span>
                                <span>SEPTEMBER 1, 2025</span>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- Everyday Cooking Section -->
        <div class="w-full pt-16 pb-16 lg:pb-32" style="background: linear-gradient(to bottom, var(--color-yellow) 0%, white 15%);">
            <div class="container-main px-5 lg:px-10">
                <!-- Row 1: Title + Featured Card -->
                <div class="flex flex-col lg:grid lg:grid-cols-2 gap-10 lg:gap-16 mb-10">
                    <!-- Title Column -->
                <div class="flex items-center justify-center">
                    <div class="w-full max-w-[310px] lg:max-w-[400px] flex flex-col justify-start items-center gap-5 lg:gap-8">
                        <img src="https://images.unsplash.com/photo-1495195134817-aeb325a55b65?w=600&h=476&fit=crop" alt="Everyday Cooking" class="w-full max-h-[156px] lg:max-h-[430px] object-contain" style="mix-blend-mode: darken;">
                        <div class="w-full flex flex-col justify-start items-center gap-6">
                            <div class="w-full flex flex-col justify-start items-center">
                                <span class="text-h2 text-blue-black text-center">Everyday</span>
                                <h2 class="text-h1 text-blue-black text-center uppercase">COOKING</h2>
                            </div>
                            <p class="text-body-large text-blue-black text-center">The flavors, characters, and tiny island obsessions that makes the Maldivian food culture.</p>
                        </div>
                    </div>
                </div>
                <!-- Featured Recipe Card -->
                <div class="w-full">
                    <article class="w-full bg-off-white rounded-xl overflow-hidden flex flex-col items-center gap-8 pb-10">
                        <a href="#" class="block relative w-full flex-1 min-h-[300px] lg:min-h-[500px] p-6 flex flex-col justify-end items-center">
                            <img src="https://images.unsplash.com/photo-1547592180-85f173990554?w=700&h=600&fit=crop" alt="12 Must-Try Recipes" class="absolute inset-0 w-full h-full object-cover rounded-t-[4px]">
                            <div class="relative z-10">
                                <span class="tag">RECIPE • BEST OF</span>
                            </div>
                        </a>
                        <div class="w-full px-6 lg:px-10 flex flex-col items-center gap-6">
                            <h3 class="text-h3 text-blue-black text-center">12 Must-Try Recipes to Make This December</h3>
                            <p class="text-body-medium text-blue-black text-center">The recipe for choosing the perfect resort is in the menu! Ever look up hotels and resorts and see terms like "all inclusive" and "European plan" and not quite know what they mean?</p>
                            <div class="meta-row meta-row-stack text-caption uppercase text-blue-black">
                                <span class="underline underline-offset-4">BY Author Name</span>
                                <span class="meta-separator">•</span>
                                <span>NOVEMBER 12, 2025</span>
                            </div>
                        </div>
                    </article>
                    </div>
                </div>
                <!-- Row 2: Recipe Cards - Scroll on mobile, grid on desktop -->
                <!-- Desktop: Grid -->
                <div class="hidden lg:grid lg:grid-cols-3 gap-10">
                    <!-- Squash Pasta -->
                    <article class="h-[626px] flex flex-col items-center gap-8 pb-10 bg-off-white rounded-xl overflow-hidden">
                        <a href="#" class="block relative w-full flex-1 p-6 flex flex-col justify-end items-center">
                            <img src="https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?w=500&h=400&fit=crop" alt="Squash Pasta" class="absolute inset-0 w-full h-full object-cover rounded-t-[4px]">
                            <div class="relative z-10">
                                <span class="tag">RECIPE • VEGAN</span>
                            </div>
                        </a>
                        <div class="flex flex-col items-center gap-6 px-8 text-center text-blue-black">
                            <h3 class="text-h4 line-clamp-2">How to Cook Squash Pasta</h3>
                            <div class="meta-row text-caption uppercase">
                                <span class="underline underline-offset-4">BY Author Name</span>
                                <span class="meta-separator">•</span>
                                <span>NOVEMBER 12, 2025</span>
                            </div>
                        </div>
                    </article>
                    <!-- Chocolate Chip Cookies -->
                    <article class="h-[626px] flex flex-col items-center gap-8 pb-10 bg-off-white rounded-xl overflow-hidden">
                        <a href="#" class="block relative w-full flex-1 p-6 flex flex-col justify-end items-center">
                            <img src="https://images.unsplash.com/photo-1499636136210-6f4ee915583e?w=500&h=400&fit=crop" alt="Chocolate Chip Cookies" class="absolute inset-0 w-full h-full object-cover rounded-t-[4px]">
                            <div class="relative z-10">
                                <span class="tag">RECIPE • SWEET TOOTH</span>
                            </div>
                        </a>
                        <div class="flex flex-col items-center gap-6 px-8 text-center text-blue-black">
                            <h3 class="text-h4 line-clamp-2">Chocolate Chip Cookies to Die for</h3>
                            <div class="meta-row text-caption uppercase">
                                <span class="underline underline-offset-4">BY Author Name</span>
                                <span class="meta-separator">•</span>
                                <span>NOVEMBER 12, 2025</span>
                            </div>
                        </div>
                    </article>
                    <!-- Fantastic Omelet -->
                    <article class="h-[626px] flex flex-col items-center gap-8 pb-10 bg-off-white rounded-xl overflow-hidden">
                        <a href="#" class="block relative w-full flex-1 p-6 flex flex-col justify-end items-center">
                            <img src="https://images.unsplash.com/photo-1525351484163-7529414344d8?w=500&h=400&fit=crop" alt="Fantastic Omelet" class="absolute inset-0 w-full h-full object-cover rounded-t-[4px]">
                            <div class="relative z-10">
                                <span class="tag">RECIPE • MALDIVIAN</span>
                            </div>
                        </a>
                        <div class="flex flex-col items-center gap-6 px-8 text-center text-blue-black">
                            <h3 class="text-h4 line-clamp-2">How to Make a Fantastic Omelet</h3>
                            <div class="meta-row text-caption uppercase">
                                <span class="underline underline-offset-4">BY Author Name</span>
                                <span class="meta-separator">•</span>
                                <span>NOVEMBER 12, 2025</span>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
            <!-- Mobile: Horizontal scroll (breaks out of container) -->
            <div class="lg:hidden scroll-container">
                <div class="flex gap-5 px-5 min-w-max">
                        <!-- Squash Pasta -->
                        <article class="w-[310px] h-[411px] flex flex-col items-center gap-8 pb-8 bg-off-white rounded-xl overflow-hidden shrink-0">
                            <a href="#" class="block relative w-full flex-1 p-6 flex flex-col justify-end items-center">
                                <img src="https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?w=500&h=400&fit=crop" alt="Squash Pasta" class="absolute inset-0 w-full h-full object-cover rounded-t-[4px]">
                                <div class="relative z-10">
                                    <span class="tag">RECIPE • VEGAN</span>
                                </div>
                            </a>
                            <div class="flex flex-col items-center gap-4 px-5 text-center text-blue-black">
                                <h3 class="text-h4 line-clamp-2">How to Cook Squash Pasta</h3>
                                <div class="meta-row meta-row-stack text-caption uppercase">
                                    <span class="underline underline-offset-4">BY Author Name</span>
                                    <span class="meta-separator">•</span>
                                    <span>NOV 12, 2025</span>
                                </div>
                            </div>
                        </article>
                        <!-- Chocolate Chip Cookies -->
                        <article class="w-[310px] h-[411px] flex flex-col items-center gap-8 pb-8 bg-off-white rounded-xl overflow-hidden shrink-0">
                            <a href="#" class="block relative w-full flex-1 p-6 flex flex-col justify-end items-center">
                                <img src="https://images.unsplash.com/photo-1499636136210-6f4ee915583e?w=500&h=400&fit=crop" alt="Chocolate Chip Cookies" class="absolute inset-0 w-full h-full object-cover rounded-t-[4px]">
                                <div class="relative z-10">
                                    <span class="tag">RECIPE • SWEET TOOTH</span>
                                </div>
                            </a>
                            <div class="flex flex-col items-center gap-4 px-5 text-center text-blue-black">
                                <h3 class="text-h4 line-clamp-2">Chocolate Chip Cookies to Die for</h3>
                                <div class="meta-row meta-row-stack text-caption uppercase">
                                    <span class="underline underline-offset-4">BY Author Name</span>
                                    <span class="meta-separator">•</span>
                                    <span>NOV 12, 2025</span>
                                </div>
                            </div>
                        </article>
                        <!-- Fantastic Omelet -->
                        <article class="w-[310px] h-[411px] flex flex-col items-center gap-8 pb-8 bg-off-white rounded-xl overflow-hidden shrink-0">
                            <a href="#" class="block relative w-full flex-1 p-6 flex flex-col justify-end items-center">
                                <img src="https://images.unsplash.com/photo-1525351484163-7529414344d8?w=500&h=400&fit=crop" alt="Fantastic Omelet" class="absolute inset-0 w-full h-full object-cover rounded-t-[4px]">
                                <div class="relative z-10">
                                    <span class="tag">RECIPE • MALDIVIAN</span>
                                </div>
                            </a>
                            <div class="flex flex-col items-center gap-4 px-5 text-center text-blue-black">
                                <h3 class="text-h4 line-clamp-2">How to Make a Fantastic Omelet</h3>
                                <div class="meta-row meta-row-stack text-caption uppercase">
                                    <span class="underline underline-offset-4">BY Author Name</span>
                                    <span class="meta-separator">•</span>
                                    <span>NOV 12, 2025</span>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
        </div>

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
        <div class="w-full">
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
