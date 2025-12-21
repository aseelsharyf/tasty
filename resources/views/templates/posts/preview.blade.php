@extends('layouts.app')

@section('content')
    @include('templates.posts.article', [
        'post' => $post,
        'template' => $template,
        'templateConfig' => $templateConfig,
        'isRtl' => $isRtl,
        'readingTime' => $readingTime,
        'showAuthor' => $showAuthor,
        'isPreview' => true,
    ])
@endsection
