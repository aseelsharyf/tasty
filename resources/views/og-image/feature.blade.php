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
            flex-direction: column;
        }
        .image-section {
            position: relative;
            width: 100%;
            height: 70%;
        }
        .featured-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: {{ ($post->featured_image_anchor['x'] ?? 50) }}% {{ ($post->featured_image_anchor['y'] ?? 0) }}%;
        }
        .gradient-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60%;
            background: linear-gradient(to top, #ffe762 0%, #ffe76280 50%, transparent 100%);
        }
        .content-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0 80px;
            text-align: center;
            gap: 12px;
        }
        .category {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #1b1b1b;
        }
        .title {
            font-size: {{ $titleSize ?? 36 }}px;
            font-weight: bold;
            color: #1b1b1b;
            line-height: 1.1;
        }
        .logo {
            position: absolute;
            bottom: 30px;
            left: 40px;
            height: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="image-section">
            @if($post->featured_image_url)
                <img src="{{ $post->featured_image_url }}" alt="" class="featured-image">
            @endif
            <div class="gradient-overlay"></div>
        </div>
        <div class="content-section">
            @if($category = $post->categories->first())
                <div class="category">{{ $category->name }}</div>
            @endif
            <h1 class="title">{{ $post->title }}</h1>
        </div>
        <img src="{{ asset('images/tasty-logo-black.png') }}" alt="Tasty" class="logo">
    </div>
</body>
</html>
