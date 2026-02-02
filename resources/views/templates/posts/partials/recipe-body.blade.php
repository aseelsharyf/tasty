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

<div class="w-full bg-off-white">
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
            <div class="flex justify-center mb-12">
                <div class="inline-flex items-center gap-6 px-8 py-4 bg-white rounded-full shadow-sm {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    @if($prepTime)
                        <span class="text-body text-tasty-blue-black/70">
                            {{ $isRtl ? 'ތައްޔާރުކުރާ ވަގުތު' : 'Prep' }}: {{ $prepTime }} {{ $isRtl ? 'މިނެޓް' : 'min' }}
                        </span>
                    @endif
                    @if($cookTime)
                        <span class="text-body text-tasty-blue-black/70">
                            {{ $isRtl ? 'ކައްކާ ވަގުތު' : 'Cook' }}: {{ $cookTime }} {{ $isRtl ? 'މިނެޓް' : 'min' }}
                        </span>
                    @endif
                    @if($servings)
                        <span class="text-body text-tasty-blue-black/70">
                            {{ $isRtl ? 'ޚިދުމަތް' : 'Serves' }}: {{ $servings }}
                        </span>
                    @endif
                </div>
            </div>
        @endif

        {{-- Two Column Layout --}}
        <div class="flex flex-col lg:flex-row gap-12 lg:gap-16 {{ $isRtl ? 'lg:flex-row-reverse' : '' }}">
            {{-- Left Column: Ingredients + Ad --}}
            <aside class="w-full lg:w-[280px] lg:flex-shrink-0">
                <div class="lg:sticky lg:top-8 space-y-8">
                    <x-recipe.ingredients :ingredients="$ingredients" :isRtl="$isRtl" />

                     @if(!$isPreview)
                        @php
                            $categoryId = $categoryId ?? $post->categories->first()?->id;
                            $adCodeBeforeComments = \App\Models\AdPlacement::getAdForArticleSlot(\App\Models\AdPlacement::SLOT_AFTER_INGREDIENTS, $categoryId);
                        @endphp
                        @if($adCodeBeforeComments)
                            <div class="bg-off-white py-8">
                                <div class="ad-slot flex items-center justify-center">
                                    {!! $adCodeBeforeComments !!}
                                </div>
                            </div>
                        @endif
                    @endif

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
