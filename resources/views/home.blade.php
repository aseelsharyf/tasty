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


        {{-- Latest Updates Section (Dynamic/Automatic) --}}
        <x-sections.latest-updates
            introImage="{{ Vite::asset('resources/images/latest-updates-transparent.png') }}"
            introImageAlt="Latest Updates"
            titleSmall="Latest"
            titleLarge="Updates"
            description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
            buttonText="More Updates"
            loadAction="recent"
        />

        {{-- Featured Person Section (Dynamic) --}}
        <x-sections.featured-person
            :postId="1"
            tag1="TASTY FEATURE"
            buttonText="Read More"
            bgColor="yellow"
        />

        {{-- The Spread Section (Dynamic) --}}
        <x-sections.spread
            introImage="{{ Vite::asset('resources/images/latest-updates-transparent.png') }}"
            introImageAlt="The Spread"
            titleSmall="The"
            titleLarge="SPREAD"
            description="Explore the latest from our kitchen to yours."
            bgColor="yellow"
            :showIntro="true"
            :showDividers="true"
            dividerColor="white"
            mobileLayout="scroll"
            action="recent"
            :count="4"
        />

        {{-- Featured Video Section (Dynamic) --}}
        <x-sections.featured-video
            :postId="1"
            buttonText="Watch"
            overlayColor="#FFE762"
        />

        {{-- Review Section (Dynamic with Load More) --}}
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
            buttonText="More Reviews"
            :showLoadMore="true"
            action="recent"
            :count="5"
        />

        {{-- Featured Location Section (Dynamic) --}}
        <x-sections.featured-location
            :postId="1"
            tag1="TASTY FEATURE"
            bgColor="yellow"
        />


        {{-- Dynamic Spread Section --}}
        <x-sections.spread
            :showIntro="false"
            bgColor="yellow"
            :showDividers="true"
            dividerColor="white"
            mobileLayout="scroll"
            action="recent"
            :count="5"
        />

        {{-- Newsletter Section --}}
        <x-sections.newsletter />
    </main>

@endsection
