@extends('layouts.app')

@section('content')
@if($page->usesMarkdown())
<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-20 bg-tasty-off-white min-h-screen">
    <article class="max-w-3xl mx-auto prose-page">
        {!! $page->renderContent() !!}
    </article>
</div>
@else
{!! $page->renderContent() !!}
@endif
@endsection
