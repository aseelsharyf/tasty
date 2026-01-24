@props([
    'html' => '',
])

@if($html)
<section class="w-full">
    <div class="w-full">
        {!! $html !!}
    </div>
</section>
@endif
