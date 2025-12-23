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
            mobileLayout="grid"
            action="recent"
            :count="4"
        />

        {{-- Featured Video Section (Dynamic) --}}
        <x-sections.featured-video
            :postId="1"
            buttonText="Watch"
            overlayColor="#FFE762"
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
