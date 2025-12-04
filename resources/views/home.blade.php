@extends('layouts.app')

@section('content')


{{-- Full Customization --}}
<x-post.hero
    image="{{Vite::asset('resources/images/image-01.png')}}"
    imageAlt="Delicious fried chicken in paper cups"
    category="On Culture"
    categoryUrl="#"
    author="Mohamed Ashraf"
    authorUrl="#"
    date="November 12, 2025"
    title="BITE CLUB"
    subtitle="The ghost kitchen feeding"
    subtitleItalic="Malé after dark"
    buttonText="Read More"
    buttonUrl="#"
    alignment="center"
    bgColor="bg-tasty-yellow"
/>


<div class="w-full px-5 md:px-10 pt-16 pb-32 bg-tasty-off-white">
    {{-- Mobile: Vertical Stack --}}
    <div class="md:hidden flex flex-col gap-10">
        <x-post.section-intro
            image="{{ Vite::asset('resources/images/latest-updates-transparent.png') }}"
            imageAlt="Latest Updates"
            title="Latest"
            titleLarge="Updates"
            description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
        />

        <x-post.card-news
            image="{{Vite::asset('resources/images/image-02.png')}}"
            imageAlt="Mexican food dishes on a table"
            category="Food & Drink"
            categoryUrl="/category/food-drink"
            tag="Restaurant Review"
            tagUrl="/tag/restaurant-review"
            author="Mohamed Ashraf"
            authorUrl="/author/mohamed-ashraf"
            date="November 12, 2025"
            title="Mexican Fiesta at Bianco"
            description="Bianco rolls out a short-run menu featuring quesadillas, nachos, rice bowls and pulled-beef tacos — available for a limited time only."
            articleUrl="/article/mexican-fiesta-at-bianco"
        />

        <x-post.card-news-small
            image="{{ Vite::asset('resources/images/image-03.png') }}"
            imageAlt="Jazz Cafe event"
            category="Latest"
            categoryUrl="#"
            tag="Event"
            tagUrl="#"
            author="Author Name"
            authorUrl="#"
            date="December 20, 2024"
            title="Gig alert in Jazz Cafe! Haveeree Hingun Jazz Chronicles: Vol. 4 - Rumba in C on Sat, Dec 14th."
            description="Live jazz chronicles with a fusion twist happening at Jazz Cafe."
            articleUrl="#"
        />

        <x-post.card-news-small
            image="{{ Vite::asset('resources/images/image-04.png') }}"
            imageAlt="Sun Siyam Olhuveli resort"
            category="Latest"
            categoryUrl="#"
            tag="Seasonal"
            tagUrl="#"
            author="Author Name"
            authorUrl="#"
            date="December 20, 2024"
            title="Celebrate Diwali and Culinary Excellence at Sun Siyam Olhuveli"
            description="Festive celebrations with culinary experiences at Olhuveli."
            articleUrl="#"
        />

        <x-post.card-news-small
            image="{{ Vite::asset('resources/images/image-04.png') }}"
            imageAlt="Young chef with medals"
            category="Latest"
            categoryUrl="#"
            tag="People"
            tagUrl="#"
            author="Author Name"
            authorUrl="#"
            date="December 20, 2024"
            title="FHAM 2025 Culinary Challenge began with the Young Chef competition."
            description="A showcase of rising culinary talent kicking off FHAM 2025."
            articleUrl="#"
        />

        <x-post.card-news-small
            image="{{ Vite::asset('resources/images/image-05.png') }}"
            imageAlt="Food Carnival 2025 poster"
            category="Latest"
            categoryUrl="#"
            tag="Event"
            tagUrl="#"
            author="Author Name"
            authorUrl="#"
            date="December 20, 2024"
            title="Food Carnival 2025 coming on Jan 21st, Hulhumale. A weekend of pop-ups and street flavors!"
            description="A vibrant food carnival with pop-ups and diverse street foods."
            articleUrl="#"
        />

        <div class="flex justify-center mt-10">
            <x-ui.button
                url="#"
                text="More Updates"
                icon="plus"
                :iconRotate="true"
            />
        </div>
    </div>

    {{-- Desktop: 3 Rows x 2 Columns Grid Layout --}}
    <div class="hidden md:block">
        <div class="grid grid-cols-[1fr_1fr] gap-5">
            {{-- Row 1, Col 1 --}}
            <div class="flex items-center">
                <x-post.section-intro
                    image="{{ Vite::asset('resources/images/latest-updates-transparent.png') }}"
                    imageAlt="Latest Updates"
                    title="Latest"
                    titleLarge="Updates"
                    description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
                    maxWidth="max-w-[310px] md:max-w-none"
                />
            </div>

            {{-- Row 1, Col 2 --}}
            <div>
                <x-post.card-news
                    image="{{Vite::asset('resources/images/image-02.png')}}"
                    imageAlt="Mexican food dishes on a table"
                    category="Food & Drink"
                    categoryUrl="/category/food-drink"
                    tag="Restaurant Review"
                    tagUrl="/tag/restaurant-review"
                    author="Mohamed Ashraf"
                    authorUrl="/author/mohamed-ashraf"
                    date="November 12, 2025"
                    title="Mexican Fiesta at Bianco"
                    description="Bianco rolls out a short-run menu featuring quesadillas, nachos, rice bowls and pulled-beef tacos — available for a limited time only."
                    articleUrl="/article/mexican-fiesta-at-bianco"
                />
            </div>

            {{-- Row 2, Col 1 --}}
            <div>
                <x-post.card-news-small
                    image="{{ Vite::asset('resources/images/image-03.png') }}"
                    imageAlt="Jazz Cafe event"
                    category="Latest"
                    categoryUrl="#"
                    tag="Event"
                    tagUrl="#"
                    author="Author Name"
                    authorUrl="#"
                    date="December 20, 2024"
                    title="Gig alert in Jazz Cafe! Haveeree Hingun Jazz Chronicles: Vol. 4 - Rumba in C on Sat, Dec 14th."
                    description="Live jazz chronicles with a fusion twist happening at Jazz Cafe."
                    articleUrl="#"
                />
            </div>

            {{-- Row 2, Col 2 --}}
            <div>
                <x-post.card-news-small
                    image="{{ Vite::asset('resources/images/image-04.png') }}"
                    imageAlt="Sun Siyam Olhuveli resort"
                    category="Latest"
                    categoryUrl="#"
                    tag="Seasonal"
                    tagUrl="#"
                    author="Author Name"
                    authorUrl="#"
                    date="December 20, 2024"
                    title="Celebrate Diwali and Culinary Excellence at Sun Siyam Olhuveli"
                    description="Festive celebrations with culinary experiences at Olhuveli."
                    articleUrl="#"
                />
            </div>

            {{-- Row 3, Col 1 --}}
            <div>
                <x-post.card-news-small
                    image="{{ Vite::asset('resources/images/image-04.png') }}"
                    imageAlt="Young chef with medals"
                    category="Latest"
                    categoryUrl="#"
                    tag="People"
                    tagUrl="#"
                    author="Author Name"
                    authorUrl="#"
                    date="December 20, 2024"
                    title="FHAM 2025 Culinary Challenge began with the Young Chef competition."
                    description="A showcase of rising culinary talent kicking off FHAM 2025."
                    articleUrl="#"
                />
            </div>

            {{-- Row 3, Col 2 --}}
            <div>
                <x-post.card-news-small
                    image="{{ Vite::asset('resources/images/image-05.png') }}"
                    imageAlt="Food Carnival 2025 poster"
                    category="Latest"
                    categoryUrl="#"
                    tag="Event"
                    tagUrl="#"
                    author="Author Name"
                    authorUrl="#"
                    date="December 20, 2024"
                    title="Food Carnival 2025 coming on Jan 21st, Hulhumale. A weekend of pop-ups and street flavors!"
                    description="A vibrant food carnival with pop-ups and diverse street foods."
                    articleUrl="#"
                />
            </div>
        </div>

        <div class="flex justify-center mt-10">
            <x-ui.button
                url="#"
                text="More Updates"
                icon="plus"
                :iconRotate="true"
            />
        </div>
    </div>

</div>

{{-- Featured Profile Section --}}
<x-featured-person
    image="{{ Vite::asset('resources/images/image-06.png') }}"
    imageAlt="Aminath Hameed - Chef"
    name="Aminath Hameed"
    title="Chef and Owner of Maldivian Patisserie."
    tag1="tasty feature"
    tag2="people"
    description="Two weeks in Lanka, documenting dishes and cooks who give the island its food identity."
    buttonText="Read More"
    buttonUrl="#"
    bgColor="bg-tasty-yellow"
/>

{{-- The Spread Section --}}
<div class="w-full px-5 md:px-10 pt-16 pb-32 bg-tasty-yellow">

    {{--
       Layout Wrapper:
       Mobile: flex-col (Stack Intro on top, Cards below)
       Desktop: flex-row + overflow-x-auto (Intro sits next to cards, whole row scrolls)
    --}}
    <div class="flex flex-col gap-10 md:flex-row md:gap-10 md:overflow-x-auto md:scrollbar-hide md:scroll-smooth">

        {{--
            1. Intro Section
            Mobile: w-full (Stacked)
            Desktop: Fixed width 384px (First item in scroll)
        --}}
        <div class="w-full md:w-[384px] md:shrink-0 md:flex md:items-center">
            <x-post.section-intro
                image="{{ Vite::asset('resources/images/image-07.png') }}"
                imageAlt="The Spread"
                title="The"
                titleLarge="spread"
                description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
            />
        </div>

        {{-- Separator (Desktop only) --}}
        <div class="hidden md:block w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white shrink-0"></div>

        {{--
            2. Scrollable Cards Container
            Mobile: overflow-x-auto (This container scrolls independently)
            Desktop: overflow-visible (Let the parent handle the scrolling)
        --}}
        <div class="flex gap-10 overflow-x-auto scrollbar-hide scroll-smooth md:overflow-visible">

            {{-- Card Wrapper: Enforce widths here to ensure scrolling triggers --}}
            <div class="w-[310px] md:w-[480px] shrink-0">
                <x-post.card-spread
                    image="{{ Vite::asset('resources/images/image-08.png') }}"
                    imageAlt="BBQ grilling scene"
                    category="The Spread"
                    categoryUrl="#"
                    tag="On Ingredients"
                    tagUrl="#"
                    author="Mohamed Ashraf"
                    authorUrl="#"
                    date="January 8, 2025"
                    title="Salt, Smoke & Stories"
                    description="Malé's unofficial BBQ scene: backyard grills, midnight marinades, and home pitmasters turning small spaces into smoky flavor labs."
                    articleUrl="#"
                    imagePosition="top"
                />
            </div>

            {{-- Separator --}}
            <div class="hidden md:block w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white shrink-0"></div>

            <div class="w-[310px] md:w-[480px] shrink-0">
                <x-post.card-spread
                    image="{{ Vite::asset('resources/images/image-09.png') }}"
                    imageAlt="Fisherman carrying basket of fish"
                    category="The Spread"
                    categoryUrl="#"
                    tag="Ingredients"
                    tagUrl="#"
                    author="Aminath Ahmed"
                    authorUrl="#"
                    date="February 14, 2025"
                    title="Job Fish: The Underrated Catch Running the Whole Country"
                    description="Lean, shiny, firm as hell—job fish does everything except brag about itself. Here's why this humble catch is the quiet backbone of Maldivian 'freshness.'"
                    articleUrl="#"
                    imagePosition="bottom"
                />
            </div>

            {{-- Separator --}}
            <div class="hidden md:block w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white shrink-0"></div>

            <div class="w-[310px] md:w-[480px] shrink-0">
                <x-post.card-spread
                    image="{{ Vite::asset('resources/images/image-10.png') }}"
                    imageAlt="Traditional toddy tapping scene"
                    category="The Spread"
                    categoryUrl="#"
                    tag="Ingredients"
                    tagUrl="#"
                    author="Hanan Saeed"
                    authorUrl="#"
                    date="September 22, 2025"
                    title="Toddy Tenders: The Families Keeping Island Abundance Flowing"
                    description="Behind every good toddy is a family working before sunrise. A slow, sticky look at the people who climb, tap, wait, and keep one of our oldest island traditions alive."
                    articleUrl="#"
                    imagePosition="top"
                />
            </div>

            {{-- Separator --}}
            <div class="hidden md:block w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white shrink-0"></div>

            <div class="w-[310px] md:w-[480px] shrink-0">
                <x-post.card-spread
                    image="{{ Vite::asset('resources/images/image-11.png') }}"
                    imageAlt="Adduan home cook"
                    category="The Spread"
                    categoryUrl="#"
                    tag="On Ingredients"
                    tagUrl="#"
                    author="Aminath Ahmed"
                    authorUrl="#"
                    date="February 14, 2025"
                    title="Addu on a Plate: Three Women Cooking a Region Back to Life"
                    description="Three Adduan home cooks, one shared mission: keep the island's grandma-level cooking alive. Expect smoky tuna, coconut memories, and recipes passed down like family secrets."
                    articleUrl="#"
                    imagePosition="bottom"
                />
            </div>

            {{-- Separator --}}
            <div class="hidden md:block w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white shrink-0"></div>

            <div class="w-[310px] md:w-[480px] shrink-0">
                <x-post.card-spread
                    image="{{ Vite::asset('resources/images/image-12.png') }}"
                    imageAlt="Rihaakuru being cooked"
                    category="The Spread"
                    categoryUrl="#"
                    tag="On Ingredients"
                    tagUrl="#"
                    author="Aminath Ahmed"
                    authorUrl="#"
                    date="September 1, 2025"
                    title="On Rihaakuru,"
                    description="A thick, salty, slow-cooked potion that tastes like home. How one sauce became the backbone of Maldivian comfort food."
                    articleUrl="#"
                    imagePosition="top"
                />
            </div>

        </div>
    </div>
</div>


{{-- Video Feature Section --}}
<div class="w-full px-5 md:px-10 pt-16 pb-32 bg-gradient-to-b from-tasty-yellow to-white flex flex-col justify-start items-center gap-10">
    <x-post.card-video
        video="{{ Vite::asset('resources/videos/nami-reveli.mp4') }}"
        videoPoster="{{ Vite::asset('resources/images/image-13.png') }}"
        title="Nami at Reveli"
        subtitle="Sushi and steak by the sea"
        description="Nami's Japanese-inspired plates land big flavors in a sleek dining room. Come for the sushi; stay because you forgot you were in Malé for a second."
        author="Mohamed Ashraf"
        authorUrl="/author/mohamed-ashraf"
        date="January 8, 2025"
        articleUrl="/article/nami-at-reveli"
    />
</div>

{{-- Restaurant/Cafe Section - On the Menu --}}
<div class="w-full pt-16 pb-32 bg-tasty-off-white">

    {{-- Mobile: Horizontal Scroll --}}
    <div class="md:hidden flex flex-col gap-10">
        {{-- Section Intro --}}
        <div class="w-full px-5 flex justify-center">
            <x-post.section-intro
                image="{{ Vite::asset('resources/images/image-19.png') }}"
                imageAlt="On the Menu"
                title="On the"
                titleLarge="Menu"
                description="Restaurant reviews, chef crushes, and the dishes we can't stop talking about."
                maxWidth="max-w-[310px] md:max-w-[400px]"
            />
        </div>

        {{-- Scrollable Cards --}}
        <div class="flex gap-10 overflow-x-auto scrollbar-hide scroll-smooth pl-5 pr-20">
            <div class="w-[310px] shrink-0">
                <x-restaurant.card
                    image="{{ Vite::asset('resources/images/image-15.png') }}"
                    imageAlt="Bianco cafe interior"
                    name="BIANCO"
                    subtitle="Minimalist coffee, quality bites."
                    description="A serene all-white interior in Malé, noted for filter & specialty coffees plus French-toast and pasta dishes."
                    :rating="5"
                    articleUrl="/restaurant/bianco"
                />
            </div>

            <div class="w-[310px] shrink-0">
                <x-restaurant.card
                    image="{{ Vite::asset('resources/images/image-16.png') }}"
                    imageAlt="Island Patisserie display"
                    name="Island Patisserie"
                    subtitle="Classic pastries and clean flavours."
                    description="Popular for its pastries, tarts, and cakes, this patisserie offers a menu built on quality butter, technique, & clean flavours."
                    :rating="5"
                    articleUrl="/restaurant/island-patisserie"
                />
            </div>

            <div class="w-[310px] shrink-0">
                <x-restaurant.card
                    image="{{ Vite::asset('resources/images/image-17.png') }}"
                    imageAlt="Tawa cafe"
                    name="Tawa"
                    subtitle="Elevated local bites in Hulhumale'."
                    description="In Hulhumalé, Tawa blends café relaxation with inventive small plates—ideal for a pause or a casual dinner."
                    :rating="3"
                    articleUrl="/restaurant/tawa"
                />
            </div>

            <div class="w-[310px] shrink-0">
                <x-restaurant.card
                    image="{{ Vite::asset('resources/images/image-18.png') }}"
                    imageAlt="Holm Deli sandwiches"
                    name="Holm Deli"
                    subtitle="Italian sandwiches, beautiful decor."
                    description="A new deli known for focaccia sandwiches layered with fresh mozzarella, roasted vegetables, and house-made spreads."
                    :rating="5"
                    articleUrl="/restaurant/holm-deli"
                />
            </div>

            <div class="w-[310px] shrink-0">
                <x-restaurant.card
                    image="{{ Vite::asset('resources/images/image-15.png') }}"
                    imageAlt="Soho cafe"
                    name="Soho"
                    subtitle="Modern comfort food,"
                    description="A lively Malé spot known for stacked burgers, hearty bowls, and strong coffee. Built for casual meet-ups."
                    :rating="3"
                    articleUrl="/restaurant/soho"
                />
            </div>
        </div>
    </div>

    {{-- Desktop: Grid Layout --}}
    <div class="hidden md:block md:px-10">
        <div class="grid grid-cols-[auto_0px_auto_0px_auto] gap-5">
            {{-- Section Intro --}}
            <div class="flex justify-center">
                <x-post.section-intro
                    image="{{ Vite::asset('resources/images/image-19.png') }}"
                    imageAlt="On the Menu"
                    title="On the"
                    titleLarge="Menu"
                    description="Restaurant reviews, chef crushes, and the dishes we can't stop talking about."
                    maxWidth="max-w-[310px] md:max-w-[400px]"
                />
            </div>

            {{-- Separator --}}
            <div class="w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white"></div>

            {{-- Card 1 --}}
            <div class="flex justify-center">
                <x-restaurant.card
                    image="{{ Vite::asset('resources/images/image-15.png') }}"
                    imageAlt="Bianco cafe interior"
                    name="BIANCO"
                    subtitle="Minimalist coffee, quality bites."
                    description="A serene all-white interior in Malé, noted for filter & specialty coffees plus French-toast and pasta dishes."
                    :rating="5"
                    articleUrl="/restaurant/bianco"
                />
            </div>

            {{-- Separator --}}
            <div class="w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white"></div>

            {{-- Card 2 --}}
            <div class="flex justify-center">
                <x-restaurant.card
                    image="{{ Vite::asset('resources/images/image-16.png') }}"
                    imageAlt="Island Patisserie display"
                    name="Island Patisserie"
                    subtitle="Classic pastries and clean flavours."
                    description="Popular for its pastries, tarts, and cakes, this patisserie offers a menu built on quality butter, technique, & clean flavours."
                    :rating="5"
                    articleUrl="/restaurant/island-patisserie"
                />
            </div>

            {{-- Card 3 --}}
            <div class="flex justify-center">
                <x-restaurant.card
                    image="{{ Vite::asset('resources/images/image-17.png') }}"
                    imageAlt="Tawa cafe"
                    name="Tawa"
                    subtitle="Elevated local bites in Hulhumale'."
                    description="In Hulhumalé, Tawa blends café relaxation with inventive small plates—ideal for a pause or a casual dinner."
                    :rating="3"
                    articleUrl="/restaurant/tawa"
                />
            </div>

            {{-- Separator --}}
            <div class="w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white"></div>

            {{-- Card 4 --}}
            <div class="flex justify-center">
                <x-restaurant.card
                    image="{{ Vite::asset('resources/images/image-18.png') }}"
                    imageAlt="Holm Deli sandwiches"
                    name="Holm Deli"
                    subtitle="Italian sandwiches, beautiful decor."
                    description="A new deli known for focaccia sandwiches layered with fresh mozzarella, roasted vegetables, and house-made spreads."
                    :rating="5"
                    articleUrl="/restaurant/holm-deli"
                />
            </div>

            {{-- Separator --}}
            <div class="w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white"></div>

            {{-- Card 5 --}}
            <div class="flex justify-center">
                <x-restaurant.card
                    image="{{ Vite::asset('resources/images/image-15.png') }}"
                    imageAlt="Soho cafe"
                    name="Soho"
                    subtitle="Modern comfort food,"
                    description="A lively Malé spot known for stacked burgers, hearty bowls, and strong coffee. Built for casual meet-ups."
                    :rating="3"
                    articleUrl="/restaurant/soho"
                />
            </div>
        </div>
    </div>

</div>

<x-subscribe/>

@endsection
