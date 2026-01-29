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
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Space+Mono:ital,wght@0,400;0,700;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css'])
        @endif
    </head>
    <body class="antialiased">
        <div class="w-full min-h-screen flex flex-col bg-tasty-off-white">
            {{-- Header with Logo --}}
            <header class="w-full py-8 px-6 lg:px-10">
                <div class="max-w-5xl mx-auto">
                    <a href="{{ url('/') }}" class="inline-block hover:opacity-80 transition-opacity">
                        <x-layout.logo class="w-[74px] h-9 text-tasty-blue-black" />
                    </a>
                </div>
            </header>

            {{-- Error Content --}}
            <main class="flex-1 flex items-center justify-center px-5 lg:px-10 py-16 lg:py-24">
                <div class="max-w-3xl mx-auto text-center">
                    {{-- Error Code --}}
                    <div class="mb-6">
                        <span class="text-h1-hero text-tasty-blue-black">@yield('code')</span>
                    </div>

                    {{-- Error Title --}}
                    <h1 class="text-h3 lg:text-h2 text-tasty-blue-black mb-10 lg:mb-14">
                        @yield('title')
                    </h1>

                    {{-- What you can do Section --}}
                    <div class="mb-10 lg:mb-14">
                        <p class="text-body-md text-tasty-blue-black/50 mb-6">What you can do:</p>
                        <div class="flex flex-col items-center gap-4">
                            @yield('actions')
                        </div>
                    </div>

                    {{-- Closing Tagline --}}
                    <p class="text-body-md text-tasty-blue-black/40 italic">
                        @yield('tagline')
                    </p>
                </div>
            </main>

            {{-- Footer --}}
            <footer class="w-full py-10 px-6 lg:px-10 text-center border-t border-tasty-blue-black/5">
                <p class="text-xs font-medium text-tasty-blue-black/40 uppercase tracking-wider">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Tasty') }}
                </p>
            </footer>
        </div>
    </body>
</html>
