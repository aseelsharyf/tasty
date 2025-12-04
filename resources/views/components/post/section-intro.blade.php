{{-- resources/views/components/post/section-intro.blade.php --}}

@props([
    'image',
    'imageAlt' => '',
    'title' => '',
    'titleLarge' => '',
    'description' => '',
    'maxWidth' => null, // Optional: e.g., 'max-w-[310px] md:max-w-[400px]'
])

<div class="w-full {{ $maxWidth }} flex flex-col justify-start items-center gap-8">
    <img class="w-full h-[156px] md:h-[429.5px] object-contain"
         src="{{ $image }}"
         alt="{{ $imageAlt }}" />

    <div class="w-full flex flex-col justify-start items-start gap-6">
        <div class="w-full flex flex-col justify-start items-center">
            @if($title)
                <x-ui.heading
                    level="h2"
                    :text="$title"
                    align="center"
                />
            @endif

            @if($titleLarge)
                <x-ui.heading
                    level="h1"
                    :text="$titleLarge"
                    align="center"
                    :uppercase="true"
                />
            @endif
        </div>

        @if($description)
            <x-ui.text
                :text="$description"
                variant="md"
                align="center"
            />
        @endif
    </div>
</div>
