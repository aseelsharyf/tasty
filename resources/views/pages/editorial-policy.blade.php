@extends('layouts.app')

@section('content')

<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-32 bg-tasty-off-white">
    <div class="max-w-3xl mx-auto">
        <div class="flex flex-col gap-10">
            <div class="flex flex-col gap-6">
                <x-ui.heading
                    level="h1"
                    text="Editorial Policy"
                    align="center"
                />
                <p class="text-body-lg text-tasty-blue-black text-center">
                    Our commitment to honest, thoughtful food journalism.
                </p>
            </div>

            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="Our Mission"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        Tasty exists to document, celebrate, and critically examine the food culture of the Maldives. We aim to tell stories that matter, highlight voices that deserve attention, and provide honest assessments that serve our readers.
                    </p>
                </div>

                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="Independence"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        Our editorial content is independent from our advertising. We never accept payment for coverage, and our reviews are never influenced by advertisers. When we write about a restaurant, product, or establishment, our opinion is our own.
                    </p>
                </div>

                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="Reviews & Ratings"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        All restaurant reviews are conducted anonymously. We pay for our own meals and visit establishments multiple times before publishing a review. Our ratings reflect the overall dining experience, including food quality, service, atmosphere, and value.
                    </p>
                    <p class="text-body-md text-tasty-blue-black">
                        We believe in constructive criticism. When we identify areas for improvement, we aim to be specific, fair, and respectful. Our goal is to help readers make informed decisions and help establishments improve.
                    </p>
                </div>

                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="Sponsored Content"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        When content is sponsored or created in partnership with a brand, we clearly label it as such. Sponsored content is always marked with a "Sponsored" or "Partner Content" label. These pieces are held to the same editorial standards as our independent content but are paid for by the partner.
                    </p>
                </div>

                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="Corrections"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        We take accuracy seriously. If we make an error, we correct it promptly and transparently. Corrections are noted at the bottom of articles with the date and nature of the correction.
                    </p>
                    <p class="text-body-md text-tasty-blue-black">
                        If you spot an error in our coverage, please contact us at <a href="mailto:editor@tasty.mv" class="underline hover:opacity-70">editor@tasty.mv</a>.
                    </p>
                </div>

                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="Diversity & Inclusion"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        We are committed to representing the full diversity of Maldivian food culture. This means covering not just Malé-based establishments but food stories from across the atolls. It means highlighting home cooks alongside professional chefs, traditional recipes alongside modern innovations.
                    </p>
                </div>

                <div class="flex flex-col gap-4">
                    <x-ui.heading
                        level="h3"
                        text="Contributor Guidelines"
                    />
                    <p class="text-body-md text-tasty-blue-black">
                        All contributors must disclose any potential conflicts of interest. Writers cannot review establishments they have a personal or financial relationship with. All contributors are expected to adhere to these editorial standards.
                    </p>
                </div>

                <div class="flex flex-col gap-4 pt-6 border-t border-gray-200">
                    <p class="text-body-sm text-tasty-blue-black">
                        Last updated: January 2025
                    </p>
                    <p class="text-body-sm text-tasty-blue-black">
                        Questions about our editorial policy? Contact <a href="mailto:editor@tasty.mv" class="underline hover:opacity-70">editor@tasty.mv</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
