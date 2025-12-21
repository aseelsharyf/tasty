@props([
    'author' => 'Unknown',
    'authorUrl' => '#',
    'date' => '',
    'centered' => false,
])

<div {{ $attributes->merge(['class' => 'text-caption uppercase text-blue-black ' . ($centered ? 'author-date' : 'meta-row')]) }}>
    <a href="{{ $authorUrl }}" class="underline underline-offset-4 hover:text-tasty-yellow transition-colors">BY {{ $author }}</a>
    @if($date)
        <span class="meta-separator">•</span>
        <span>{{ $date }}</span>
    @endif
</div>
