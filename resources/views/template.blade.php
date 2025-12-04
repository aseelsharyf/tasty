@extends('layouts.app')

@section('content')

{{-- Hero Section --}}
<x-hero
    image="{{ Vite::asset('resources/images/image-01.png') }}"
    imageAlt="Delicious fried chicken"
    :tags="['On Culture', 'Mohamed Ashraf']"
    date="November 12, 2025"
    title="BITE CLUB"
    subtitle="The ghost kitchen feeding"
    subtitleItalic="Malé after dark"
    buttonText="Read More"
    buttonUrl="#"
    bgColor="bg-tasty-yellow"
    align="left"
/>

{{-- Latest Updates Section --}}
<section class="w-full bg-tasty-yellow py-16 md:py-32">
    <div class="px-5 md:px-10">
        {{-- Mobile Layout --}}
        <div class="md:hidden flex flex-col gap-10">
            {{-- Header --}}
            <div class="flex justify-center">
                <x-sections.header
                    image="{{ Vite::asset('resources/images/latest-updates-transparent.png') }}"
                    imageAlt="Latest Updates"
                    titleSmall="Latest"
                    titleLarge="Updates"
                    description="News, features and stories from around the table."
                />
            </div>

            {{-- Large News Card --}}
            <x-cards.news
                image="{{ Vite::asset('resources/images/image-02.png') }}"
                imageAlt="Tasty launch event"
                :tags="['latest', 'event']"
                author="Mohamed Ashraf"
                date="November 12, 2025"
                title="Tasty Officially Launches at Exclusive Malé Event"
                description="Malé's newest food platform kicked off with a night of bites, beats, and big plans. Here's what went down."
                url="#"
                size="large"
            />

            {{-- Small News Cards --}}
            <div class="flex flex-col gap-8">
                <x-cards.news
                    image="{{ Vite::asset('resources/images/image-03.png') }}"
                    imageAlt="Nami restaurant"
                    :tags="['on the menu', 'review']"
                    author="Aminath Ahmed"
                    date="January 8, 2025"
                    title="Nami at Reveli: Sushi and Steak by the Sea"
                    url="#"
                    size="small"
                />

                <x-cards.news
                    image="{{ Vite::asset('resources/images/image-04.png') }}"
                    imageAlt="December recipes"
                    :tags="['recipe', 'best of']"
                    author="Hanan Saeed"
                    date="December 1, 2025"
                    title="12 Must-Try Recipes to Make This December"
                    url="#"
                    size="small"
                />

                <x-cards.news
                    image="{{ Vite::asset('resources/images/image-05.png') }}"
                    imageAlt="Resort dining"
                    :tags="['travel', 'guide']"
                    author="Aishath Fathimath"
                    date="November 28, 2025"
                    title="The Ultimate Guide to Maldivian Resort Dining"
                    url="#"
                    size="small"
                />
            </div>
        </div>

        {{-- Desktop Layout --}}
        <div class="hidden md:flex gap-10">
            {{-- Header --}}
            <div class="w-[400px] shrink-0 flex items-start">
                <x-sections.header
                    image="{{ Vite::asset('resources/images/latest-updates-transparent.png') }}"
                    imageAlt="Latest Updates"
                    titleSmall="Latest"
                    titleLarge="Updates"
                    description="News, features and stories from around the table."
                />
            </div>

            <x-sections.divider />

            {{-- Large News Card --}}
            <div class="flex-1 max-w-[600px]">
                <x-cards.news
                    image="{{ Vite::asset('resources/images/image-02.png') }}"
                    imageAlt="Tasty launch event"
                    :tags="['latest', 'event']"
                    author="Mohamed Ashraf"
                    date="November 12, 2025"
                    title="Tasty Officially Launches at Exclusive Malé Event"
                    description="Malé's newest food platform kicked off with a night of bites, beats, and big plans. Here's what went down."
                    url="#"
                    size="large"
                />
            </div>

            <x-sections.divider />

            {{-- Small News Cards Column --}}
            <div class="flex-1 flex flex-col gap-8">
                <x-cards.news
                    image="{{ Vite::asset('resources/images/image-03.png') }}"
                    imageAlt="Nami restaurant"
                    :tags="['on the menu', 'review']"
                    author="Aminath Ahmed"
                    date="January 8, 2025"
                    title="Nami at Reveli: Sushi and Steak by the Sea"
                    url="#"
                    size="small"
                />

                <x-cards.news
                    image="{{ Vite::asset('resources/images/image-04.png') }}"
                    imageAlt="December recipes"
                    :tags="['recipe', 'best of']"
                    author="Hanan Saeed"
                    date="December 1, 2025"
                    title="12 Must-Try Recipes to Make This December"
                    url="#"
                    size="small"
                />

                <x-cards.news
                    image="{{ Vite::asset('resources/images/image-05.png') }}"
                    imageAlt="Resort dining"
                    :tags="['travel', 'guide']"
                    author="Aishath Fathimath"
                    date="November 28, 2025"
                    title="The Ultimate Guide to Maldivian Resort Dining"
                    url="#"
                    size="small"
                />
            </div>
        </div>
    </div>
</section>

{{-- The Spread Section --}}
<x-sections.scroll-row bgColor="bg-tasty-yellow">
    {{-- Section Header --}}
    <div class="w-full md:w-[424px] md:shrink-0 md:flex md:items-center">
        <x-sections.header
            image="{{ Vite::asset('resources/images/image-07.png') }}"
            imageAlt="The Spread"
            titleSmall="The"
            titleLarge="spread"
            description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
        />
    </div>

    <x-sections.divider />

    {{-- Scrollable Cards --}}
    <div class="flex gap-10 overflow-x-auto scrollbar-hide scroll-smooth md:overflow-visible">
        <div class="w-[310px] md:w-[480px] shrink-0">
            <x-cards.article
                image="{{ Vite::asset('resources/images/image-08.png') }}"
                imageAlt="BBQ grilling"
                :tags="['the spread', 'on ingredients']"
                author="Mohamed Ashraf"
                authorUrl="#"
                date="January 8, 2025"
                title="Salt, Smoke & Stories"
                description="Malé's unofficial BBQ scene: backyard grills, midnight marinades, and home pitmasters turning small spaces into smoky flavor labs."
                url="#"
                layout="image-top"
            />
        </div>

        <x-sections.divider />

        <div class="w-[310px] md:w-[480px] shrink-0">
            <x-cards.article
                image="{{ Vite::asset('resources/images/image-09.png') }}"
                imageAlt="Job fish"
                :tags="['the spread', 'ingredients']"
                author="Aminath Ahmed"
                authorUrl="#"
                date="February 14, 2025"
                title="Job Fish: The Underrated Catch Running the Whole Country"
                description="Lean, shiny, firm as hell—job fish does everything except brag about itself. Here's why this humble catch is the quiet backbone of Maldivian 'freshness.'"
                url="#"
                layout="image-bottom"
            />
        </div>

        <x-sections.divider />

        <div class="w-[310px] md:w-[480px] shrink-0">
            <x-cards.article
                image="{{ Vite::asset('resources/images/image-10.png') }}"
                imageAlt="Toddy tapping"
                :tags="['the spread', 'ingredients']"
                author="Hanan Saeed"
                authorUrl="#"
                date="September 22, 2025"
                title="Toddy Tenders: The Families Keeping Island Abundance Flowing"
                description="Behind every good toddy is a family working before sunrise. A slow, sticky look at the people who climb, tap, wait, and keep one of our oldest island traditions alive."
                url="#"
                layout="image-top"
            />
        </div>

        <x-sections.divider />

        <div class="w-[310px] md:w-[480px] shrink-0">
            <x-cards.article
                image="{{ Vite::asset('resources/images/image-11.png') }}"
                imageAlt="Addu cooking"
                :tags="['the spread', 'the south']"
                author="Aminath Ahmed"
                authorUrl="#"
                date="February 14, 2025"
                title="Addu on a Plate: Three Women Cooking a Region Back to Life"
                description="Three Adduan home cooks, one shared mission: keep the island's grandma-level cooking alive. Expect smoky tuna, coconut memories, and recipes passed down like family secrets."
                url="#"
                layout="image-bottom"
            />
        </div>

        <x-sections.divider />

        <div class="w-[310px] md:w-[480px] shrink-0 pr-10">
            <x-cards.article
                image="{{ Vite::asset('resources/images/image-12.png') }}"
                imageAlt="Rihaakuru"
                :tags="['the spread', 'on ingredients']"
                author="Aminath Ahmed"
                authorUrl="#"
                date="September 1, 2025"
                title="On Rihaakuru,"
                description="A thick, salty, slow-cooked potion that tastes like home. How one sauce became the backbone of Maldivian comfort food."
                url="#"
                layout="image-top"
            />
        </div>
    </div>
</x-sections.scroll-row>

{{-- Video Feature Section --}}
<section class="w-full bg-gradient-to-b from-tasty-yellow to-white py-16 md:py-32 flex justify-center">
    <div class="px-5 md:px-10">
        <x-cards.video
            video="{{ Vite::asset('resources/images/image-13.png') }}"
            poster="{{ Vite::asset('resources/images/image-13.png') }}"
            :tags="['on the menu', 'review']"
            title="Nami at Reveli"
            subtitle="Sushi and steak by the sea"
            description="Nami's Japanese-inspired plates land big flavors in a sleek dining room. Come for the sushi; stay because you forgot you were in Malé for a second."
            author="Mohamed Ashraf"
            date="January 8, 2025"
            url="#"
        />
    </div>
</section>

{{-- On the Menu - Restaurant Reviews --}}
<section class="w-full bg-tasty-off-white py-16 md:py-32">
    {{-- Mobile: Horizontal Scroll --}}
    <div class="md:hidden flex flex-col gap-10">
        <div class="px-5 flex justify-center">
            <x-sections.header
                image="{{ Vite::asset('resources/images/image-19.png') }}"
                imageAlt="On the Menu"
                titleSmall="On the"
                titleLarge="Menu"
                description="Restaurant reviews, chef crushes, and the dishes we can't stop talking about."
            />
        </div>

        <div class="flex gap-10 overflow-x-auto scrollbar-hide scroll-smooth pl-5 pr-20">
            <div class="w-[310px] shrink-0">
                <x-cards.restaurant
                    image="{{ Vite::asset('resources/images/image-15.png') }}"
                    imageAlt="Bianco"
                    name="BIANCO"
                    tagline="Minimalist coffee, quality bites."
                    description="A serene all-white interior in Malé, noted for filter & specialty coffees plus French-toast and pasta dishes."
                    :rating="5"
                    url="#"
                />
            </div>

            <div class="w-[310px] shrink-0">
                <x-cards.restaurant
                    image="{{ Vite::asset('resources/images/image-16.png') }}"
                    imageAlt="Island Patisserie"
                    name="Island Patisserie"
                    tagline="Classic pastries and clean flavours."
                    description="Popular for its pastries, tarts, and cakes, this patisserie offers a menu built on quality butter, technique, & clean flavours."
                    :rating="5"
                    url="#"
                />
            </div>

            <div class="w-[310px] shrink-0">
                <x-cards.restaurant
                    image="{{ Vite::asset('resources/images/image-17.png') }}"
                    imageAlt="Tawa"
                    name="Tawa"
                    tagline="Elevated local bites in Hulhumale'."
                    description="In Hulhumalé, Tawa blends café relaxation with inventive small plates—ideal for a pause or a casual dinner."
                    :rating="3"
                    url="#"
                />
            </div>

            <div class="w-[310px] shrink-0">
                <x-cards.restaurant
                    image="{{ Vite::asset('resources/images/image-18.png') }}"
                    imageAlt="Holm Deli"
                    name="Holm Deli"
                    tagline="Italian sandwiches, beautiful decor."
                    description="A new deli known for focaccia sandwiches layered with fresh mozzarella, roasted vegetables, and house-made spreads."
                    :rating="5"
                    url="#"
                />
            </div>

            <div class="w-[310px] shrink-0">
                <x-cards.restaurant
                    image="{{ Vite::asset('resources/images/image-15.png') }}"
                    imageAlt="Soho"
                    name="Soho"
                    tagline="Modern comfort food,"
                    description="A lively Malé spot known for stacked burgers, hearty bowls, and strong coffee. Built for casual meet-ups."
                    :rating="3"
                    url="#"
                />
            </div>
        </div>
    </div>

    {{-- Desktop: Grid Layout --}}
    <div class="hidden md:block px-10">
        <div class="flex gap-10">
            <div class="w-[400px] shrink-0 flex items-center">
                <x-sections.header
                    image="{{ Vite::asset('resources/images/image-19.png') }}"
                    imageAlt="On the Menu"
                    titleSmall="On the"
                    titleLarge="Menu"
                    description="Restaurant reviews, chef crushes, and the dishes we can't stop talking about."
                />
            </div>

            <x-sections.divider />

            <div class="flex-1">
                <x-cards.restaurant
                    image="{{ Vite::asset('resources/images/image-15.png') }}"
                    imageAlt="Bianco"
                    name="BIANCO"
                    tagline="Minimalist coffee, quality bites."
                    description="A serene all-white interior in Malé, noted for filter & specialty coffees plus French-toast and pasta dishes."
                    :rating="5"
                    url="#"
                />
            </div>

            <x-sections.divider />

            <div class="flex-1">
                <x-cards.restaurant
                    image="{{ Vite::asset('resources/images/image-16.png') }}"
                    imageAlt="Island Patisserie"
                    name="Island Patisserie"
                    tagline="Classic pastries and clean flavours."
                    description="Popular for its pastries, tarts, and cakes, this patisserie offers a menu built on quality butter, technique, & clean flavours."
                    :rating="5"
                    url="#"
                />
            </div>
        </div>

        {{-- Second Row --}}
        <div class="flex gap-10 mt-16">
            <div class="flex-1">
                <x-cards.restaurant
                    image="{{ Vite::asset('resources/images/image-17.png') }}"
                    imageAlt="Tawa"
                    name="Tawa"
                    tagline="Elevated local bites in Hulhumale'."
                    description="In Hulhumalé, Tawa blends café relaxation with inventive small plates—ideal for a pause or a casual dinner."
                    :rating="3"
                    url="#"
                />
            </div>

            <x-sections.divider />

            <div class="flex-1">
                <x-cards.restaurant
                    image="{{ Vite::asset('resources/images/image-18.png') }}"
                    imageAlt="Holm Deli"
                    name="Holm Deli"
                    tagline="Italian sandwiches, beautiful decor."
                    description="A new deli known for focaccia sandwiches layered with fresh mozzarella, roasted vegetables, and house-made spreads."
                    :rating="5"
                    url="#"
                />
            </div>

            <x-sections.divider />

            <div class="flex-1">
                <x-cards.restaurant
                    image="{{ Vite::asset('resources/images/image-15.png') }}"
                    imageAlt="Soho"
                    name="Soho"
                    tagline="Modern comfort food,"
                    description="A lively Malé spot known for stacked burgers, hearty bowls, and strong coffee. Built for casual meet-ups."
                    :rating="3"
                    url="#"
                />
            </div>
        </div>
    </div>
</section>

{{-- Ceylon Feature Section --}}
<x-feature-destination
    image="{{ Vite::asset('resources/images/image-20.png') }}"
    imageAlt="Ceylon landscape"
    heading="CEYLON"
    subheading="Where the air smells like spice, surf, and something softly familiar."
    :tags="['tasty feature', 'food destinations']"
    description="Two weeks in Lanka, documenting dishes and cooks who give the island its food identity."
    bgColor="bg-tasty-yellow"
/>

{{-- Ceylon Spread Section --}}
<x-sections.scroll-row bgColor="bg-tasty-yellow">
    <div class="flex gap-10 overflow-x-auto scrollbar-hide scroll-smooth md:overflow-visible">
        <div class="w-[310px] md:w-[480px] shrink-0">
            <x-cards.article
                image="{{ Vite::asset('resources/images/image-08.png') }}"
                imageAlt="Tea hills"
                :tags="['the spread', 'on ingredients']"
                author="Aminath Ahmed"
                authorUrl="#"
                date="February 14, 2025"
                title="Where Ceylon Begins: Walking the Endless Green of Lanka's Tea Hills"
                description="Walking through misty rows of emerald green, meeting the pickers and stories behind Sri Lanka's most iconic leaf."
                url="#"
                layout="image-top"
            />
        </div>

        <x-sections.divider />

        <div class="w-[310px] md:w-[480px] shrink-0">
            <x-cards.article
                image="{{ Vite::asset('resources/images/image-09.png') }}"
                imageAlt="Heritage cafe"
                :tags="['section', 'tag']"
                author="Mohamed Ashraf"
                authorUrl="#"
                date="January 8, 2025"
                title="A Morning at Colombo's Heritage Café, Where Time Moves Softer"
                description="Quiet columns, slow mornings, and a café that pours history with every cup. A taste of Sri Lanka's timeless charm."
                url="#"
                layout="image-bottom"
            />
        </div>

        <x-sections.divider />

        <div class="w-[310px] md:w-[480px] shrink-0">
            <x-cards.article
                image="{{ Vite::asset('resources/images/image-10.png') }}"
                imageAlt="Street food"
                :tags="['the spread', 'on ingredients']"
                author="Hanan Saeed"
                authorUrl="#"
                date="September 22, 2025"
                title="Heat, Hustle & Street Bites"
                description="From spicy short eats to crispy fritters made on the fly, Sri Lanka's street vendors turn sidewalks into open-air kitchens. Loud, fast, chaotic — and absolutely delicious."
                url="#"
                layout="image-top"
            />
        </div>

        <x-sections.divider />

        <div class="w-[310px] md:w-[480px] shrink-0">
            <x-cards.article
                image="{{ Vite::asset('resources/images/image-11.png') }}"
                imageAlt="Bananas"
                :tags="['section', 'tag']"
                author="Aishath Fathimath"
                authorUrl="#"
                date="October 27, 2025"
                title="Banana Country: Exploring the Wild, Colorful World of Lanka's Many Varieties"
                description="Dozens of shapes, sizes, and sweetness levels — a colorful dive into Sri Lanka's banana farms and their wild variety."
                url="#"
                layout="image-bottom"
            />
        </div>

        <x-sections.divider />

        <div class="w-[310px] md:w-[480px] shrink-0 pr-10">
            <x-cards.article
                image="{{ Vite::asset('resources/images/image-12.png') }}"
                imageAlt="Banana leaf cooking"
                :tags="['the spread', 'on ingredients']"
                author="Aminath Ahmed"
                authorUrl="#"
                date="September 1, 2025"
                title="Cooking on Green Gold: How the Banana Leaf Shapes Sri Lankan Flavor"
                description="More than a natural plate, the banana leaf transforms aroma and texture. From wrapping curries to steaming rice, we explore how this simple leaf carries centuries of Lankan food tradition."
                url="#"
                layout="image-top"
            />
        </div>
    </div>
</x-sections.scroll-row>

{{-- Everyday Cooking Section --}}
<section class="w-full py-16 md:py-32 bg-gradient-to-b from-tasty-yellow via-tasty-yellow/50 to-white">
    <div class="px-5 md:px-10">
        {{-- Header Row --}}
        <div class="flex flex-col md:flex-row gap-10 items-center justify-center mb-16">
            <div class="w-full md:w-[660px]">
                <x-sections.header
                    image="{{ Vite::asset('resources/images/latest-updates-transparent.png') }}"
                    imageAlt="Everyday Cooking"
                    titleSmall="Everyday"
                    titleLarge="Cooking"
                    description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
                />
            </div>

            <div class="w-full md:w-[660px]">
                <x-cards.recipe
                    image="{{ Vite::asset('resources/images/image-02.png') }}"
                    imageAlt="Must-try recipes"
                    :tags="['recipe', 'best of']"
                    author="Author Name"
                    date="November 12, 2025"
                    title="12 Must-Try Recipes to Make This December"
                    description="The recipe for choosing the perfect resort is in the menu! Ever look up hotels and resorts and see terms like 'all inclusive' and 'European plan' and not quite know what they mean?"
                    url="#"
                    size="featured"
                />
            </div>
        </div>

        {{-- Recipe Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
            <x-cards.recipe
                image="{{ Vite::asset('resources/images/image-03.png') }}"
                imageAlt="Squash Pasta"
                :tags="['recipe', 'vegan']"
                author="Author Name"
                date="November 12, 2025"
                title="How to Cook Squash Pasta"
                url="#"
            />

            <x-cards.recipe
                image="{{ Vite::asset('resources/images/image-04.png') }}"
                imageAlt="Chocolate Chip Cookies"
                :tags="['recipe', 'sweet tooth']"
                author="Author Name"
                date="November 12, 2025"
                title="Chocolate Chip Cookies to Die for"
                url="#"
            />

            <x-cards.recipe
                image="{{ Vite::asset('resources/images/image-05.png') }}"
                imageAlt="Fantastic Omelet"
                :tags="['recipe', 'maldivian']"
                author="Author Name"
                date="November 12, 2025"
                title="How to Make a Fantastic Omelet"
                url="#"
            />
        </div>
    </div>
</section>

{{-- Add to Cart / Pantry Section --}}
<section class="w-full bg-white py-16 md:py-32">
    <div class="px-5 md:px-10">
        {{-- Section Header --}}
        <div class="text-center mb-16">
            <h2 class="font-display text-6xl md:text-8xl text-tasty-blue-black tracking-tight uppercase mb-6">
                Add to Cart
            </h2>
            <p class="font-sans text-xl md:text-2xl text-tasty-blue-black max-w-2xl mx-auto">
                Ingredients, tools, and staples we actually use.
            </p>
        </div>

        {{-- Pantry Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 max-w-[1358px] mx-auto">
            <x-cards.pantry
                image="{{ Vite::asset('resources/images/image-14.png') }}"
                imageAlt="Vitamix Ascent X2"
                :tags="['pantry', 'appliance']"
                name="Vitamix Ascent X2"
                description="Powerful, precise, and fast — a blender that handles anything you throw at it."
                url="#"
            />

            <x-cards.pantry
                image="{{ Vite::asset('resources/images/image-15.png') }}"
                imageAlt="Gozney Arc XL"
                :tags="['pantry', 'appliance']"
                name="Gozney Arc XL"
                description="A compact oven with real flame power — crisp, blistered pizza every time."
                url="#"
            />

            <x-cards.pantry
                image="{{ Vite::asset('resources/images/image-16.png') }}"
                imageAlt="Ratio Eight"
                :tags="['pantry', 'appliance']"
                name="Ratio Eight Series 2 Coffee Maker"
                description="Beautiful design, foolproof brewing, and café-level coffee at home."
                url="#"
            />
        </div>
    </div>
</section>

{{-- Newsletter Subscribe --}}
<x-subscribe />

@endsection
