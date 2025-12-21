{{-- Featured Person Section --}}
@if($post)
<section class="w-full max-w-[1880px] mx-auto profile-section-container">
    {{-- Top part with image --}}
    <div class="{{ $topBgColorClass }} flex flex-col items-center" @if($topBgColorStyle) style="{{ $topBgColorStyle }}" @endif>
        {{-- Profile Photo Container with rounded top - background image --}}
        <a
            href="{{ $post->url }}"
            class="profile-image-container w-full max-w-[1880px] flex flex-col justify-center items-center gap-10 max-md:gap-[10px] bg-cover bg-center bg-no-repeat relative overflow-hidden group"
            style="background-image: url('{{ $post->featured_image_url }}');"
        >
            {{-- Gradient overlay - fades to background color at bottom --}}
            <div class="absolute inset-0 pointer-events-none transition-opacity group-hover:opacity-90" style="{{ $overlayGradient }}"></div>
        </a>
    </div>

    {{-- Bottom part with content --}}
    <div class="{{ $bgColorClass }} pb-32 max-md:pb-16" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
        <div class="container-main flex flex-col items-center gap-10 text-center w-full px-10 max-md:px-5 max-md:gap-5">
            {{-- Title --}}
            <a href="{{ $post->url }}" class="hover:opacity-80 transition-opacity">
                <h2 class="text-h1 text-blue-black uppercase max-md:text-[60px] max-md:leading-[50px]">{{ $post->title }}</h2>
            </a>

            {{-- Subtitle --}}
            @if($post->subtitle)
                <p class="text-h2 text-blue-black max-md:text-[40px] max-md:leading-[44px]">{{ $post->subtitle }}</p>
            @endif

            {{-- Tags --}}
            <div class="flex items-center gap-5 text-caption uppercase text-blue-black">
                @if($tag1)
                    <span>{{ $tag1 }}</span>
                    <span>•</span>
                @endif
                @if($tag2)
                    @php $category = $post->categories->first(); @endphp
                    @if($category)
                        <a href="{{ route('category.show', $category->slug) }}" class="hover:underline">{{ strtoupper($tag2) }}</a>
                    @else
                        <span>{{ strtoupper($tag2) }}</span>
                    @endif
                @endif
            </div>

            {{-- Description --}}
            @if($post->excerpt)
                <p class="text-body-large text-blue-black max-w-[650px]">{{ $post->excerpt }}</p>
            @endif

            {{-- Button --}}
            <a href="{{ $post->url }}" class="btn btn-white">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="#0A0924" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>{{ $buttonText }}</span>
            </a>
        </div>
    </div>
</section>
@endif
