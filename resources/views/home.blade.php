@extends('layouts.app')

@section('content')

<main class="flex flex-col flex-1">
    @foreach($sections as $section)
        @switch($section['type'])
            @case('hero')
                <x-sections.hero
                    :alignment="$section['data']['alignment'] ?? 'center'"
                    :action="$section['data']['action'] ?? 'recent'"
                    :params="$section['data']['params'] ?? []"
                    :postId="$section['data']['postId'] ?? null"
                    :bgColor="$section['data']['bgColor'] ?? 'yellow'"
                    :buttonText="$section['data']['buttonText'] ?? 'Read More'"
                    :buttonColor="$section['data']['buttonColor'] ?? 'white'"
                />
                @break

            @case('latest-updates')
                <x-sections.latest-updates
                    :introImage="$section['data']['introImage'] ?? ''"
                    :introImageAlt="$section['data']['introImageAlt'] ?? 'Latest Updates'"
                    :titleSmall="$section['data']['titleSmall'] ?? 'Latest'"
                    :titleLarge="$section['data']['titleLarge'] ?? 'Updates'"
                    :description="$section['data']['description'] ?? ''"
                    :buttonText="$section['data']['buttonText'] ?? 'More Updates'"
                    :showLoadMore="$section['data']['showLoadMore'] ?? true"
                    :action="$section['data']['action'] ?? 'recent'"
                    :params="$section['data']['params'] ?? []"
                    :totalSlots="$section['data']['totalSlots'] ?? 0"
                    :manualPostIds="$section['data']['manualPostIds'] ?? []"
                    :staticContent="$section['data']['staticContent'] ?? []"
                    :dynamicCount="$section['data']['dynamicCount'] ?? 0"
                />
                @break

            @case('spread')
                <x-sections.spread
                    :showIntro="$section['data']['showIntro'] ?? false"
                    :introImage="$section['data']['introImage'] ?? ''"
                    :introImageAlt="$section['data']['introImageAlt'] ?? 'The Spread'"
                    :titleSmall="$section['data']['titleSmall'] ?? 'The'"
                    :titleLarge="$section['data']['titleLarge'] ?? 'SPREAD'"
                    :description="$section['data']['description'] ?? ''"
                    :bgColor="$section['data']['bgColor'] ?? 'yellow'"
                    :showDividers="$section['data']['showDividers'] ?? true"
                    :dividerColor="$section['data']['dividerColor'] ?? 'white'"
                    :mobileLayout="$section['data']['mobileLayout'] ?? 'scroll'"
                    :action="$section['data']['action'] ?? 'recent'"
                    :params="$section['data']['params'] ?? []"
                    :totalSlots="$section['data']['totalSlots'] ?? 0"
                    :manualPostIds="$section['data']['manualPostIds'] ?? []"
                    :staticContent="$section['data']['staticContent'] ?? []"
                    :dynamicCount="$section['data']['dynamicCount'] ?? 0"
                />
                @break

            @case('review')
                <x-sections.review
                    :showIntro="$section['data']['showIntro'] ?? true"
                    :introImage="$section['data']['introImage'] ?? ''"
                    :introImageAlt="$section['data']['introImageAlt'] ?? 'On the Menu'"
                    :titleSmall="$section['data']['titleSmall'] ?? 'On the'"
                    :titleLarge="$section['data']['titleLarge'] ?? 'Menu'"
                    :description="$section['data']['description'] ?? ''"
                    :showDividers="$section['data']['showDividers'] ?? true"
                    :dividerColor="$section['data']['dividerColor'] ?? 'white'"
                    :mobileLayout="$section['data']['mobileLayout'] ?? 'scroll'"
                    :buttonText="$section['data']['buttonText'] ?? 'More Reviews'"
                    :showLoadMore="$section['data']['showLoadMore'] ?? true"
                    :action="$section['data']['action'] ?? 'recent'"
                    :params="$section['data']['params'] ?? []"
                    :totalSlots="$section['data']['totalSlots'] ?? 0"
                    :manualPostIds="$section['data']['manualPostIds'] ?? []"
                    :staticContent="$section['data']['staticContent'] ?? []"
                    :dynamicCount="$section['data']['dynamicCount'] ?? 0"
                />
                @break

            @case('recipe')
                <x-sections.recipe
                    :showIntro="$section['data']['showIntro'] ?? true"
                    :introImage="$section['data']['introImage'] ?? ''"
                    :introImageAlt="$section['data']['introImageAlt'] ?? 'Everyday Cooking'"
                    :titleSmall="$section['data']['titleSmall'] ?? 'Everyday'"
                    :titleLarge="$section['data']['titleLarge'] ?? 'COOKING'"
                    :description="$section['data']['description'] ?? ''"
                    :bgColor="$section['data']['bgColor'] ?? 'yellow'"
                    :gradient="$section['data']['gradient'] ?? 'top'"
                    :mobileLayout="$section['data']['mobileLayout'] ?? 'grid'"
                    :showDividers="$section['data']['showDividers'] ?? false"
                    :dividerColor="$section['data']['dividerColor'] ?? 'white'"
                    :action="$section['data']['action'] ?? 'recent'"
                    :params="$section['data']['params'] ?? []"
                    :totalSlots="$section['data']['totalSlots'] ?? 0"
                    :manualPostIds="$section['data']['manualPostIds'] ?? []"
                    :staticContent="$section['data']['staticContent'] ?? []"
                    :dynamicCount="$section['data']['dynamicCount'] ?? 0"
                />
                @break

            @case('featured-person')
                <x-sections.featured-person
                    :postId="$section['data']['postId'] ?? null"
                    :action="$section['data']['action'] ?? 'recent'"
                    :params="$section['data']['params'] ?? []"
                    :tag1="$section['data']['tag1'] ?? 'TASTY FEATURE'"
                    :buttonText="$section['data']['buttonText'] ?? 'Read More'"
                    :bgColor="$section['data']['bgColor'] ?? 'yellow'"
                />
                @break

            @case('featured-video')
                <x-sections.featured-video
                    :postId="$section['data']['postId'] ?? null"
                    :action="$section['data']['action'] ?? 'recent'"
                    :params="$section['data']['params'] ?? []"
                    :buttonText="$section['data']['buttonText'] ?? 'Watch'"
                    :overlayColor="$section['data']['overlayColor'] ?? '#FFE762'"
                />
                @break

            @case('featured-location')
                <x-sections.featured-location
                    :postId="$section['data']['postId'] ?? null"
                    :action="$section['data']['action'] ?? 'recent'"
                    :params="$section['data']['params'] ?? []"
                    :tag1="$section['data']['tag1'] ?? 'TASTY FEATURE'"
                    :bgColor="$section['data']['bgColor'] ?? 'yellow'"
                />
                @break

            @case('newsletter')
                <x-sections.newsletter
                    :title="$section['data']['title'] ?? 'COME HUNGRY, LEAVE INSPIRED. SIGN UP FOR TASTY UPDATES.'"
                    :placeholder="$section['data']['placeholder'] ?? 'Enter your Email'"
                    :buttonText="$section['data']['buttonText'] ?? 'SUBSCRIBE'"
                    :bgColor="$section['data']['bgColor'] ?? '#F3F4F6'"
                />
                @break

            @case('add-to-cart')
                <x-sections.add-to-cart
                    :title="$section['data']['title'] ?? 'ADD TO CART'"
                    :description="$section['data']['description'] ?? 'Ingredients, tools, and staples we actually use.'"
                    :bgColor="$section['data']['bgColor'] ?? 'white'"
                    :products="$section['data']['products'] ?? []"
                />
                @break
        @endswitch
    @endforeach
</main>

@endsection
