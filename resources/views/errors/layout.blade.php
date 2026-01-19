<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

        <title>@yield('title') - {{ config('app.name', 'Tasty') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://use.typekit.net/ekg0nxf.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css'])
        @endif
    </head>
    <body class="antialiased bg-off-white">
        <div class="min-h-screen flex flex-col">
            {{-- Error Content --}}
            <main class="flex-1 flex items-center justify-center px-6 py-16">
                <div class="text-center max-w-2xl mx-auto">
                    {{-- Error Code --}}
                    <div class="mb-6">
                        <span class="text-h1-hero text-blue-black">@yield('code')</span>
                    </div>

                    {{-- Error Title --}}
                    <h1 class="text-h2 text-blue-black mb-4">
                        @yield('title')
                    </h1>

                    {{-- Error Message --}}
                    <p class="text-body-md text-blue-black/70 mb-10">
                        @yield('message')
                    </p>

                    {{-- Action Button --}}
                    <a href="{{ url('/') }}" class="inline-flex items-center justify-center px-8 py-4 bg-blue-black text-white rounded-full text-body-md font-medium hover:opacity-80 transition-all">
                        Go Home
                    </a>
                </div>
            </main>

            {{-- Simple Footer --}}
            <footer class="w-full py-6 px-6 lg:px-10 text-center">
                <p class="text-body-sm text-blue-black/50">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Tasty') }}. All rights reserved.
                </p>
            </footer>
        </div>
    </body>
</html>
