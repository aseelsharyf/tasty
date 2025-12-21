@extends('layouts.app')

@section('content')
    @include('templates.posts.article', [
        'post' => $post,
        'template' => $post->template ?? 'default',
        'isRtl' => false,
        'isPreview' => false,
        'relatedPosts' => $relatedPosts,
    ])
@endsection
