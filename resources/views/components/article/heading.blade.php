{{-- Article Subheading + Text Block --}}
@props(['heading' => '', 'text' => ''])

<div class="article-content-narrow flex flex-col gap-6 md:gap-8">
    @if($heading)
        <h2 class="article-subheading">{{ $heading }}</h2>
    @endif
    @if($text)
        <div class="article-text">
            {!! $text !!}
        </div>
    @endif
</div>
