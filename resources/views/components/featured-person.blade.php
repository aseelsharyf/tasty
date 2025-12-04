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
    'imageHeightMobile' => 294,
    'imageHeightTablet' => 800,
    'imageHeightDesktop' => 1126,
    'imageHeightLarge' => 1300
])

@php
    /**
     * Parse color to RGB string for gradients
     * Supports: hex colors (#fff, #ffffff), rgb(), rgba(), Tailwind classes, CSS variables
     */
    function parseColorToRgb($color) {
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

        // Try to resolve Tailwind/CSS custom property via getComputedStyle
        // This reads from the actual rendered CSS variables in app.css
        $cssVarName = str_replace('bg-', '--color-', $color);

        // Map common Tailwind bg- classes to CSS custom properties
        $tailwindColorMap = [
            'bg-tasty-yellow' => '--color-tasty-yellow',
            'bg-tasty-off-white' => '--color-tasty-off-white',
            'bg-tasty-blue-black' => '--color-tasty-blue-black',
            'bg-tasty-pure-white' => '--color-tasty-pure-white',
            'bg-tasty-light-gray' => '--color-tasty-light-gray',
            'bg-tasty-black' => '--color-tasty-black',
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
                return parseColorToRgb($hexColor);
            }
        }

        // Fallback to tasty-yellow
        return '255, 231, 98';
    }

    // Determine if bgColor is a hex color or Tailwind class
    $isHexColor = str_starts_with($bgColor, '#');
    $styleAttr = $isHexColor ? "background-color: {$bgColor};" : '';
    $classColor = !$isHexColor ? $bgColor : '';

    // Parse color to RGB
    $gradientRgb = parseColorToRgb($bgColor);

    // Calculate border-radius (half of height for perfect curve)
    $radiusMobile = $imageHeightMobile / 2;
    $radiusTablet = $imageHeightTablet / 2;
    $radiusDesktop = $imageHeightDesktop / 2;
    $radiusLarge = $imageHeightLarge / 2;
@endphp

{{-- Featured Person Section --}}
<div class="w-full pt-16 overflow-x-hidden">

    {{-- Profile Image with Gradient Overlay --}}
    <div class="w-full mx-auto flex justify-center">
        <style>
            .featured-person-image {
                height: {{ $imageHeightMobile }}px;
                border-top-left-radius: {{ $radiusMobile }}px;
                border-top-right-radius: {{ $radiusMobile }}px;
            }
            @media (min-width: 768px) {
                .featured-person-image {
                    height: {{ $imageHeightTablet }}px;
                    border-top-left-radius: {{ $radiusTablet }}px;
                    border-top-right-radius: {{ $radiusTablet }}px;
                }
            }
            @media (min-width: 1024px) {
                .featured-person-image {
                    height: {{ $imageHeightDesktop }}px;
                    border-top-left-radius: {{ $radiusDesktop }}px;
                    border-top-right-radius: {{ $radiusDesktop }}px;
                }
            }
            @media (min-width: 1280px) {
                .featured-person-image {
                    height: {{ $imageHeightLarge }}px;
                    border-top-left-radius: {{ $radiusLarge }}px;
                    border-top-right-radius: {{ $radiusLarge }}px;
                }
            }
        </style>
        <div class="featured-person-image relative flex flex-col justify-center items-center w-full overflow-hidden bg-cover bg-center"
             style="background: linear-gradient(180deg, rgba({{ $gradientRgb }}, 0) 60%, rgba({{ $gradientRgb }}, 0.5) 80%, rgba({{ $gradientRgb }}, 1) 100%), url('{{ $image }}'); background-size: cover; background-position: center;">
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
                        <x-ui.heading
                            level="h1"
                            :text="$name"
                            align="center"
                        />
                    </div>
                    <div class="flex justify-center">
                        <x-ui.heading
                            level="h2"
                            :text="$title"
                            align="center"
                        />
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
