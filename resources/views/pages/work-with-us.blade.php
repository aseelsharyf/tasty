@extends('layouts.app')

@section('content')

<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-32 bg-tasty-off-white">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col gap-10">
            <div class="flex flex-col gap-6">
                <x-ui.heading
                    level="h1"
                    text="Work With Us"
                    align="center"
                />
                <p class="text-body-lg text-tasty-blue-black text-center">
                    Join our team of food enthusiasts, writers, and creators.
                </p>
            </div>

            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="Who We're Looking For"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        We're always on the lookout for talented individuals who share our passion for Maldivian food culture. Whether you're a seasoned food writer, an aspiring photographer, or someone with unique culinary expertise, we'd love to hear from you.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl p-6 flex flex-col gap-4">
                        <div class="w-12 h-12 bg-tasty-yellow rounded-full flex items-center justify-center">
                            <i class="fas fa-pen text-tasty-blue-black"></i>
                        </div>
                        <x-ui.heading
                            level="h4"
                            text="Writers"
                        />
                        <p class="text-body-md text-tasty-blue-black">
                            We need storytellers who can capture the essence of a dish, the personality of a chef, or the history behind an ingredient. Experience in food writing preferred but not required.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-6 flex flex-col gap-4">
                        <div class="w-12 h-12 bg-tasty-yellow rounded-full flex items-center justify-center">
                            <i class="fas fa-camera text-tasty-blue-black"></i>
                        </div>
                        <x-ui.heading
                            level="h4"
                            text="Photographers"
                        />
                        <p class="text-body-md text-tasty-blue-black">
                            Food photography that makes people hungry. We're looking for photographers who can capture the beauty, texture, and story of Maldivian cuisine.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-6 flex flex-col gap-4">
                        <div class="w-12 h-12 bg-tasty-yellow rounded-full flex items-center justify-center">
                            <i class="fas fa-video text-tasty-blue-black"></i>
                        </div>
                        <x-ui.heading
                            level="h4"
                            text="Video Creators"
                        />
                        <p class="text-body-md text-tasty-blue-black">
                            From recipe videos to chef profiles, we're expanding our video content. Experience with food videography or documentary-style content is a plus.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-6 flex flex-col gap-4">
                        <div class="w-12 h-12 bg-tasty-yellow rounded-full flex items-center justify-center">
                            <i class="fas fa-map-marker-alt text-tasty-blue-black"></i>
                        </div>
                        <x-ui.heading
                            level="h4"
                            text="Regional Contributors"
                        />
                        <p class="text-body-md text-tasty-blue-black">
                            Based outside Malé? We need voices from across the atolls to cover local food scenes, traditional recipes, and regional specialties.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="What We Offer"
                    />
                    <ul class="text-body-md text-tasty-blue-black space-y-3">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-tasty-yellow mt-1"></i>
                            <span>Competitive compensation for accepted work</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-tasty-yellow mt-1"></i>
                            <span>Byline credit and portfolio building opportunities</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-tasty-yellow mt-1"></i>
                            <span>Editorial support and feedback to help you grow</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-tasty-yellow mt-1"></i>
                            <span>Access to food events, restaurant openings, and industry connections</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-check text-tasty-yellow mt-1"></i>
                            <span>Flexible, remote-friendly working arrangements</span>
                        </li>
                    </ul>
                </div>

                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="How to Apply"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        Send us an email with:
                    </p>
                    <ul class="text-body-md text-tasty-blue-black space-y-2 list-disc list-inside">
                        <li>A brief introduction about yourself</li>
                        <li>The role you're interested in</li>
                        <li>2-3 relevant work samples (links or attachments)</li>
                        <li>Why you want to work with Tasty</li>
                    </ul>
                    <div class="flex gap-4 pt-4">
                        <x-ui.button
                            url="mailto:careers@tasty.mv"
                            text="Apply Now"
                            icon="arrow-right"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
