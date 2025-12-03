{{-- resources/views/components/featured-person.blade.php --}}

@props([
    'image',
    'imageAlt' => 'Featured person image',
    'name' => 'Aminath Hameed',
    'title' => 'Chef and Owner of Maldivian Patisserie.',
    'tag1' => 'tasty feature',
    'tag2' => 'people',
    'description' => 'Two weeks in Lanka, documenting dishes and cooks who give the island its food identity.',
    'buttonText' => 'Read More',
    'buttonUrl' => '#',
    'bgColor' => 'bg-tasty-yellow', // Background color for bottom section (Tailwind color or hex)
    'imageHeightMobile' => 'h-[500px]',
    'imageHeightTablet' => 'md:h-[800px]',
    'imageHeightDesktop' => 'lg:h-[1200px]',
    'imageHeightLarge' => 'xl:h-[1300px]'
])

@php
    // Color mapping for Tailwind and custom colors
    $colorMap = [
        // Custom tasty colors
        'bg-tasty-yellow' => '255, 214, 0',
        'bg-tasty-off-white' => '245, 245, 245',
        'bg-tasty-blue-black' => '10, 14, 39',

        // Tailwind reds
        'bg-red-50' => '254, 242, 242',
        'bg-red-100' => '254, 226, 226',
        'bg-red-200' => '254, 202, 202',
        'bg-red-300' => '252, 165, 165',
        'bg-red-400' => '248, 113, 113',
        'bg-red-500' => '239, 68, 68',
        'bg-red-600' => '220, 38, 38',

        // Tailwind yellows
        'bg-yellow-50' => '254, 252, 232',
        'bg-yellow-100' => '254, 249, 195',
        'bg-yellow-200' => '254, 240, 138',
        'bg-yellow-300' => '253, 224, 71',
        'bg-yellow-400' => '250, 204, 21',
        'bg-yellow-500' => '234, 179, 8',

        // Tailwind blues
        'bg-blue-50' => '239, 246, 255',
        'bg-blue-100' => '219, 234, 254',
        'bg-blue-500' => '59, 130, 246',
        'bg-blue-600' => '37, 99, 235',

        // Tailwind greens
        'bg-green-50' => '240, 253, 244',
        'bg-green-100' => '220, 252, 231',
        'bg-green-500' => '34, 197, 94',
        'bg-green-600' => '22, 163, 74',
    ];

    // Determine if bgColor is a hex color or Tailwind class
    $isHexColor = str_starts_with($bgColor, '#');
    $styleAttr = $isHexColor ? "background-color: {$bgColor};" : '';
    $classColor = !$isHexColor ? $bgColor : '';

    // Extract RGB for gradient
    if ($isHexColor) {
        // If hex color, extract RGB
        $hex = ltrim($bgColor, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $gradientRgb = "{$r}, {$g}, {$b}";
    } else {
        // If Tailwind class, lookup in color map
        $gradientRgb = $colorMap[$bgColor] ?? '255, 214, 0'; // Default to tasty-yellow
    }
@endphp

{{-- Featured Person Section --}}
<div class="w-full pt-16">

    {{-- Profile Image with Gradient Overlay --}}
    <div class="w-full mx-auto flex justify-center">
        <div class="relative flex flex-col justify-center items-center w-full {{ $imageHeightMobile }} {{ $imageHeightTablet }} {{ $imageHeightDesktop }} {{ $imageHeightLarge }} rounded-t-[2000px] md:rounded-t-[5000px] overflow-hidden bg-cover bg-center" style="background: linear-gradient(180deg, rgba({{ $gradientRgb }}, 0) 60%, rgba({{ $gradientRgb }}, 0.5) 80%, rgba({{ $gradientRgb }}, 1) 100%), url('{{ $image }}'); background-size: cover; background-position: center;">
            {{-- Content (can be extended if needed) --}}
            <div class="relative z-10">
                {{-- Your content here --}}
            </div>
        </div>
    </div>

    {{-- Profile Info Section --}}
    <div class="w-full {{ $classColor }} py-8 md:py-12 -mt-1" @if($styleAttr) style="{{ $styleAttr }}" @endif>
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col space-y-8 md:space-y-10">
                <!-- Header Section -->
                <div class="flex flex-col space-y-4">
                    <div class="flex justify-center">
                        <h1 class="font-serif text-4xl md:text-6xl lg:text-8xl text-center leading-tight text-stone-900">
                            {{ $name }}
                        </h1>
                    </div>
                    <div class="flex justify-center">
                        <h2 class="font-serif text-2xl md:text-4xl lg:text-5xl text-center leading-tight text-stone-900">
                            {{ $title }}
                        </h2>
                    </div>
                </div>

                <!-- Tags Section -->
                <div class="flex justify-center">
                    <x-post.metadata-badge
                        :category="$tag1"
                        categoryUrl="#"
                        :tag="$tag2"
                        tagUrl="#"
                        bgColor="bg-transparent"
                        textSize="text-xs md:text-sm"
                        padding="px-0 py-0"
                        gap="gap-3 md:gap-5"
                    />
                </div>

                <!-- Description Section -->
                <div class="flex justify-center">
                    <div class="max-w-2xl">
                        <x-post.description
                            :description="$description"
                            size="lg"
                            align="center"
                        />
                    </div>
                </div>

                <!-- Button Section -->
                <div class="flex justify-center">
                    <x-ui.button
                        :url="$buttonUrl"
                        :text="$buttonText"
                        icon="plus"
                        :iconRotate="true"
                        bgColor="bg-white"
                        paddingSize="md"
                    />
                </div>
            </div>
        </div>
    </div>

</div>
