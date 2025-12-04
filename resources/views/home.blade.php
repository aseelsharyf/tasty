@extends('layouts.app')

@section('content')

<x-sections.hero
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


<div class="w-full px-5 lg:px-10 pt-16 pb-32 bg-tasty-off-white">
    <div class="lg:hidden flex flex-col gap-10">
        <x-sections.intro
            image="{{ Vite::asset('resources/images/latest-updates-transparent.png') }}"
            imageAlt="Latest Updates"
            title="Latest"
            titleLarge="Updates"
            description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
        />

        <x-cards.news
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

        <x-cards.news-small
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

        <x-cards.news-small
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

        <x-cards.news-small
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

        <x-cards.news-small
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

    <div class="hidden lg:block">
        <div class="grid grid-cols-[1fr_1fr] gap-5">
            <div class="flex items-center">
                <x-sections.intro
                    image="{{ Vite::asset('resources/images/latest-updates-transparent.png') }}"
                    imageAlt="Latest Updates"
                    title="Latest"
                    titleLarge="Updates"
                    description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
                    maxWidth="max-w-[310px] md:max-w-none"
                />
            </div>

            <div>
                <x-cards.news
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

            <div>
                <x-cards.news-small
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

            <div>
                <x-cards.news-small
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

            <div>
                <x-cards.news-small
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

            <div>
                <x-cards.news-small
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

<x-sections.featured-person
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

<div class="w-full px-5 lg:px-10 pt-16 pb-32 bg-tasty-yellow overflow-x-hidden">
    <div class="flex flex-col gap-10 lg:flex-row lg:gap-10 lg:overflow-x-auto scrollbar-hide lg:scroll-smooth">
        <div class="w-full lg:w-[384px] lg:shrink-0 lg:flex lg:items-center">
            <x-sections.intro
                image="{{ Vite::asset('resources/images/image-07.png') }}"
                imageAlt="The Spread"
                title="The"
                titleLarge="spread"
                description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
            />
        </div>

        <div class="hidden lg:block w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white shrink-0"></div>

        <div class="flex gap-10 overflow-x-auto scrollbar-hide scroll-smooth lg:overflow-visible">
            <div class="w-[310px] lg:w-[480px] shrink-0">
                <x-cards.spread
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

            <div class="hidden lg:block w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white shrink-0"></div>

            <div class="w-[310px] lg:w-[480px] shrink-0">
                <x-cards.spread
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

            <div class="hidden lg:block w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white shrink-0"></div>

            <div class="w-[310px] lg:w-[480px] shrink-0">
                <x-cards.spread
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

            <div class="hidden lg:block w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white shrink-0"></div>

            <div class="w-[310px] lg:w-[480px] shrink-0">
                <x-cards.spread
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

            <div class="hidden lg:block w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white shrink-0"></div>

            <div class="w-[310px] lg:w-[480px] shrink-0">
                <x-cards.spread
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


<div class="w-full px-5 md:px-10 pt-16 pb-32 bg-gradient-to-b from-tasty-yellow to-white flex flex-col justify-start items-center gap-10">
    <x-cards.video
        video="{{ Vite::asset('resources/images/image-13.png') }}"
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

<div class="w-full pt-16 pb-32 bg-tasty-off-white overflow-x-hidden">
    <div class="md:hidden flex flex-col gap-10">
        <div class="w-full px-5 flex justify-center">
            <x-sections.intro
                image="{{ Vite::asset('resources/images/image-19.png') }}"
                imageAlt="On the Menu"
                title="On the"
                titleLarge="Menu"
                description="Restaurant reviews, chef crushes, and the dishes we can't stop talking about."
                maxWidth="max-w-[310px] md:max-w-[400px]"
            />
        </div>

        <div class="flex gap-10 overflow-x-auto scrollbar-hide scroll-smooth pl-5 pr-20">
            <div class="w-[310px] shrink-0">
                <x-cards.restaurant
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
                <x-cards.restaurant
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
                <x-cards.restaurant
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
                <x-cards.restaurant
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
                <x-cards.restaurant
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

    <div class="hidden md:block md:px-10">
        <div class="grid grid-cols-[auto_0px_auto_0px_auto] gap-5">
            <div class="flex justify-center">
                <x-sections.intro
                    image="{{ Vite::asset('resources/images/image-19.png') }}"
                    imageAlt="On the Menu"
                    title="On the"
                    titleLarge="Menu"
                    description="Restaurant reviews, chef crushes, and the dishes we can't stop talking about."
                    maxWidth="max-w-[310px] md:max-w-[400px]"
                />
            </div>

            <div class="w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white"></div>

            <div class="flex justify-center">
                <x-cards.restaurant
                    image="{{ Vite::asset('resources/images/image-15.png') }}"
                    imageAlt="Bianco cafe interior"
                    name="BIANCO"
                    subtitle="Minimalist coffee, quality bites."
                    description="A serene all-white interior in Malé, noted for filter & specialty coffees plus French-toast and pasta dishes."
                    :rating="5"
                    articleUrl="/restaurant/bianco"
                />
            </div>

            <div class="w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white"></div>

            <div class="flex justify-center">
                <x-cards.restaurant
                    image="{{ Vite::asset('resources/images/image-16.png') }}"
                    imageAlt="Island Patisserie display"
                    name="Island Patisserie"
                    subtitle="Classic pastries and clean flavours."
                    description="Popular for its pastries, tarts, and cakes, this patisserie offers a menu built on quality butter, technique, & clean flavours."
                    :rating="5"
                    articleUrl="/restaurant/island-patisserie"
                />
            </div>

            <div class="flex justify-center">
                <x-cards.restaurant
                    image="{{ Vite::asset('resources/images/image-17.png') }}"
                    imageAlt="Tawa cafe"
                    name="Tawa"
                    subtitle="Elevated local bites in Hulhumale'."
                    description="In Hulhumalé, Tawa blends café relaxation with inventive small plates—ideal for a pause or a casual dinner."
                    :rating="3"
                    articleUrl="/restaurant/tawa"
                />
            </div>

            <div class="w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white"></div>

            <div class="flex justify-center">
                <x-cards.restaurant
                    image="{{ Vite::asset('resources/images/image-18.png') }}"
                    imageAlt="Holm Deli sandwiches"
                    name="Holm Deli"
                    subtitle="Italian sandwiches, beautiful decor."
                    description="A new deli known for focaccia sandwiches layered with fresh mozzarella, roasted vegetables, and house-made spreads."
                    :rating="5"
                    articleUrl="/restaurant/holm-deli"
                />
            </div>

            <div class="w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white"></div>

            <div class="flex justify-center">
                <x-cards.restaurant
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


<x-sections.featured-content
    image="{{ Vite::asset('resources/images/image-20.png') }}"
    imageAlt="Ceylon"
    heading="CEYLON"
    subheading="Where the air smells like spice, surf, and something softly familiar."
    tag1="tasty feature"
    tag1Url="#"
    tag2="food destinations"
    tag2Url="#"
    description="Two weeks in Lanka, documenting dishes and cooks who give the island its food identity."
    buttonText=""
    buttonUrl="#"
    imageBgColor="bg-tasty-yellow"
    contentBgColor="bg-tasty-yellow"
/>

<div class="w-full px-5 lg:px-10 py-16 bg-tasty-yellow overflow-hidden">
    <div class="flex gap-10 overflow-x-auto scrollbar-hide scroll-smooth">
        <div class="w-[310px] lg:w-[480px] shrink-0">
            <x-cards.spread
                image="{{ Vite::asset('resources/images/image-21.png') }}"
                imageAlt="Tea hill picker in green fields"
                category="The Spread"
                categoryUrl="#"
                tag="On Ingredients"
                tagUrl="#"
                author="Aminath Ahmed"
                authorUrl="#"
                date="February 14, 2025"
                title="Where Ceylon Begins: Walking the Endless Green of Lanka's Tea Hills"
                description="Walking through misty rows of emerald green, meeting the pickers and stories behind Sri Lanka's most iconic leaf."
                articleUrl="#"
                imagePosition="top"
            />
        </div>

        <div class="hidden lg:block w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white shrink-0"></div>

        <div class="w-[310px] lg:w-[480px] shrink-0">
            <x-cards.spread
                image="{{ Vite::asset('resources/images/image-22.png') }}"
                imageAlt="Heritage café interior with tables"
                category="The Spread"
                categoryUrl="#"
                tag="Section"
                tagUrl="#"
                author="Mohamed Ashraf"
                authorUrl="#"
                date="January 8, 2025"
                title="A Morning at Colombo's Heritage Café, Where Time Moves Softer"
                description="Quiet columns, slow mornings, and a café that pours history with every cup. A taste of Sri Lanka's timeless charm."
                articleUrl="#"
                imagePosition="bottom"
            />
        </div>

        <div class="hidden lg:block w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white shrink-0"></div>

        <div class="w-[310px] lg:w-[480px] shrink-0">
            <x-cards.spread
                image="{{ Vite::asset('resources/images/image-23.png') }}"
                imageAlt="Street vendor frying snacks"
                category="The Spread"
                categoryUrl="#"
                tag="On Ingredients"
                tagUrl="#"
                author="Hanan Saeed"
                authorUrl="#"
                date="September 22, 2025"
                title="Heat, Hustle & Street Bites"
                description="From spicy short eats to crispy fritters made on the fly, Sri Lanka's street vendors turn sidewalks into open-air kitchens. Loud, fast, chaotic — and absolutely delicious."
                articleUrl="#"
                imagePosition="top"
            />
        </div>

        <div class="hidden lg:block w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white shrink-0"></div>

        <div class="w-[310px] lg:w-[480px] shrink-0">
            <x-cards.spread
                image="{{ Vite::asset('resources/images/image-24.png') }}"
                imageAlt="Hanging bunches of bananas at market"
                category="The Spread"
                categoryUrl="#"
                tag="Section"
                tagUrl="#"
                author="Aishath Fathimath"
                authorUrl="#"
                date="October 27, 2025"
                title="Banana Country: Exploring the Wild, Colorful World of Lanka's Many Varieties"
                description="Dozens of shapes, sizes, and sweetness levels — a colorful dive into Sri Lanka's banana farms and their wild variety."
                articleUrl="#"
                imagePosition="bottom"
            />
        </div>

        <div class="hidden lg:block w-0 self-stretch outline outline-1 outline-offset-[-0.50px] outline-white shrink-0"></div>

        <div class="w-[310px] lg:w-[480px] shrink-0">
            <x-cards.spread
                image="{{ Vite::asset('resources/images/image-25.png') }}"
                imageAlt="Man preparing banana leaves in a rustic doorway"
                category="The Spread"
                categoryUrl="#"
                tag="On Ingredients"
                tagUrl="#"
                author="Aminath Ahmed"
                authorUrl="#"
                date="September 1, 2025"
                title="Cooking on Green Gold: How the Banana Leaf Shapes Sri Lankan Flavor"
                description="More than a natural plate, the banana leaf transforms aroma and texture. From wrapping curries to steaming rice, we explore how this simple leaf carries centuries of Lankan food tradition."
                articleUrl="#"
                imagePosition="top"
            />
        </div>
    </div>
</div>


<div class="w-full px-5 lg:px-10 pt-16 pb-32 bg-tasty-light-gray" style="background: linear-gradient(to bottom, var(--color-tasty-yellow) 0%, var(--color-tasty-light-gray) 15%);">
    <div class="flex flex-col lg:grid lg:grid-cols-2 gap-10 lg:gap-16 mb-10">
        <div class="flex items-center justify-center">
            <x-sections.intro
                image="{{ Vite::asset('resources/images/image-07.png') }}"
                imageAlt="Everyday Cooking"
                title="Everyday"
                titleLarge="Cooking"
                description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
                maxWidth="max-w-[310px] lg:max-w-[400px]"
            />
        </div>

        <div class="w-full">
            <x-cards.recipe
                image="{{ Vite::asset('resources/images/image-26.png') }}"
                imageAlt="Illustrated cooking scene"
                :tags="['recipe','best of']"
                author="Author Name"
                authorUrl="#"
                date="November 12, 2025"
                title="12 Must-Try Recipes to Make This December"
                description="The recipe for choosing the perfect resort is in the menu! Ever look up hotels and resorts and see terms like 'all inclusive' and 'European plan' and not quite know what they mean?"
                url="#"
            />
        </div>
    </div>

    <div class="flex lg:grid lg:grid-cols-3 items-start gap-5 lg:gap-10 overflow-x-auto lg:overflow-visible scrollbar-hide scroll-smooth">
        <x-cards.recipe-small
            image="{{ Vite::asset('resources/images/image-27.png') }}"
            imageAlt="Squash pasta closeup"
            :tags="['recipe','vegan']"
            author="Author Name"
            authorUrl="#"
            date="November 12, 2025"
            title="How to Cook Squash Pasta"
            url="#"
        />

        <x-cards.recipe-small
            image="{{ Vite::asset('resources/images/image-28.png') }}"
            imageAlt="Chocolate chip cookie"
            :tags="['recipe','sweet tooth']"
            author="Author Name"
            authorUrl="#"
            date="November 12, 2025"
            title="Chocolate Chip Cookies to Die for"
            url="#"
        />

        <x-cards.recipe-small
            image="{{ Vite::asset('resources/images/image-29.png') }}"
            imageAlt="Omelet with mushrooms"
            :tags="['recipe','Maldivian']"
            author="Author Name"
            authorUrl="#"
            date="November 12, 2025"
            title="How to Make a Fantastic Omelet"
            url="#"
        />
    </div>
</div>


<x-sections.subscribe/>

@endsection
