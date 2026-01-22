{{-- Featured Video Section --}}
@if($title)
@php
    $sectionStyle = '';
    if ($sectionBgColor) {
        // Fixed background color takes priority
        $sectionStyle = "background: {$sectionBgColor};";
    } elseif ($showSectionGradient) {
        $gradientDirection = $sectionGradientDirection === 'bottom' ? '0deg' : '180deg';
        $sectionStyle = "background: linear-gradient({$gradientDirection}, {$overlayColor} 0%, " . $overlayColor . "80 20%, transparent 40%), white;";
    } else {
        $sectionStyle = 'background: white;';
    }

    // Determine if we have autoplay video
    $hasLocalVideo = $coverVideoType === 'video_local' && $coverVideoUrl;
    $hasEmbedVideo = $coverVideoType === 'video_embed' && $coverVideoEmbedId;
    $hasAutoplayVideo = $hasLocalVideo || $hasEmbedVideo;
    $uniqueId = 'featured-video-' . uniqid();
@endphp
<section class="w-full max-w-[1880px] mx-auto" style="{{ $sectionStyle }}">
    {{-- Desktop Layout --}}
    <div class="hidden lg:flex w-full max-w-[1440px] mx-auto px-10 pt-16 pb-24 flex-col items-center gap-10">
        {{-- Video Card - 966px max width --}}
        <article class="w-full max-w-[966px] rounded-xl overflow-hidden">
            {{-- Image/Video Section - 544px height on desktop --}}
            <a href="{{ $videoUrl }}" class="relative block h-[544px] overflow-hidden group" id="{{ $uniqueId }}-desktop">
                {{-- Poster Image (fallback) --}}
                @if($image)
                    <img
                        src="{{ $image }}"
                        alt="{{ $imageAlt }}"
                        class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 {{ $hasAutoplayVideo ? 'featured-video-poster' : '' }}"
                        data-poster-for="{{ $uniqueId }}"
                    >
                @endif

                {{-- Autoplay Video (local) --}}
                @if($hasLocalVideo)
                    <video
                        class="absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-300 featured-video-player z-[1]"
                        data-video-id="{{ $uniqueId }}"
                        muted
                        loop
                        playsinline
                        preload="metadata"
                        poster="{{ $image }}"
                    >
                        <source src="{{ $coverVideoUrl }}" type="video/mp4">
                    </video>
                @endif

                {{-- Autoplay Video (YouTube/Vimeo embed) --}}
                @if($hasEmbedVideo)
                    <div
                        class="absolute inset-0 w-full h-full opacity-0 transition-opacity duration-300 featured-video-player z-[1] overflow-hidden"
                        data-video-id="{{ $uniqueId }}"
                        data-embed-provider="{{ $coverVideoEmbedProvider }}"
                        data-embed-video-id="{{ $coverVideoEmbedId }}"
                    >
                        {{-- Gradient overlay inside embed container --}}
                        <div
                            class="absolute inset-0 pointer-events-none z-[100]"
                            style="background: linear-gradient(180deg, transparent 0%, transparent 30%, {{ $overlayColor }}20 50%, {{ $overlayColor }}60 70%, {{ $overlayColor }}90 85%, {{ $overlayColor }} 100%);"
                        ></div>
                    </div>
                @endif

                {{-- Bottom gradient overlay (for poster/local video) --}}
                <div
                    class="absolute inset-0 pointer-events-none z-[5]"
                    style="background: linear-gradient(180deg, transparent 0%, transparent 30%, {{ $overlayColor }}20 50%, {{ $overlayColor }}60 70%, {{ $overlayColor }}90 85%, {{ $overlayColor }} 100%);"
                ></div>

                {{-- Content overlay --}}
                <div class="absolute inset-0 flex flex-col justify-between p-10 z-[10]">
                    {{-- Tag at top --}}
                    @if($category || $tag)
                        <div class="flex items-start">
                            <div class="tag tag-white">
                                @if($category)
                                    <span>{{ strtoupper($category) }}</span>
                                @endif
                                @if($category && $tag)
                                    <span class="mx-2.5">•</span>
                                @endif
                                @if($tag)
                                    <span>{{ strtoupper($tag) }}</span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div></div>
                    @endif

                    {{-- Title at bottom --}}
                    <div class="flex flex-col gap-1 text-blue-black">
                        <h2 class="font-display text-[36px] leading-[1] tracking-[-0.04em] uppercase">{{ $title }}</h2>
                        @if($subtitle)
                            <p class="font-display text-[24px] leading-[1.1] tracking-[-0.04em]">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
            </a>

            {{-- Text Section --}}
            <div class="flex gap-20 items-end px-10 pt-2.5 pb-12" style="background-color: {{ $overlayColor }}">
                {{-- Description & Meta --}}
                <div class="flex-1 flex flex-col gap-6 items-start justify-end min-w-0">
                    @if($description)
                        <p class="text-body-md text-blue-black">{{ $description }}</p>
                    @endif

                    {{-- Author/Date --}}
                    <div class="flex flex-wrap items-center gap-5 text-[14px] leading-[12px] uppercase text-blue-black">
                        @if($author)
                            <span>BY {{ strtoupper($author) }}</span>
                            <span>•</span>
                        @endif
                        @if($date)
                            <span>{{ strtoupper($date) }}</span>
                        @endif
                    </div>
                </div>

                {{-- Watch Button --}}
                <a href="{{ $videoUrl }}" class="btn btn-white shrink-0">
                    {{-- Play icon --}}
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M10 8.5L16 12L10 15.5V8.5Z" fill="currentColor"/>
                    </svg>
                    <span>{{ $buttonText }}</span>
                </a>
            </div>
        </article>
    </div>

    {{-- Mobile Layout --}}
    <div class="lg:hidden w-full px-5 pt-10 pb-16 flex flex-col items-center">
        {{-- Video Card - Full width --}}
        <article class="w-full rounded-xl overflow-hidden flex flex-col">
            {{-- Image Section with overlay content --}}
            <a href="{{ $videoUrl }}" class="relative flex-1 min-h-[450px] flex flex-col justify-between px-4 pt-6 pb-2.5 overflow-hidden" id="{{ $uniqueId }}-mobile">
                {{-- Poster Image (fallback) --}}
                @if($image)
                    <img
                        src="{{ $image }}"
                        alt="{{ $imageAlt }}"
                        class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 {{ $hasAutoplayVideo ? 'featured-video-poster' : '' }}"
                        data-poster-for="{{ $uniqueId }}-mobile"
                    >
                @endif

                {{-- Autoplay Video (local) --}}
                @if($hasLocalVideo)
                    <video
                        class="absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-300 featured-video-player z-[1]"
                        data-video-id="{{ $uniqueId }}-mobile"
                        muted
                        loop
                        playsinline
                        preload="metadata"
                        poster="{{ $image }}"
                    >
                        <source src="{{ $coverVideoUrl }}" type="video/mp4">
                    </video>
                @endif

                {{-- Autoplay Video (YouTube/Vimeo embed) --}}
                @if($hasEmbedVideo)
                    <div
                        class="absolute inset-0 w-full h-full opacity-0 transition-opacity duration-300 featured-video-player z-[1] overflow-hidden"
                        data-video-id="{{ $uniqueId }}-mobile"
                        data-embed-provider="{{ $coverVideoEmbedProvider }}"
                        data-embed-video-id="{{ $coverVideoEmbedId }}"
                    >
                        {{-- Gradient overlay inside embed container --}}
                        <div
                            class="absolute inset-0 pointer-events-none z-[100]"
                            style="background: linear-gradient(180deg, transparent 0%, transparent 30%, {{ $overlayColor }}20 50%, {{ $overlayColor }}60 70%, {{ $overlayColor }}90 85%, {{ $overlayColor }} 100%);"
                        ></div>
                    </div>
                @endif

                {{-- Gradient overlay (for poster/local video) --}}
                <div
                    class="absolute inset-0 pointer-events-none z-[5]"
                    style="background: linear-gradient(180deg, transparent 0%, transparent 30%, {{ $overlayColor }}20 50%, {{ $overlayColor }}60 70%, {{ $overlayColor }}90 85%, {{ $overlayColor }} 100%);"
                ></div>

                {{-- Tag at top center --}}
                @if($category || $tag)
                    <div class="relative z-[10] flex justify-center">
                        <div class="tag">
                            @if($category)
                                <span>{{ strtoupper($category) }}</span>
                                @if($tag)
                                    <span class="mx-2.5 hidden">•</span>
                                    <span class="hidden">{{ strtoupper($tag) }}</span>
                                @endif
                            @elseif($tag)
                                <span>{{ strtoupper($tag) }}</span>
                            @endif
                        </div>
                    </div>
                @else
                    <div></div>
                @endif

                {{-- Title/Subtitle at bottom center --}}
                <div class="relative z-[10] flex flex-col items-center text-center text-blue-black pt-16 pb-2.5 gap-1">
                    <h2 class="font-display text-[28px] leading-[1] tracking-[-0.04em] uppercase">{{ $title }}</h2>
                    @if($subtitle)
                        <p class="font-display text-[20px] leading-[1.1] tracking-[-0.04em]">{{ $subtitle }}</p>
                    @endif
                </div>
            </a>

            {{-- Yellow Section with description, meta, button --}}
            <div class="flex flex-col gap-5 items-center px-4 pt-2.5 pb-8" style="background-color: {{ $overlayColor }}">
                <div class="flex flex-col gap-5 items-center w-full">
                    @if($description)
                        <p class="text-body-md text-blue-black text-center">{{ $description }}</p>
                    @endif

                    {{-- Author/Date - stacked vertically --}}
                    <div class="flex flex-col items-center gap-4 text-[12px] leading-[12px] uppercase text-blue-black">
                        @if($author)
                            <span class="underline underline-offset-4">BY {{ strtoupper($author) }}</span>
                        @endif
                        @if($date)
                            <span>{{ strtoupper($date) }}</span>
                        @endif
                    </div>
                </div>

                {{-- Watch Button --}}
                <a href="{{ $videoUrl }}" class="btn btn-white">
                    {{-- Play icon --}}
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M10 8.5L16 12L10 15.5V8.5Z" fill="currentColor"/>
                    </svg>
                    <span>{{ $buttonText }}</span>
                </a>
            </div>
        </article>
    </div>
</section>

@if($hasAutoplayVideo)
<script>
(function() {
    const videoId = '{{ $uniqueId }}';

    // Initialize IntersectionObserver for autoplay
    function initAutoplayObserver(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const videoPlayer = container.querySelector('.featured-video-player');
        const poster = container.querySelector('.featured-video-poster');
        if (!videoPlayer) return;

        let embedIframe = null;
        let hasStarted = false;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    // Video is in viewport - play/resume
                    if (videoPlayer.tagName === 'VIDEO') {
                        videoPlayer.play().then(() => {
                            videoPlayer.style.opacity = '1';
                            if (poster) poster.style.opacity = '0';
                            hasStarted = true;
                        }).catch(() => {
                            // Autoplay blocked - keep poster visible
                        });
                    } else if (videoPlayer.dataset.embedProvider) {
                        // Create embed iframe once for YouTube/Vimeo
                        if (!embedIframe) {
                            embedIframe = document.createElement('iframe');
                            embedIframe.className = 'w-full h-full';
                            // Use aspect ratio scaling to achieve object-fit:cover effect for iframe
                            // Scale based on container aspect ratio vs video aspect ratio (16:9)
                            const containerRect = videoPlayer.getBoundingClientRect();
                            const containerAspect = containerRect.width / containerRect.height;
                            const videoAspect = 16 / 9;
                            let scale = 1;
                            if (containerAspect > videoAspect) {
                                // Container is wider than video - scale to fill width
                                scale = containerAspect / videoAspect;
                            } else {
                                // Container is taller than video - scale to fill height
                                scale = videoAspect / containerAspect;
                            }
                            // Add small buffer to ensure no gaps
                            scale = Math.max(scale, 1.02);
                            embedIframe.style.cssText = `position:absolute;top:50%;left:50%;width:100%;height:100%;transform:translate(-50%,-50%) scale(${scale});pointer-events:none;z-index:1;border:0;`;
                            embedIframe.setAttribute('frameborder', '0');
                            embedIframe.setAttribute('allow', 'autoplay; encrypted-media');

                            if (videoPlayer.dataset.embedProvider === 'youtube') {
                                // YouTube: use enablejsapi for pause/resume control
                                embedIframe.src = `https://www.youtube.com/embed/${videoPlayer.dataset.embedVideoId}?autoplay=1&mute=1&loop=1&playlist=${videoPlayer.dataset.embedVideoId}&controls=0&showinfo=0&rel=0&modestbranding=1&enablejsapi=1&disablekb=1`;
                                embedIframe.id = containerId + '-yt-iframe';
                            } else if (videoPlayer.dataset.embedProvider === 'vimeo') {
                                embedIframe.src = `https://player.vimeo.com/video/${videoPlayer.dataset.embedVideoId}?autoplay=1&muted=1&loop=1&background=1`;
                            }

                            // Insert before gradient overlay (first child)
                            videoPlayer.insertBefore(embedIframe, videoPlayer.firstChild);
                            hasStarted = true;
                        } else {
                            // Resume: send play command via postMessage
                            if (videoPlayer.dataset.embedProvider === 'youtube') {
                                embedIframe.contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*');
                            } else if (videoPlayer.dataset.embedProvider === 'vimeo') {
                                embedIframe.contentWindow.postMessage('{"method":"play"}', '*');
                            }
                        }
                        videoPlayer.style.opacity = '1';
                        if (poster) poster.style.opacity = '0';
                    }
                } else if (hasStarted) {
                    // Video is out of viewport - pause (not stop/remove)
                    if (videoPlayer.tagName === 'VIDEO') {
                        videoPlayer.pause();
                    } else if (embedIframe) {
                        // Pause via postMessage instead of removing iframe
                        if (videoPlayer.dataset.embedProvider === 'youtube') {
                            embedIframe.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
                        } else if (videoPlayer.dataset.embedProvider === 'vimeo') {
                            embedIframe.contentWindow.postMessage('{"method":"pause"}', '*');
                        }
                    }
                    // Keep video visible but paused (no fade back to poster)
                }
            });
        }, {
            threshold: 0.3 // Trigger when 30% of the element is visible
        });

        observer.observe(container);
    }

    // Initialize for both desktop and mobile layouts
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initAutoplayObserver(videoId + '-desktop');
            initAutoplayObserver(videoId + '-mobile');
        });
    } else {
        initAutoplayObserver(videoId + '-desktop');
        initAutoplayObserver(videoId + '-mobile');
    }
})();
</script>
@endif
@endif
