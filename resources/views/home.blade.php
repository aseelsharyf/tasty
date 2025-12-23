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

        {{-- Recipe Section (Dynamic) --}}
        <x-sections.recipe
            :showIntro="true"
            introImage="{{ Vite::asset('resources/images/on-the-menu.png') }}"
            introImageAlt="Everyday Cooking"
            titleSmall="Everyday"
            titleLarge="COOKING"
            description="The flavors, characters, and tiny island obsessions that makes the Maldivian food culture."
            bgColor="yellow"
            gradient="top"
            mobileLayout="grid"
            action="recent"
            :count="3"
        />

        {{-- Add to Cart Section --}}
        <x-sections.add-to-cart
            title="ADD TO CART"
            description="Ingredients, tools, and staples we actually use."
            bgColor="white"
            :products="[
                [
                    'title' => 'Vitamix Ascent X2',
                    'description' => 'Powerful, precise, and fast — a blender that handles anything you throw at it.',
                    'image' => 'https://www.figma.com/api/mcp/asset/38c405a2-69ad-46db-939c-6c849d224840',
                    'imageAlt' => 'Vitamix Ascent X2 Blender',
                    'tags' => ['PANTRY', 'APPLIANCE'],
                    'url' => '#',
                ],
                [
                    'title' => 'Gozney Arc XL',
                    'description' => 'A compact oven with real flame power — crisp, blistered pizza every time.',
                    'image' => 'https://www.figma.com/api/mcp/asset/1dc5a2f7-b226-4f7f-adbd-c514e676ac93',
                    'imageAlt' => 'Gozney Arc XL Pizza Oven',
                    'tags' => ['PANTRY', 'APPLIANCE'],
                    'url' => '#',
                ],
                [
                    'title' => 'Ratio Eight Series 2 Coffee Maker',
                    'description' => 'Beautiful design, foolproof brewing, and café-level coffee at home.',
                    'image' => 'https://www.figma.com/api/mcp/asset/0c3d053a-3cce-472d-8d89-d315c64a4603',
                    'imageAlt' => 'Ratio Eight Series 2 Coffee Maker',
                    'tags' => ['PANTRY', 'APPLIANCE'],
                    'url' => '#',
                ],
            ]"
        />

        {{-- Newsletter Section --}}
        <x-sections.newsletter />
    </main>

@endsection
