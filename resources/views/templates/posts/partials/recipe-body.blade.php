{{-- Recipe Body Layout --}}
{{-- Two-column layout: Ingredients (left) | Preparation (right) --}}

@props([
    'post',
    'contentBlocks' => [],
    'isRtl' => false,
])

@php
    $ingredients = $post->custom_fields['ingredients'] ?? [];
    $postTypeConfig = $post->getCustomFieldDefinitions();
@endphp

<div class="w-full bg-off-white py-16">
    <div class="max-w-[1200px] mx-auto px-4 lg:px-8">
        {{-- Recipe Meta (prep time, cook time, servings, difficulty) --}}
        @if($postTypeConfig && !empty($post->custom_fields))
            @php
                $metaFields = collect($postTypeConfig['fields'] ?? [])->filter(fn($f) => $f['name'] !== 'ingredients');
                $hasMetaValues = $metaFields->some(fn($f) => !empty($post->custom_fields[$f['name']] ?? null));
            @endphp

            @if($hasMetaValues)
                <div class="flex flex-wrap items-center gap-6 mb-12 pb-8 border-b border-tasty-blue-black/10 {{ $isRtl ? 'justify-end' : '' }}">
                    @foreach($metaFields as $field)
                        @php $value = $post->custom_fields[$field['name']] ?? null; @endphp
                        @if(!empty($value))
                            <div class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                @switch($field['name'])
                                    @case('prep_time')
                                        <svg class="w-5 h-5 text-tasty-blue-black/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        @break
                                    @case('cook_time')
                                        <svg class="w-5 h-5 text-tasty-blue-black/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                        </svg>
                                        @break
                                    @case('servings')
                                        <svg class="w-5 h-5 text-tasty-blue-black/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        @break
                                    @case('difficulty')
                                        <svg class="w-5 h-5 text-tasty-blue-black/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        @break
                                @endswitch
                                <div class="{{ $isRtl ? 'text-right' : '' }}">
                                    <span class="text-xs uppercase tracking-wide text-tasty-blue-black/50">{{ $field['label'] }}</span>
                                    <p class="text-sm font-medium text-tasty-blue-black">
                                        {{ $value }}@if($field['suffix'] ?? null) {{ $field['suffix'] }}@endif
                                    </p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
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
