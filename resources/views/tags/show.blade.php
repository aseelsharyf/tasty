@extends('layouts.app')

@section('content')
    <x-sections.latest-updates
        introImage="{{ Vite::asset('resources/images/latest-updates-transparent.png') }}"
        introImageAlt="{{ $tag->name }}"
        titleSmall="Tag"
        :titleLarge="$tag->name"
        description="Explore our latest posts with this tag."
        buttonText="More Posts"
        loadAction="byTag"
        :loadParams="['tag' => $tag->slug]"
        :autoFetch="true"
        :featuredCount="1"
        :postsCount="8"
    />
@endsection
