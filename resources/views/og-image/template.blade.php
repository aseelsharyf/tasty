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
<body>
    <div class="relative w-[1200px] h-[630px] overflow-hidden">
        @if($post->featured_image_url)
            <img
                src="{{ $post->featured_image_url }}"
                alt=""
                class="w-full h-full object-cover grayscale"
                style="object-position: {{ ($post->featured_image_anchor['x'] ?? 50) }}% {{ ($post->featured_image_anchor['y'] ?? 0) }}%;"
            >
        @endif
        {{-- Rotated gradient rectangle matching Figma: 1030x901, rotated 146.67deg --}}
        <div class="absolute flex items-center justify-center" style="left: 556.67px; top: 73.79px; width: 1029.70px; height: 900.70px;">
            <div style="width: 1029.70px; height: 900.70px; transform: rotate(146.67deg); background: linear-gradient(175deg, rgba(255,230,66,0.881) 19%, rgba(255,230,66,0.792) 54%, rgba(255,230,67,0.725) 58%, rgba(255,232,82,0) 80%);"></div>
        </div>
        <img src="{{ asset('images/tasty-logo-black.png') }}" alt="Tasty" class="absolute bottom-10 right-10 h-14">
    </div>
</body>
</html>