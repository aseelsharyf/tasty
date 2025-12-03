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


<div class="w-full px-10 pt-16 pb-6 bg-tasty-off-white bg-red-100">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 md:items-center gap-10">

            <!-- Left Side (Intro) -->
            <div class="flex flex-col justify-start items-center p-10 space-y-8">
                <img src="{{Vite::asset('resources/images/latest-updates-transparent.png')}}" />
                <div>
                    <div class="font-serif text-3xl md:text-4xl lg:text-5xl leading-none text-stone-900 text-center">Latest</div>
                    <div class="font-serif text-5xl md:text-6xl lg:text-7xl text-stone-900 uppercase leading-tight">Updates</div>
                </div>
                <div class="text-center justify-start text-slate-950 text-2xl font-normal font-serif leading-6">The flavors, characters, and tiny island obsessions that makes the Maldivian food culture.</div>
            </div>

            <!-- Right Side (Card) -->
            <div class="w-full">
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

        </div>

        <div class="pt-10 grid grid-cols-1 md:grid-cols-2 gap-10">

            {{-- Card 1 --}}
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


            {{-- Card 2 --}}
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

            {{-- Card 3 --}}
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

            {{-- Card 4 --}}
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

        <div class="flex justify-center mt-10">
            <a href="#"
               class="group bg-tasty-yellow px-8 py-3 rounded-full inline-flex items-center gap-3 shadow-sm hover:shadow-md hover:bg-stone-50 transition-all duration-300">
                <span class="text-xl font-light leading-none group-hover:rotate-90 transition-transform duration-300">+</span>
                <span class="text-xs md:text-sm font-bold uppercase tracking-widest text-stone-900">More Updates</span>
            </a>
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


<x-subscribe/>

@endsection
