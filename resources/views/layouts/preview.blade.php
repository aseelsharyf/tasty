<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">

        <title>Preview - {{ config('app.name', 'Tasty') }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

        <!-- Fonts -->
        <link rel="stylesheet" href="https://use.typekit.net/ekg0nxf.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Space+Mono:ital,wght@0,400;0,700;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=DM+Mono:ital,wght@0,300;0,400;0,500;1,300;1,400;1,500&family=Playfair+Display+SC:ital,wght@0,400;0,700;0,900;1,400;1,700;1,900&family=Work+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

        <!-- Styles / Scripts -->
        <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.13.3/dist/cdn.min.js"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="antialiased">
        <div class="w-full bg-off-white">
            {{-- Minimal Preview Header --}}
            <div class="fixed top-0 left-0 right-0 z-50 flex justify-center pt-6 px-4 md:px-10 pointer-events-none">
                <div class="pointer-events-auto w-full max-w-[1360px] h-[72px] bg-white/70 backdrop-blur-md rounded-xl border border-white/20 shadow-lg flex items-center justify-between px-6">
                    <div class="flex items-center gap-4">
                        <x-layout.logo />
                        <span class="text-sm text-muted font-mono uppercase tracking-wide">Preview Mode</span>
                    </div>
                    <button onclick="window.close()" class="text-sm font-mono uppercase tracking-wide hover:text-yellow-600 transition">
                        Close Preview
                    </button>
                </div>
            </div>

            <main>
                @yield('content')
            </main>

            {{-- Minimal Footer --}}
            <footer class="bg-tasty-blue-black text-white py-8">
                <div class="container mx-auto px-4 text-center">
                    <p class="text-sm text-white/60">This is a preview. Content may not be published yet.</p>
                </div>
            </footer>
        </div>

        @stack('scripts')
    </body>
</html>
