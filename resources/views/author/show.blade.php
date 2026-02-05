@extends('layouts.app')

@section('content')
    <x-sections.latest-updates
        :introImage="$author->avatar_url"
        introImageAlt="{{ $author->name }}"
        titleSmall="Author"
        :titleLarge="$author->name"
        description="Explore all posts by {{ $author->name }}."
        buttonText="More Posts"
        loadAction="byAuthor"
        :loadParams="['author' => $author->username]"
        :autoFetch="true"
        :featuredCount="1"
        :postsCount="8"
        imageStyle="author"
        :authorInitials="strtoupper(collect(explode(' ', $author->name))->map(fn($n) => substr($n, 0, 1))->take(2)->join(''))"
    />
@endsection
