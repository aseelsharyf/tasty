<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://use.typekit.net/ekg0nxf.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            width: 1200px;
            height: 630px;
            font-family: new-spirit-condensed, new-spirit, Georgia, serif;
            background: #ffe762;
            overflow: hidden;
        }
        .container {
            width: 100%;
            height: 100%;
            display: flex;
            padding: 40px;
            gap: 50px;
        }
        .image-section {
            width: 550px;
            height: 550px;
            flex-shrink: 0;
            border-radius: 24px;
            overflow: hidden;
        }
        .featured-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: {{ ($post->featured_image_anchor['x'] ?? 50) }}% {{ ($post->featured_image_anchor['y'] ?? 0) }}%;
        }
        .content-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 16px;
            padding-right: 20px;
        }
        .kicker {
            font-size: 24px;
            font-weight: normal;
            text-transform: uppercase;
            color: #1b1b1b;
            line-height: 1.2;
            letter-spacing: 1px;
        }
        .title {
            font-size: {{ $titleSize ?? 48 }}px;
            font-weight: normal;
            color: #1b1b1b;
            line-height: 1.05;
        }
        .logo {
            position: absolute;
            bottom: 40px;
            left: 640px; /* Aligns with text start: 40px padding + 550px image + 50px gap */
            height: 56px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="image-section">
            @if($post->featured_image_url)
                <img src="{{ $post->featured_image_url }}" alt="" class="featured-image">
            @endif
        </div>
        <div class="content-section">
            @if($post->kicker)
                <div class="kicker">{{ $post->kicker }}</div>
            @elseif($category = $post->categories->first())
                <div class="kicker">{{ $category->name }}</div>
            @endif
            <h1 class="title">{{ $post->title }}</h1>
        </div>
    </div>
    <img src="{{ asset('images/tasty-logo-black.png') }}" alt="Tasty" class="logo">
</body>
</html>
