@extends('layouts.app')

@section('content')


{{-- Full Customization --}}
<x-post.hero
    image="https://images.unsplash.com/photo-1615322712569-8eb81aa62f80?q=80&w=1287&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
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

{{-- Center Aligned Version --}}
{{-- <x-post.hero
    image="https://images.unsplash.com/photo-1618416682145-2fe1aaa6bd40?q=80&w=1287&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
    category="Food & Drink"
    categoryUrl="#"
    author="Jane Doe"
    authorUrl="/author/jane-doe"
    date="December 1, 2025"
    title="LATE NIGHT EATS"
    subtitle="Exploring the city's"
    subtitleItalic="hidden culinary gems"
    buttonUrl="/article/late-night-eats"
    alignment="bottom-left"
    bgColor="bg-red-500"
/> --}}



<div class="w-full px-10 pt-16 pb-6 bg-tasty-off-white bg-red-100">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 md:items-center gap-10">

            <!-- Left Side (Intro) -->
            <div class="flex flex-col justify-start items-center">
                <img src="{{Vite::asset('resources/images/latest-updates-transparent.png')}}" />
                <div class="py-2">
                    <div class="font-serif text-3xl md:text-4xl lg:text-5xl leading-none text-stone-900 text-center">Latest</div>
                    <div class="font-serif text-5xl md:text-6xl lg:text-7xl text-stone-900 uppercase leading-tight">Updates</div>
                </div>
                <div class="py-2 text-center justify-start text-slate-950 text-2xl font-normal font-serif leading-6">The flavors, characters, and tiny island obsessions that makes the Maldivian food culture.</div>
            </div>

            <!-- Right Side (Card) -->
            <div class="w-full">
                <x-post.card-news
                    image="https://images.unsplash.com/photo-1565299585323-38d6b0865b47?q=80&w=3180&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
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

        </div>

        <div class="pt-10 grid grid-cols-1 md:grid-cols-2 gap-10">

            {{-- Card 1 --}}
            <x-post.card-news-small
                image="https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=3270&auto=format&fit=crop"
                imageAlt="Delicious pasta dish"
                category="Recipes"
                categoryUrl="/category/recipes"
                tag="Italian"
                tagUrl="/tag/italian"
                author="Sarah Johnson"
                authorUrl="/author/sarah-johnson"
                date="November 28, 2025"
                title="Homemade Pasta Perfection"
                description="Master the art of making fresh pasta from scratch with our step-by-step guide."
                articleUrl="/article/homemade-pasta"
            />

            {{-- Card 2 --}}
            <x-post.card-news-small
                image="https://images.unsplash.com/photo-1559339352-11d035aa65de?q=80&w=3174&auto=format&fit=crop"
                imageAlt="Coffee and pastries"
                category="Café Culture"
                categoryUrl="/category/cafe-culture"
                tag="Morning Eats"
                tagUrl="/tag/morning-eats"
                author="David Chen"
                authorUrl="/author/david-chen"
                date="November 25, 2025"
                title="Best Breakfast Spots Downtown"
                description="Discover the hidden gems serving the perfect morning brew and pastries."
                articleUrl="/article/breakfast-spots"
            />

            {{-- Card 3 --}}
            <x-post.card-news-small
                image="https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?q=80&w=3087&auto=format&fit=crop"
                imageAlt="Fresh salad bowl"
                category="Healthy Eats"
                categoryUrl="/category/healthy-eats"
                tag="Nutrition"
                tagUrl="/tag/nutrition"
                author="Emma Williams"
                authorUrl="/author/emma-williams"
                date="November 22, 2025"
                title="Summer Salad Revolution"
                description="Fresh, vibrant, and nutritious salad recipes that'll change your lunch game."
                articleUrl="/article/summer-salads"
            />

            {{-- Card 4 --}}
            <x-post.card-news-small
                image="https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?q=80&w=3270&auto=format&fit=crop"
                imageAlt="Sushi platter"
                category="World Cuisine"
                categoryUrl="/category/world-cuisine"
                tag="Japanese"
                tagUrl="/tag/japanese"
                author="Kenji Tanaka"
                authorUrl="/author/kenji-tanaka"
                date="November 20, 2025"
                title="Sushi Master Class"
                description="Learn the ancient techniques of sushi making from a master chef."
                articleUrl="/article/sushi-masterclass"
            />

        </div>

        <div class="flex justify-center mt-10">
            <a href="#"
               class="group bg-tasty-yellow px-8 py-3 rounded-full inline-flex items-center gap-3 shadow-sm hover:shadow-md hover:bg-stone-50 transition-all duration-300">
                <span class="text-xl font-light leading-none group-hover:rotate-90 transition-transform duration-300">+</span>
                <span class="text-xs md:text-sm font-bold uppercase tracking-widest text-stone-900">More Updates</span>
            </a>
        </div>


    </div>

</div>


<x-subscribe/>

@endsection
