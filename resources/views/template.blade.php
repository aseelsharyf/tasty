@extends('layouts.app')

@section('content')

<main class="flex flex-col flex-1">
        {{-- Hero Section --}}
        <x-sections.hero
              :manual="true"
              kicker="Bite Club"
              title="The ghost kitchen feeding Malé after dark"
              image="{{ Vite::asset('resources/images/image-01.png') }}"
              category="On Culture"
              author="Mohamed Ashraf"
              date="November 12, 2025"
              buttonText="Read More"
              buttonUrl="#"
          />

        {{-- Latest Updates Section (Static) --}}
        <x-sections.latest-updates
            introImage="{{ Vite::asset('resources/images/latest-updates-transparent.png') }}"
            introImageAlt="Latest Updates"
            titleSmall="Latest"
            titleLarge="Updates"
            description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
            :staticFeatured="[
                'image' => Vite::asset('resources/images/image-02.png'),
                'imageAlt' => 'Mexican Fiesta at Bianco',
                'category' => 'Latest',
                'tag' => 'Food',
                'title' => 'Mexican Fiesta at Bianco',
                'description' => 'Bianco rolls out a short-run menu featuring quesadillas, nachos, rice bowls and pulled-beef tacos — available for a limited time only.',
                'author' => 'Author Name',
                'date' => 'November 12, 2025',
                'url' => '#',
            ]"
            :staticPosts="[
                [
                    'image' => Vite::asset('resources/images/image-03.png'),
                    'imageAlt' => 'Gig alert in Jazz Cafe',
                    'category' => 'Latest',
                    'tag' => 'Event',
                    'title' => 'Gig alert in Jazz Cafe! Haveeree Hingun Jazz Chronicles: Vol. 4 - Rumba in C on Sat, Dec 14th.',
                    'author' => 'Author Name',
                    'date' => 'December 20, 2024',
                    'url' => '#',
                ],
                [
                    'image' => Vite::asset('resources/images/image-04.png'),
                    'imageAlt' => 'Celebrate Diwali and Culinary Excellence',
                    'category' => 'Latest',
                    'tag' => 'Seasonal',
                    'title' => 'Celebrate Diwali and Culinary Excellence at Sun Siyam Olhuveli',
                    'author' => 'Author Name',
                    'date' => 'December 20, 2024',
                    'url' => '#',
                ],
                [
                    'image' => Vite::asset('resources/images/image-08.png'),
                    'imageAlt' => 'FHAM 2025 Culinary Challenge',
                    'category' => 'Latest',
                    'tag' => 'People',
                    'title' => 'FHAM 2025 Culinary Challenge began with the Young Chef competition.',
                    'author' => 'Author Name',
                    'date' => 'December 20, 2024',
                    'url' => '#',
                ],
                [
                    'image' => Vite::asset('resources/images/image-06.png'),
                    'imageAlt' => 'Food Carnival 2025',
                    'category' => 'Latest',
                    'tag' => 'Event',
                    'title' => 'Food Carnival 2025 coming on Jan 21st, Hulhumale. A weekend of pop-ups and street flavors!',
                    'author' => 'Author Name',
                    'date' => 'December 20, 2024',
                    'url' => '#',
                ],
            ]"
            :showLoadMore="false"
        />

        {{-- Featured Person Section (Static) --}}
        <x-sections.featured-person
            :staticData="[
                'title' => 'Aminath Hameed',
                'subtitle' => 'Chef and Owner of Maldivian Patisserie.',
                'description' => 'Two weeks in Lanka, documenting dishes and cooks who give the island its food identity.',
                'image' => Vite::asset('resources/images/image-06.png'),
                'url' => '#',
            ]"
            tag1="TASTY FEATURE"
            tag2="PEOPLE"
            buttonText="Read More"
            bgColor="yellow"
        />

        {{-- The Spread Section (Static) --}}
        <x-sections.spread
            introImage="{{ Vite::asset('resources/images/image-07.png') }}"
            introImageAlt="The Spread"
            titleSmall="The"
            titleLarge="SPREAD"
            description="Explore the latest from our kitchen to yours."
            bgColor="yellow"
            :showIntro="true"
            :showDividers="true"
            dividerColor="white"
            mobileLayout="scroll"
            :staticPosts="[
                [
                    'title' => 'The Art of Maldivian Breakfast',
                    'description' => 'A deep dive into the traditional morning meals that fuel island life.',
                    'image' => Vite::asset('resources/images/image-08.png'),
                    'url' => '#',
                    'author' => 'Writer User',
                    'authorUrl' => '#',
                    'date' => 'December 22, 2025',
                    'category' => 'Food',
                ],
                [
                    'title' => 'Street Food Adventures in Male',
                    'description' => 'Discovering hidden gems and local favorites in the capital city.',
                    'image' => Vite::asset('resources/images/image-09.png'),
                    'url' => '#',
                    'author' => 'Writer User',
                    'authorUrl' => '#',
                    'date' => 'December 21, 2025',
                    'category' => 'Travel',
                ],
                [
                    'title' => 'Cooking with Coconut',
                    'description' => 'Essential techniques for using coconut in traditional recipes.',
                    'image' => Vite::asset('resources/images/image-10.png'),
                    'url' => '#',
                    'author' => 'Writer User',
                    'authorUrl' => '#',
                    'date' => 'December 20, 2025',
                    'category' => 'Recipes',
                ],
                [
                    'title' => 'Island Spice Markets',
                    'description' => 'A guide to the vibrant spice trade across the Maldivian atolls.',
                    'image' => Vite::asset('resources/images/image-11.png'),
                    'url' => '#',
                    'author' => 'Writer User',
                    'authorUrl' => '#',
                    'date' => 'December 19, 2025',
                    'category' => 'Culture',
                ],
            ]"
        />

        {{-- Featured Video Section (Static) --}}
        <x-sections.featured-video
            :staticData="[
                'title' => 'Nami at Reveli',
                'subtitle' => 'Sushi and steak by the sea',
                'description' => 'Nami\'s Japanese-inspired plates land big flavors in a sleek dining room. Come for the sushi; stay because you forgot you were in Malé for a second.',
                'image' => Vite::asset('resources/images/image-13.png'),
                'url' => '#',
                'videoUrl' => '#',
                'category' => 'On The Menu',
                'tag' => 'Review',
                'author' => 'Mohamed Ashraf',
                'date' => 'January 8, 2025',
            ]"
            buttonText="Watch"
            overlayColor="#FFE762"
        />

        {{-- Review Section (Static) --}}
        <x-sections.review
            :showIntro="true"
            introImage="{{ Vite::asset('resources/images/on-the-menu.png') }}"
            introImageAlt="On the Menu"
            titleSmall="On the"
            titleLarge="Menu"
            description="Restaurant reviews, chef crushes, and the dishes we can't stop talking about."
            :showDividers="true"
            dividerColor="white"
            mobileLayout="scroll"
            :showLoadMore="false"
            :staticPosts="[
                [
                    'image' => Vite::asset('resources/images/image-14.png'),
                    'title' => 'SUSHI MASTERCLASS',
                    'subtitle' => 'At Nobu Maldives',
                    'description' => 'A journey through traditional Japanese cuisine with a modern twist.',
                    'url' => '#',
                    'category' => 'REVIEW',
                    'rating' => 4,
                ],
                [
                    'image' => Vite::asset('resources/images/image-15.png'),
                    'title' => 'ITALIAN NIGHTS',
                    'subtitle' => 'At Olive Garden',
                    'description' => 'Authentic pasta and wood-fired pizzas under the stars.',
                    'url' => '#',
                    'category' => 'REVIEW',
                    'rating' => 5,
                ],
                [
                    'image' => Vite::asset('resources/images/image-16.png'),
                    'title' => 'SEAFOOD FEAST',
                    'subtitle' => 'At The Ocean Grill',
                    'description' => 'Fresh catches prepared with local spices and herbs.',
                    'url' => '#',
                    'category' => 'REVIEW',
                    'rating' => 3,
                ],
                [
                    'image' => Vite::asset('resources/images/image-17.png'),
                    'title' => 'BREAKFAST BLISS',
                    'subtitle' => 'At Morning Glory',
                    'description' => 'Start your day with the perfect island breakfast spread.',
                    'url' => '#',
                    'category' => 'REVIEW',
                    'rating' => 4,
                ],
                [
                    'image' => Vite::asset('resources/images/image-18.png'),
                    'title' => 'FINE DINING',
                    'subtitle' => 'At The Lighthouse',
                    'description' => 'An elevated culinary experience with breathtaking views.',
                    'url' => '#',
                    'category' => 'REVIEW',
                    'rating' => 5,
                ],
            ]"
        />

        {{-- Featured Location Section (Static) --}}
        <x-sections.featured-location
            :post="[
                'kicker' => 'CEYLON',
                'title' => 'Where the air smells like spice, surf, and something softly familiar.',
                'tag1' => 'TASTY FEATURE',
                'tag2' => 'FOOD DESTINATIONS',
                'description' => 'Two weeks in Lanka, documenting dishes and cooks who give the island its food identity.',
                'image' => Vite::asset('resources/images/image-20.png'),
                'buttonText' => 'Read More',
                'buttonUrl' => '#',
            ]"
            bgColor="yellow"
        />

        {{-- Ceylon Spread Section (Static) --}}
        <x-sections.spread
            :showIntro="false"
            bgColor="yellow"
            :showDividers="true"
            dividerColor="white"
            mobileLayout="scroll"
            :staticPosts="[
                [
                    'title' => 'Where Ceylon Begins: Walking the Endless Green of Lanka\'s Tea Hills',
                    'excerpt' => 'Walking through misty rows of emerald green, meeting the pickers and stories behind Sri Lanka\'s most iconic leaf.',
                    'image' => Vite::asset('resources/images/image-21.png'),
                    'url' => '#',
                    'author' => 'Aminath Ahmed',
                    'date' => 'February 14, 2025',
                    'tag1' => 'THE SPREAD',
                    'tag2' => 'ON INGREDIENTS',
                ],
                [
                    'title' => 'A Morning at Colombo\'s Heritage Café, Where Time Moves Softer',
                    'excerpt' => 'Quiet columns, slow mornings, and a café that pours history with every cup. A taste of Sri Lanka\'s timeless charm.',
                    'image' => Vite::asset('resources/images/image-22.png'),
                    'url' => '#',
                    'author' => 'Mohamed Ashraf',
                    'date' => 'January 8, 2025',
                    'tag1' => 'SECTION',
                    'tag2' => 'TAG',
                ],
                [
                    'title' => 'Heat, Hustle & Street Gold',
                    'excerpt' => 'From spicy short eats to crispy on-the-fly, Sri Lanka\'s street vendors turn open-air kitchens. Loud, fast, and absolutely delicious.',
                    'image' => Vite::asset('resources/images/image-23.png'),
                    'url' => '#',
                    'author' => 'Hanan Saeed',
                    'date' => 'September 22, 2025',
                    'tag1' => 'THE SPREAD',
                    'tag2' => 'ON INGREDIENTS',
                ],
            ]"
            :count="0"
        />


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
