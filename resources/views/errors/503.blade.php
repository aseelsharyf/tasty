<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

        <title>Coming Soon - {{ config('app.name', 'Tasty') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://use.typekit.net/ekg0nxf.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Space+Mono:ital,wght@0,400;0,700;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css'])
        @endif
    </head>
    @php
        $labels = [
            'Something new is brewing',
            'Good things take time',
            'Almost ready',
            'Behind the scenes',
            'In the kitchen',
        ];

        $descriptions = [
            "We're putting the finishing touches on something special. Check back soon.",
            "We're crafting something delicious. It won't be long now.",
            "Our team is hard at work. Stay tuned for the big reveal.",
            "Something exciting is on its way. We can't wait to share it with you.",
            "We're preparing something wonderful. Thank you for your patience.",
        ];

        $label = $labels[array_rand($labels)];
        $description = $descriptions[array_rand($descriptions)];
    @endphp

    <body class="antialiased">
        <div class="w-full min-h-screen flex flex-col bg-tasty-off-white">
            {{-- Main Content --}}
            <main class="flex-1 flex items-center justify-center px-6">
                <div class="max-w-md text-center">
                    {{-- Subtle Label --}}
                    <p class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-[0.2em] mb-4">
                        {{ $label }}
                    </p>

                    {{-- Coming Soon Title --}}
                    <h1 class="text-h2 text-tasty-blue-black mb-8">
                        Coming Soon
                    </h1>

                    {{-- Minimal Divider --}}
                    <div class="w-16 h-px bg-tasty-blue-black/10 mx-auto mb-8"></div>

                    {{-- Description --}}
                    <p class="text-body-md text-tasty-blue-black/50 leading-relaxed">
                        {{ $description }}
                    </p>
                </div>
            </main>

            {{-- Footer --}}
            <footer class="py-8 text-center">
                <p class="text-xs text-tasty-blue-black/30">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Tasty') }}
                </p>
            </footer>
        </div>
    </body>
</html>
