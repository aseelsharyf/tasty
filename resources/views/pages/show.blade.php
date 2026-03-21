@extends('layouts.app')

@section('content')
@if($page->usesBlade())
{!! $page->renderContent() !!}
@else
<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-20 bg-tasty-off-white min-h-screen">
    <article class="max-w-3xl mx-auto prose-page">
        @if($page->title)
            <h1>{{ $page->title }}</h1>
        @endif
        @php
            $rendered = $page->renderContent();
            $isHtml = $rendered !== strip_tags($rendered);
        @endphp
        @if($isHtml)
            {!! $rendered !!}
        @else
            @foreach(preg_split('/\n{2,}/', $rendered) as $paragraph)
                @php $trimmed = trim($paragraph); @endphp
                @if($trimmed !== '')
                    @if(preg_match('/^\d+\.\s/', $trimmed) || preg_match('/^[A-Z][A-Z\s&]+$/', explode("\n", $trimmed)[0]))
                        <h2>{{ explode("\n", $trimmed)[0] }}</h2>
                        @if(count(explode("\n", $trimmed)) > 1)
                            <p>{!! nl2br(e(implode("\n", array_slice(explode("\n", $trimmed), 1)))) !!}</p>
                        @endif
                    @else
                        <p>{!! nl2br(e($trimmed)) !!}</p>
                    @endif
                @endif
            @endforeach
        @endif
    </article>
</div>
@endif
@endsection
