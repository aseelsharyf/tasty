{{-- resources/views/components/featured-section.blade.php --}}

@props([
    'image',
    'imageAlt' => 'Featured section image',
    'heading' => '',
    'subheading' => '',
    'tag1' => '',
    'tag1Url' => '#',
    'tag2' => '',
    'tag2Url' => '#',
    'description' => '',
    'buttonText' => 'Read More',
    'buttonUrl' => '#',
    'imageBgColor' => 'bg-tasty-yellow', // Background color for image gradient (Tailwind color or hex)
    'contentBgColor' => 'bg-white', // Background color for content section (Tailwind color or hex)
    'imageHeightMobile' => 750,
    'imageHeightTablet' => 900,
    'imageHeightDesktop' => 1058,
    'imageHeightLarge' => 1058
])

@php
    /**
     * Parse color to RGB string for gradients
     * Supports: hex colors (#fff, #ffffff), rgb(), rgba(), Tailwind classes, CSS variables
     */
    function parseColorToRgbSection($color) {
        // Handle hex colors
        if (str_starts_with($color, '#')) {
            $hex = ltrim($color, '#');
            // Support 3-digit hex
            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return "{$r}, {$g}, {$b}";
        }

        // Handle rgb() or rgba()
        if (preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+)/', $color, $matches)) {
            return "{$matches[1]}, {$matches[2]}, {$matches[3]}";
        }

        // Map common Tailwind bg- classes to CSS custom properties
        $tailwindColorMap = [
            'bg-tasty-yellow' => '--color-tasty-yellow',
            'bg-tasty-off-white' => '--color-tasty-off-white',
            'bg-tasty-blue-black' => '--color-tasty-blue-black',
            'bg-tasty-pure-white' => '--color-tasty-pure-white',
            'bg-tasty-light-gray' => '--color-tasty-light-gray',
            'bg-tasty-black' => '--color-tasty-black',
            'bg-white' => '--color-tasty-pure-white',
        ];

        // Check if we have a mapping to CSS variable
        if (isset($tailwindColorMap[$color])) {
            // Read from app.css theme variables
            $cssVarMap = [
                '--color-tasty-pure-white' => '#ffffff',
                '--color-tasty-off-white' => '#f5f5f5',
                '--color-tasty-light-gray' => '#f2f2f2',
                '--color-tasty-yellow' => '#ffe762',
                '--color-tasty-black' => '#000000',
                '--color-tasty-blue-black' => '#0a0924',
            ];

            $hexColor = $cssVarMap[$tailwindColorMap[$color]] ?? null;
            if ($hexColor) {
                return parseColorToRgbSection($hexColor);
            }
        }

        // Fallback to tasty-yellow
        return '255, 231, 98';
    }

    // Determine if colors are hex or Tailwind class
    $isImageHexColor = str_starts_with($imageBgColor, '#');
    $imageStyleAttr = $isImageHexColor ? "background-color: {$imageBgColor};" : '';
    $imageClassColor = !$isImageHexColor ? $imageBgColor : '';

    $isContentHexColor = str_starts_with($contentBgColor, '#');
    $contentStyleAttr = $isContentHexColor ? "background-color: {$contentBgColor};" : '';
    $contentClassColor = !$isContentHexColor ? $contentBgColor : '';

    // Parse color to RGB for gradient
    $gradientRgb = parseColorToRgbSection($imageBgColor);

    // Calculate border-radius (half of height for perfect curve)
    $radiusMobile = $imageHeightMobile / 2;
    $radiusTablet = $imageHeightTablet / 2;
    $radiusDesktop = $imageHeightDesktop / 2;
    $radiusLarge = $imageHeightLarge / 2;
@endphp

{{-- Featured Section --}}
{{-- Based on Figma Frame 117 (node 158:1028) --}}
{{-- Mobile: Container 750px, Content pt-96px pb-64px px-40px gap-24px --}}
{{-- Desktop: Container 1058px, Content h-735px pt-110px px-362px gap-40px --}}
<div class="w-full overflow-x-hidden">

    {{-- Container with Image Background and Curved Content Overlay --}}
    <div class="w-full mx-auto flex justify-center relative h-[750px] md:h-[1058px]">

        {{-- Background Image --}}
        <div class="absolute inset-0 w-full">
            @if($image)
                <img
                    src="{{ $image }}"
                    alt="{{ $imageAlt }}"
                    class="w-full h-full object-cover object-center"
                />
            @else
                <div class="w-full h-full bg-gray-300"></div>
            @endif
        </div>

        {{-- Curved Content Overlay --}}
        <div class="relative w-full h-full flex flex-col justify-end items-center">
            <div class="w-full {{ $contentClassColor }} rounded-t-[2000px] md:rounded-t-[5000px] pt-[96px] pb-[64px] px-[40px] md:h-[735px] md:pt-[110px] md:pb-0 md:px-[100px] lg:px-[200px] xl:px-[362px] flex flex-col justify-center items-center gap-6 md:gap-10" @if($contentStyleAttr) style="{{ $contentStyleAttr }}" @endif>
                <!-- Header Section -->
                @if($heading || $subheading)
                    <div class="self-stretch flex flex-col justify-center items-center gap-3 md:gap-4">
                        @if($heading)
                            <x-ui.heading
                                level="h1"
                                variant="hero"
                                :text="$heading"
                                align="center"
                            />
                        @endif

                        @if($subheading)
                            <x-ui.heading
                                level="h2"
                                :text="$subheading"
                                align="center"
                            />
                        @endif
                    </div>
                @endif

                <!-- Tags Section -->
                @if($tag1 || $tag2)
                    <div class="flex items-center justify-center gap-4 md:gap-5 uppercase">
                        <x-ui.text variant="sm">
                            @if($tag1)
                                <a href="{{ $tag1Url }}" class="hover:opacity-70 transition-opacity">{{ $tag1 }}</a>
                            @endif
                            @if($tag1 && $tag2)
                                <span class="mx-2 md:mx-3">•</span>
                            @endif
                            @if($tag2)
                                <a href="{{ $tag2Url }}" class="hover:opacity-70 transition-opacity">{{ $tag2 }}</a>
                            @endif
                        </x-ui.text>
                    </div>
                @endif

                <!-- Description Section -->
                @if($description)
                    <x-ui.text
                        variant="lg"
                        :text="$description"
                        align="center"
                    />
                @endif

                <!-- Button Section -->
                @if($buttonText)
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
                @endif
            </div>
        </div>
    </div>

</div>
