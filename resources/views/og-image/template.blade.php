<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { width: 1200px; height: 630px; overflow: hidden; }
    </style>
</head>
<body class="relative">
    @if($post->featured_image_url)
        <img
            src="{{ $post->featured_image_url }}"
            alt=""
            class="w-full h-full object-cover"
            style="object-position: {{ ($post->featured_image_anchor['x'] ?? 50) }}% {{ ($post->featured_image_anchor['y'] ?? 0) }}%;"
        >
    @endif
    <div
        class="absolute inset-0 pointer-events-none"
        style="background: radial-gradient(circle at 0% 100%, rgba(255,231,98,0.92) 0%, rgba(255,231,98,0.7) 12%, rgba(255,231,98,0.35) 24%, transparent 42%);"
    ></div>
    <img src="{{ asset('images/tasty-logo-black.png') }}" alt="Tasty" class="absolute bottom-10 left-10 h-14">
</body>
</html>