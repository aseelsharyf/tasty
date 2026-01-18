{{-- Recipe Body Layout --}}
{{-- Two-column layout: Ingredients (left) | Preparation (right) --}}

@props([
    'post',
    'contentBlocks' => [],
    'isRtl' => false,
])

@php
    $ingredients = $post->custom_fields['ingredients'] ?? [];
    $introduction = $post->custom_fields['introduction'] ?? null;
    $prepTime = $post->custom_fields['prep_time'] ?? null;
    $cookTime = $post->custom_fields['cook_time'] ?? null;
    $servings = $post->custom_fields['servings'] ?? null;
    $hasMetadata = $prepTime || $cookTime || $servings;
@endphp

<div class="w-full bg-off-white py-16">
    <div class="max-w-[1200px] mx-auto px-4 lg:px-8">

        {{-- Introduction (from custom_fields, separate from excerpt) --}}
        @if($introduction)
            <div class="max-w-[894px] mx-auto text-center mb-12 {{ $isRtl ? 'font-dhivehi' : '' }}">
                <p class="text-body-lg text-tasty-blue-black/90" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
                    {{ $introduction }}
                </p>
            </div>
        @endif

        {{-- Recipe Metadata Pills (only show if any values exist) --}}
        @if($hasMetadata)
            <div class="flex justify-center items-center gap-8 py-6 border-t border-b border-tasty-blue-black/10 mb-12 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                @if($prepTime)
                    <span class="text-body text-tasty-blue-black/60">
                        {{ $isRtl ? 'ތައްޔާރުކުރާ ވަގުތު' : 'Prep' }}: {{ $prepTime }} {{ $isRtl ? 'މިނެޓް' : 'min' }}
                    </span>
                @endif
                @if($cookTime)
                    <span class="text-body text-tasty-blue-black/60">
                        {{ $isRtl ? 'ކައްކާ ވަގުތު' : 'Cook' }}: {{ $cookTime }} {{ $isRtl ? 'މިނެޓް' : 'min' }}
                    </span>
                @endif
                @if($servings)
                    <span class="text-body text-tasty-blue-black/60">
                        {{ $isRtl ? 'ޚިދުމަތް' : 'Serves' }}: {{ $servings }}
                    </span>
                @endif
            </div>
        @endif

        {{-- Two Column Layout --}}
        <div class="flex flex-col lg:flex-row gap-12 lg:gap-16 {{ $isRtl ? 'lg:flex-row-reverse' : '' }}">
            {{-- Left Column: Ingredients --}}
            <aside class="w-full lg:w-[280px] lg:flex-shrink-0">
                <div class="lg:sticky lg:top-8">
                    <x-recipe.ingredients :ingredients="$ingredients" :isRtl="$isRtl" />
                </div>
            </aside>

            {{-- Right Column: Preparation Steps --}}
            <main class="flex-1 min-w-0">
                {{-- Section Heading --}}
                <h2 class="text-h3 uppercase text-tasty-blue-black mb-6 {{ $isRtl ? 'text-right' : '' }}">
                    {{ $isRtl ? 'ތައްޔާރުކުރާނެގޮތް' : 'Preparation' }}
                </h2>

                {{-- Content Blocks --}}
                @if(!empty($contentBlocks))
                    @include('templates.posts.partials.content-blocks', [
                        'blocks' => $contentBlocks,
                        'isRtl' => $isRtl,
                        'contentWidth' => 'w-full',
                        'fullWidth' => 'w-full',
                    ])
                @else
                    <p class="text-body text-tasty-blue-black/60 {{ $isRtl ? 'text-right font-dhivehi' : '' }}">
                        {{ $isRtl ? 'ތައްޔާރުކުރާނެގޮތް އެއްވެސް ތަނެއް ނެތް' : 'No preparation steps listed.' }}
                    </p>
                @endif
            </main>
        </div>
    </div>
</div>
