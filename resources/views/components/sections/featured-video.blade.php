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
    {{-- Desktop Layout --}}
    <div class="hidden lg:flex w-full max-w-[1440px] mx-auto px-10 pt-16 pb-32 flex-col items-center gap-10">
        {{-- Video Card - 966px max width --}}
        <article class="w-full max-w-[966px] rounded-xl overflow-hidden">
            {{-- Image/Video Section - 544px height on desktop --}}
            <a href="{{ $videoUrl }}" class="relative block h-[544px] overflow-hidden group">
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
                <div class="absolute inset-0 flex flex-col justify-between p-10">
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
                    <div class="flex flex-col gap-2 text-blue-black">
                        <h2 class="text-h3 uppercase">{{ $title }}</h2>
                        @if($subtitle)
                            <p class="text-h4">{{ $subtitle }}</p>
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
                <a href="{{ $videoUrl }}" class="inline-flex items-center gap-2 pl-[18px] pr-5 py-3 bg-white text-blue-black rounded-full text-[20px] leading-[26px] font-sans shrink-0 hover:opacity-85 transition-opacity">
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

    {{-- Mobile Layout --}}
    <div class="lg:hidden w-full px-5 pt-8 pb-16 flex flex-col items-center">
        {{-- Video Card - Full width --}}
        <article class="w-full rounded-xl overflow-hidden flex flex-col">
            {{-- Image Section with overlay content --}}
            <a href="{{ $videoUrl }}" class="relative flex-1 min-h-[300px] flex flex-col justify-between px-4 pt-6 pb-2.5 overflow-hidden">
                {{-- Background Image --}}
                @if($image)
                    <img
                        src="{{ $image }}"
                        alt="{{ $imageAlt }}"
                        class="absolute inset-0 w-full h-full object-cover"
                    >
                @endif

                {{-- Gradient overlay --}}
                <div
                    class="absolute inset-0 pointer-events-none"
                    style="background: linear-gradient(180deg, transparent 0%, transparent 50%, {{ $overlayColor }}80 80%, {{ $overlayColor }} 100%);"
                ></div>

                {{-- Tag at top center --}}
                @if($category || $tag)
                    <div class="relative z-10 flex justify-center">
                        <div class="tag">
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

                {{-- Title/Subtitle at bottom center --}}
                <div class="relative z-10 flex flex-col items-center text-center text-blue-black py-2.5">
                    <h2 class="text-[32px] leading-[32px] tracking-[-1.28px] font-display uppercase">{{ $title }}</h2>
                    @if($subtitle)
                        <p class="text-[24px] leading-[24px] tracking-[-0.96px] font-display">{{ $subtitle }}</p>
                    @endif
                </div>
            </a>

            {{-- Yellow Section with description, meta, button --}}
            <div class="flex flex-col gap-5 items-center px-4 pt-2.5 pb-8" style="background-color: {{ $overlayColor }}">
                <div class="flex flex-col gap-6 items-center w-full">
                    @if($description)
                        <p class="text-[18px] leading-[24px] text-blue-black text-center">{{ $description }}</p>
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
                <a href="{{ $videoUrl }}" class="inline-flex items-center gap-2 pl-[18px] pr-5 py-3 bg-white text-blue-black rounded-full text-[20px] leading-[26px] font-sans hover:opacity-85 transition-opacity">
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
