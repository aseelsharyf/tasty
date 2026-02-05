{{-- Recipe Ingredients Component --}}
{{-- Renders grouped ingredients with collapsible sections --}}

@props([
    'ingredients' => [],
    'isRtl' => false,
])

<div class="recipe-ingredients">
    {{-- Section Heading --}}
    <h2 class="text-h3 uppercase text-tasty-blue-black mb-6 {{ $isRtl ? 'text-right' : '' }}">
        {{ $isRtl ? 'ބޭނުންވާ ތަކެތި' : 'Ingredients' }}
    </h2>

    {{-- Ingredient Sections --}}
    @if(!empty($ingredients) && is_array($ingredients))
        <div class="space-y-6">
            @foreach($ingredients as $section)
                @if(!empty($section['section']) && !empty($section['items']))
                    <x-blocks.collapsible
                        :title="$section['section']"
                        :defaultExpanded="true"
                        :isRtl="$isRtl"
                    >
                        <ul class="space-y-2">
                            @foreach($section['items'] as $item)
                                <li class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse text-right' : '' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-tasty-blue-black/40 mt-2 flex-shrink-0"></span>
                                    <span class="text-body text-tasty-blue-black {{ $isRtl ? 'font-dhivehi' : '' }}">{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </x-blocks.collapsible>
                @endif
            @endforeach
        </div>
    @else
        <p class="text-body text-tasty-blue-black/60 {{ $isRtl ? 'text-right font-dhivehi' : '' }}">
            {{ $isRtl ? 'ބޭނުންވާ ތަކެތި އެއްވެސް ތަނެއް ނެތް' : 'No ingredients listed.' }}
        </p>
    @endif
</div>
