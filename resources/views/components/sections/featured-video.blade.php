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
@endphp
<section class="w-full max-w-[1880px] mx-auto" style="{{ $sectionStyle }}">
    <div class="w-full max-w-[1440px] mx-auto px-10 pt-16 pb-32 max-lg:px-5 max-lg:pt-10 max-lg:pb-16 flex flex-col items-center gap-10">
        {{-- Video Card - 966px max width --}}
        <article class="w-full max-w-[966px] rounded-xl overflow-hidden">
            {{-- Image/Video Section - 544px height on desktop --}}
            <a href="{{ $videoUrl }}" class="relative block h-[544px] max-lg:h-[300px] overflow-hidden group">
                {{-- Background Image --}}
                @if($image)
                    <img
                        src="{{ $image }}"
                        alt="{{ $imageAlt }}"
                        class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                    >
                @endif

                {{-- Bottom gradient overlay --}}
                <div
                    class="absolute inset-0 pointer-events-none"
                    style="background: linear-gradient(180deg, transparent 0%, transparent 40%, {{ $overlayColor }}80 70%, {{ $overlayColor }} 100%);"
                ></div>

                {{-- Content overlay --}}
                <div class="absolute inset-0 flex flex-col justify-between p-10 max-lg:p-5">
                    {{-- Tag at top - centered on mobile --}}
                    @if($category || $tag)
                        <div class="flex items-start max-lg:justify-center">
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

                    {{-- Title at bottom - centered on mobile --}}
                    <div class="flex flex-col gap-2 text-blue-black max-lg:text-center">
                        <h2 class="text-h3 uppercase max-lg:text-[32px] max-lg:leading-[32px]">{{ $title }}</h2>
                        @if($subtitle)
                            <p class="text-h4 max-lg:text-[24px] max-lg:leading-[28px]">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
            </a>

            {{-- Text Section - Configurable background color --}}
            <div class="flex gap-20 items-end px-10 pt-2.5 pb-12 max-lg:flex-col max-lg:items-center max-lg:gap-6 max-lg:px-5 max-lg:pb-8" style="background-color: {{ $overlayColor }}">
                {{-- Description & Meta --}}
                <div class="flex-1 flex flex-col gap-6 items-start justify-end min-w-0 max-lg:items-center max-lg:text-center">
                    @if($description)
                        <p class="text-body-md text-blue-black">{{ $description }}</p>
                    @endif

                    {{-- Author/Date - stacked on mobile --}}
                    <div class="flex flex-wrap items-center gap-5 text-[14px] leading-[12px] uppercase text-blue-black max-lg:flex-col max-lg:gap-2">
                        @if($author)
                            <span>BY {{ strtoupper($author) }}</span>
                            <span class="max-lg:hidden">•</span>
                        @endif
                        @if($date)
                            <span>{{ strtoupper($date) }}</span>
                        @endif
                    </div>
                </div>

                {{-- Watch Button - NOT full width on mobile --}}
                <a href="{{ $videoUrl }}" class="inline-flex items-center gap-2 pl-3 pr-4 py-2.5 bg-white text-blue-black rounded-full text-[18px] leading-[26px] font-sans shrink-0 hover:opacity-85 transition-opacity">
                    {{-- Play icon --}}
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M10 8.5L16 12L10 15.5V8.5Z" fill="currentColor"/>
                    </svg>
                    <span>{{ $buttonText }}</span>
                </a>
            </div>
        </article>
    </div>
</section>
@endif
