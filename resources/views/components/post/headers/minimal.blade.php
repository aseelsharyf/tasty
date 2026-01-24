{{-- Minimal Article Header - Side-by-side with rounded image, includes meta --}}
@props([
    'post',  // Post model
    'includeMeta' => true,  // Whether to include author/date/share (since it's built into this header)
])

@php
    $category = $post->categories->first();
    $photographer = $post->getCustomField('photographer');
    $url = request()->url();
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

<header class="w-full bg-tasty-yellow pt-[96px] md:pt-[112px] lg:h-[calc(100vh-80px)]">
    <div class="max-w-[1440px] mx-auto h-full p-6 sm:p-8 lg:p-10">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-10 items-center h-full">
            {{-- Image/Video (Left) --}}
            @if($hasVideo)
                <div
                    x-data="heroVideoPlayer()"
                    class="w-full lg:flex-1 aspect-square lg:aspect-auto lg:h-[500px] relative rounded-xl overflow-hidden"
                >
                    {{-- Featured Image (shown when video not playing) --}}
                    <div x-show="!isPlaying" class="absolute inset-0">
                        @if($post->featured_image_url)
                            <img
                                src="{{ $post->featured_image_url }}"
                                alt="{{ $post->title }}"
                                class="absolute inset-0 w-full h-full object-cover rounded-xl"
                                style="object-position: {{ $objectPosition }}"
                            />
                        @else
                            <div class="absolute inset-0 bg-tasty-blue-black/10 rounded-xl"></div>
                        @endif

                        {{-- Play Button Overlay --}}
                        <button
                            type="button"
                            @click="playVideo()"
                            class="absolute inset-0 flex items-center justify-center group cursor-pointer"
                        >
                            <div class="w-20 h-20 md:w-24 md:h-24 bg-white/90 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-8 h-8 md:w-10 md:h-10 text-tasty-blue-black ml-1" fill="currentColor" viewBox="0 0 24 24">
                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                </svg>
                            </div>
                        </button>
                    </div>

                    {{-- Video Player (shown when playing) --}}
                    <div x-show="isPlaying" x-cloak class="absolute inset-0 bg-black rounded-xl overflow-hidden">
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
                            class="absolute top-4 right-4 w-10 h-10 bg-black/50 hover:bg-black/70 rounded-full flex items-center justify-center text-white transition-colors z-10"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @else
                <div class="w-full lg:flex-1 aspect-square lg:aspect-auto lg:h-[500px] relative rounded-xl overflow-hidden">
                    @if($post->featured_image_url)
                        <img
                            src="{{ $post->featured_image_url }}"
                            alt="{{ $post->title }}"
                            class="absolute inset-0 w-full h-full object-cover rounded-xl"
                            style="object-position: {{ $objectPosition }}"
                        />
                    @else
                        <div class="absolute inset-0 bg-tasty-blue-black/10 rounded-xl"></div>
                    @endif
                </div>
            @endif

            {{-- Content (Right) --}}
            <div class="w-full lg:flex-1 flex flex-col gap-12 lg:gap-16">
                {{-- Category Tag --}}
                @if($category)
                    <div class="flex items-center">
                        <a href="{{ route('category.show', $category->slug) }}" class="text-[14px] leading-[12px] uppercase text-tasty-blue-black font-sans hover:underline">
                            {{ $category->name }}
                        </a>
                    </div>
                @endif

                {{-- Kicker, Title & Excerpt --}}
                <div class="flex flex-col gap-3">
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

                {{-- Credits & Share (in card, aligned left) --}}
                @if($includeMeta)
                    <div class="flex flex-col gap-8">
                        {{-- Sponsor Badge --}}
                        <x-article.sponsor-badge :sponsor="$post->sponsor" />

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

                        {{-- Share Icons (left-aligned in card) --}}
                        <x-article.share-icons :url="$url" :title="$post->title" align="left" />
                    </div>
                @endif
            </div>
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
