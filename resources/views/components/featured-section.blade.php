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
<div class="w-full overflow-x-hidden">

    {{-- Container with Image Background and Curved Content Overlay --}}
    <div class="w-full mx-auto flex justify-center relative">
        <style>
            .featured-section-container {
                height: {{ $imageHeightMobile }}px;
            }
            .featured-section-content {
                border-top-left-radius: {{ $radiusMobile }}px;
                border-top-right-radius: {{ $radiusMobile }}px;
            }
            @media (min-width: 768px) {
                .featured-section-container {
                    height: {{ $imageHeightTablet }}px;
                }
                .featured-section-content {
                    border-top-left-radius: {{ $radiusTablet }}px;
                    border-top-right-radius: {{ $radiusTablet }}px;
                }
            }
            @media (min-width: 1024px) {
                .featured-section-container {
                    height: {{ $imageHeightDesktop }}px;
                }
                .featured-section-content {
                    border-top-left-radius: {{ $radiusDesktop }}px;
                    border-top-right-radius: {{ $radiusDesktop }}px;
                }
            }
            @media (min-width: 1280px) {
                .featured-section-container {
                    height: {{ $imageHeightLarge }}px;
                }
                .featured-section-content {
                    border-top-left-radius: {{ $radiusLarge }}px;
                    border-top-right-radius: {{ $radiusLarge }}px;
                }
            }
        </style>

        {{-- Background Image --}}
        <div class="featured-section-container absolute inset-0 w-full bg-cover bg-center"
             style="background-image: url('{{ $image }}'); background-size: cover; background-position: center;">
        </div>

        {{-- Curved Content Overlay --}}
        <div class="featured-section-container relative w-full flex flex-col justify-end items-center">
            <div class="featured-section-content w-full {{ $contentClassColor }} px-6 sm:px-10 md:px-20 lg:px-40 xl:px-96 pt-16 sm:pt-24 md:pt-28 pb-12 sm:pb-16 flex flex-col justify-center items-center gap-4 sm:gap-6 md:gap-10" @if($contentStyleAttr) style="{{ $contentStyleAttr }}" @endif>
                <!-- Header Section -->
                @if($heading || $subheading)
                    <div class="self-stretch flex flex-col justify-center items-center gap-4">
                        @if($heading)
                            <div class="w-full text-center text-tasty-blue-black text-[60px] md:text-[200px] font-normal font-serif uppercase leading-[50px] md:leading-[160px]">
                                {{ $heading }}
                            </div>
                        @endif

                        @if($subheading)
                            <div class="w-full text-center text-tasty-blue-black text-[32px] md:text-[64px] font-normal font-serif leading-[32px] md:leading-[66px]">
                                {{ $subheading }}
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Tags Section -->
                @if($tag1 || $tag2)
                    <div class="flex justify-center">
                        <x-post.metadata-badge
                            :category="$tag1"
                            :categoryUrl="$tag1Url"
                            :tag="$tag2"
                            :tagUrl="$tag2Url"
                            bgColor="bg-transparent"
                            textSize="text-xs md:text-sm"
                            padding="px-0 py-0"
                            gap="gap-3 md:gap-5"
                        />
                    </div>
                @endif

                <!-- Description Section -->
                @if($description)
                    <div class="w-full max-w-4xl mx-auto">
                        <x-post.description
                            :description="$description"
                            size="lg"
                            align="center"
                            color="text-tasty-blue-black"
                        />
                    </div>
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
