<section {{ $attributes->merge(['class' => "w-full {$bgClass} {$padding}"]) }} style="max-width: {{ $maxWidth }}; margin-left: auto; margin-right: auto;{{ $bgStyle }}">
    @if($container)
        <div class="container-main">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</section>
