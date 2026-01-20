{{-- Default Article Header - Split layout with image left, text right --}}
@props([
    'post',  // Post model
])

@php
    $category = $post->categories->first();
    $photographer = $post->getCustomField('photographer');
    $anchor = $post->featured_image_anchor ?? ['x' => 50, 'y' => 0];
    $objectPosition = ($anchor['x'] ?? 50) . '% ' . ($anchor['y'] ?? 50) . '%';

    // Cover video handling
    $coverVideo = $post->coverVideo;
    $hasVideo = $coverVideo !== null;
    $videoEmbedUrl = null;
    $localVideoUrl = null;

    if ($hasVideo) {
        if ($coverVideo->type === 'video_embed' && $coverVideo->embed_video_id) {
            if ($coverVideo->embed_provider === 'youtube') {
                $videoEmbedUrl = "https://www.youtube.com/embed/{$coverVideo->embed_video_id}?rel=0&autoplay=1";
            } elseif ($coverVideo->embed_provider === 'vimeo') {
                $videoEmbedUrl = "https://player.vimeo.com/video/{$coverVideo->embed_video_id}?autoplay=1";
            }
        } elseif ($coverVideo->type === 'video_local') {
            $localVideoUrl = $coverVideo->url;
        }

        // Fallback: try to extract from thumbnail URL for legacy data
        if (!$videoEmbedUrl && !$localVideoUrl && $coverVideo->thumbnail_url) {
            if (preg_match('/img\.youtube\.com\/vi\/([a-zA-Z0-9_-]{11})/', $coverVideo->thumbnail_url, $matches)) {
                $videoEmbedUrl = "https://www.youtube.com/embed/{$matches[1]}?rel=0&autoplay=1";
            } elseif (preg_match('/vumbnail\.com\/(\d+)/', $coverVideo->thumbnail_url, $matches)) {
                $videoEmbedUrl = "https://player.vimeo.com/video/{$matches[1]}?autoplay=1";
            }
        }
    }
@endphp

<header class="w-full max-w-[1880px] mx-auto">
    <div class="flex flex-col lg:flex-row min-h-[854px] lg:min-h-[864px]">
        {{-- Image/Video Section (Left) --}}
        @if($hasVideo)
            <div
                x-data="heroVideoPlayer()"
                class="hero-image-container w-full lg:w-[65%] lg:flex-none relative"
            >
                {{-- Featured Image (shown when video not playing) --}}
                <div x-show="!isPlaying" class="absolute inset-0">
                    @if($post->featured_image_url)
                        <img
                            src="{{ $post->featured_image_url }}"
                            alt="{{ $post->title }}"
                            class="absolute inset-0 w-full h-full object-cover"
                            style="object-position: {{ $objectPosition }}"
                        />
                    @else
                        <div class="absolute inset-0 bg-tasty-blue-black/10"></div>
                    @endif

                    {{-- Play Button Overlay --}}
                    <button
                        type="button"
                        @click="playVideo()"
                        class="absolute inset-0 flex items-center justify-center group cursor-pointer"
                    >
                        <div class="w-24 h-24 md:w-32 md:h-32 bg-white/90 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-10 h-10 md:w-12 md:h-12 text-tasty-blue-black ml-1 md:ml-2" fill="currentColor" viewBox="0 0 24 24">
                                <polygon points="5 3 19 12 5 21 5 3"/>
                            </svg>
                        </div>
                    </button>
                </div>

                {{-- Video Player (shown when playing) --}}
                <div x-show="isPlaying" x-cloak class="absolute inset-0 bg-black">
                    @if($videoEmbedUrl)
                        {{-- YouTube/Vimeo Embed --}}
                        <iframe
                            x-ref="videoIframe"
                            :src="isPlaying ? '{{ $videoEmbedUrl }}' : ''"
                            class="w-full h-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    @elseif($localVideoUrl)
                        {{-- Local Video --}}
                        <video
                            x-ref="videoElement"
                            class="w-full h-full object-cover"
                            controls
                            playsinline
                        >
                            <source src="{{ $localVideoUrl }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    @endif

                    {{-- Close/Back to Image Button --}}
                    <button
                        type="button"
                        @click="stopVideo()"
                        class="absolute top-4 right-4 w-12 h-12 bg-black/50 hover:bg-black/70 rounded-full flex items-center justify-center text-white transition-colors z-10"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @else
            {{-- No video - just show image --}}
            <div class="hero-image-container w-full lg:w-[65%] lg:flex-none relative">
                @if($post->featured_image_url)
                    <img
                        src="{{ $post->featured_image_url }}"
                        alt="{{ $post->title }}"
                        class="absolute inset-0 w-full h-full object-cover"
                        style="object-position: {{ $objectPosition }}"
                    />
                @else
                    <div class="absolute inset-0 bg-tasty-blue-black/10"></div>
                @endif
            </div>
        @endif

        {{-- Text Section (Right) --}}
        <div class="w-full lg:w-[35%] bg-tasty-yellow flex flex-col justify-end p-6 sm:p-8 lg:p-10 lg:py-24 gap-8 lg:gap-10">
            {{-- Category Tag --}}
            @if($category)
                <div class="flex items-center">
                    <a href="{{ route('category.show', $category->slug) }}" class="text-[14px] leading-[12px] uppercase text-tasty-blue-black font-sans hover:underline">
                        {{ $category->name }}
                    </a>
                </div>
            @endif

            {{-- Kicker, Title & Excerpt --}}
            <div class="flex flex-col gap-4">
                @if($post->kicker)
                    <span class="text-h2 text-tasty-blue-black uppercase">
                        {{ $post->kicker }}
                    </span>
                @endif
                <h1 class="text-h3 text-tasty-blue-black">
                    {{ $post->title }}
                </h1>
                @if($post->excerpt)
                    <p class="text-body-lg text-tasty-blue-black">
                        {{ $post->excerpt }}
                    </p>
                @endif
            </div>

            {{-- Author/Photographer/Date Row --}}
            <div class="flex items-center gap-5 text-[14px] leading-[12px] uppercase text-tasty-blue-black font-sans flex-wrap">
                @if($post->author)
                    <a href="{{ $post->author->url ?? '#' }}" class="underline underline-offset-4 hover:no-underline">
                        BY {{ $post->author->name }}
                    </a>
                @endif

                @if($photographer)
                    @if($post->author)
                        <span>&bull;</span>
                    @endif
                    <span>PHOTO BY {{ $photographer }}</span>
                @endif

                @if($post->published_at)
                    @if($post->author || $photographer)
                        <span>&bull;</span>
                    @endif
                    <span>{{ $post->published_at->format('F j, Y') }}</span>
                @endif
            </div>

            {{-- Sponsor Badge --}}
            <x-article.sponsor-badge :sponsor="$post->sponsor" />
        </div>
    </div>
</header>

@if($hasVideo)
@once
@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('heroVideoPlayer', () => ({
            isPlaying: false,

            playVideo() {
                this.isPlaying = true;

                // For local videos, start playback
                this.$nextTick(() => {
                    const video = this.$refs.videoElement;
                    if (video) {
                        video.play();
                    }
                });
            },

            stopVideo() {
                this.isPlaying = false;

                // For local videos, pause
                const video = this.$refs.videoElement;
                if (video) {
                    video.pause();
                    video.currentTime = 0;
                }
            }
        }));
    });
</script>
@endpush
@endonce
@endif
