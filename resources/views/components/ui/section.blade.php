<section
    class="w-full max-w-[{{ $maxWidth }}] mx-auto {{ $bgClass }} {{ $padding }} {{ $container ? 'container-main' : '' }}"
    @if($bgStyle) style="{{ $bgStyle }}" @endif
    {{ $attributes }}
>
    {{ $slot }}
</section>
