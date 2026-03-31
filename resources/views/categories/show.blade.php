@extends('layouts.app')

@section('content')
    <x-sections.latest-updates
        :introImage="$category->featuredImage?->url"
        introImageAlt="{{ $category->name }}"
        titleSmall=""
        :titleLarge="$category->name"
        :description="$category->description ?? 'Explore our latest posts in this category.'"
        buttonText="More Posts"
        loadAction="byCategory"
        :loadParams="['category' => $category->slug]"
        :autoFetch="true"
        :featuredCount="1"
        :postsCount="8"
    />
@endsection
