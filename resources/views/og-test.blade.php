<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OG Image Preview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://use.typekit.net/ekg0nxf.css">
    <style>
        .og-image-container {
            aspect-ratio: 1200 / 630;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">OG Image Preview</h1>
        <p class="text-gray-500 mb-8">Click any card to view full size. Use <code class="bg-gray-200 px-1 rounded">/og-html/{slug}</code> for screenshot services.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($posts as $post)
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">{{ $post->kicker ?: $post->categories->first()?->name }}</p>
                        <h3 class="font-bold text-gray-900 mt-1" style="font-family: new-spirit-condensed, new-spirit, Georgia, serif;">{{ $post->title }}</h3>
                    </div>
                    <div class="og-image-container bg-gray-200 relative overflow-hidden">
                        <iframe
                            src="{{ route('og.html', $post) }}"
                            class="w-[1200px] h-[630px] origin-top-left pointer-events-none"
                            style="transform: scale(0.5); width: 1200px; height: 630px;"
                            loading="lazy"
                        ></iframe>
                    </div>
                    <div class="p-3 border-t border-gray-100 flex gap-2">
                        <a href="{{ route('og.html', $post) }}" target="_blank" class="text-xs px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-gray-600">HTML</a>
                        <a href="{{ route('og.preview', $post) }}" target="_blank" class="text-xs px-2 py-1 bg-blue-100 hover:bg-blue-200 rounded text-blue-600">PHP Image</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
