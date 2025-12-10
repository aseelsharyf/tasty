<!DOCTYPE html>
<html lang="{{ $post['language_code'] ?? 'en' }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $post['title'] ?? 'Preview' }}</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: {{ $isRtl ? "'MV_Faseyha', 'Faruma', sans-serif" : "system-ui, -apple-system, sans-serif" }};
        }
        .prose { max-width: none; }
        .prose img { margin: 0; }
        .prose figure { margin: 0; }
        .preview-badge {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 50;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        @if($isRtl)
        .preview-badge { right: auto; left: 1rem; }
        @endif
    </style>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    {{-- Preview badge --}}
    <div class="preview-badge">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
        <span>{{ $templateConfig['name'] ?? 'Default' }} Template Preview</span>
    </div>

    {{-- Include the appropriate template --}}
    @include('templates.posts.layouts.' . ($template ?? 'default'), [
        'post' => $post,
        'content' => $content,
        'isRtl' => $isRtl,
    ])

    @vite(['resources/js/app.js'])
</body>
</html>
