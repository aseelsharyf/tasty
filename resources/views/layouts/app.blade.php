<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <meta name="theme-color" content="#FFE762">

        {!! SEO::generate() !!}

        @if(isset($breadcrumbJsonLd))
            <script type="application/ld+json">{!! json_encode($breadcrumbJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endif

        <!-- Resource Hints -->
        <link rel="preconnect" href="https://use.typekit.net" crossorigin>
        <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
        <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

        <!-- Fonts -->
        <link rel="stylesheet" href="https://use.typekit.net/ekg0nxf.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

        <script defer src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

        <!-- Styles / Scripts -->
        <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.13.3/dist/cdn.min.js"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
        @endif
    </head>
    <body class="antialiased">
        <div class="w-full {{ request()->routeIs('post.show', 'cms.posts.preview', 'cms.api.preview.post') ? 'bg-off-white' : 'bg-gray-100' }}">
            <x-layout.nav-bar></x-layout.nav-bar>

            {{-- Add spacer for pages without hero sections (category/tag/author pages) --}}
            @if(request()->routeIs('category.*') || request()->routeIs('tag.*') || request()->routeIs('author.*'))
                <div class="h-[96px] md:h-[112px]"></div>
            @endif

            <main>
                @yield('content')
            </main>

            <x-layout.footer></x-layout.footer>
        </div>

        @stack('scripts')
    </body>
</html>
