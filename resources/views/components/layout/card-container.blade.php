<div {{ $attributes->merge(['class' => $containerClasses()]) }} @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
    <div class="{{ $innerClasses() }}">
        {{ $slot }}
    </div>
</div>
