{{-- Custom Fields Aside - Renders post type specific fields --}}
@if($postTypeConfig && !empty($postTypeConfig['fields']) && !empty($post['custom_fields']))
    @php
        $fields = $postTypeConfig['fields'];
        $values = $post['custom_fields'];
        $hasValues = collect($fields)->some(fn($field) => !empty($values[$field['name']] ?? null));
    @endphp

    @if($hasValues)
        <aside class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-5 mb-8 {{ $isRtl ? 'text-right' : '' }}">
            {{-- Post Type Label --}}
            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ $postTypeConfig['name'] ?? 'Details' }}
                </span>
            </div>

            <dl class="space-y-3">
                @foreach($fields as $field)
                    @php
                        $value = $values[$field['name']] ?? null;
                        $fieldType = $field['type'] ?? 'text';
                    @endphp

                    @if(!empty($value))
                        <div class="flex flex-col gap-0.5">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ $field['label'] }}
                            </dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">
                                @switch($fieldType)
                                    @case('number')
                                        <span class="font-medium">{{ $value }}</span>
                                        @if($field['suffix'] ?? null)
                                            <span class="text-gray-500 dark:text-gray-400">{{ $field['suffix'] }}</span>
                                        @endif
                                        @break

                                    @case('toggle')
                                        @if($value)
                                            <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Yes
                                            </span>
                                        @else
                                            <span class="text-gray-400">No</span>
                                        @endif
                                        @break

                                    @case('textarea')
                                        <p class="whitespace-pre-line">{{ $value }}</p>
                                        @break

                                    @case('repeater')
                                        @if(is_array($value) && count($value) > 0)
                                            <ul class="list-disc list-inside space-y-1">
                                                @foreach($value as $item)
                                                    <li>{{ is_array($item) ? ($item['value'] ?? json_encode($item)) : $item }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @break

                                    @case('grouped-repeater')
                                        @if(is_array($value) && count($value) > 0)
                                            <div class="space-y-0 -mx-5 mt-2">
                                                @foreach($value as $section)
                                                    @if(!empty($section['section']) && !empty($section['items']))
                                                        <x-blocks.collapsible
                                                            :title="$section['section']"
                                                            :defaultExpanded="true"
                                                            :isRtl="$isRtl"
                                                        >
                                                            <ul class="space-y-2">
                                                                @foreach($section['items'] as $item)
                                                                    <li class="flex items-start gap-2">
                                                                        <span class="w-1.5 h-1.5 rounded-full bg-tasty-blue-black/40 mt-2 flex-shrink-0"></span>
                                                                        <span class="text-body">{{ $item }}</span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </x-blocks.collapsible>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                        @break

                                    @case('select')
                                        <span class="inline-block px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-medium">
                                            {{ $value }}
                                        </span>
                                        @break

                                    @default
                                        {{ $value }}
                                @endswitch
                            </dd>
                        </div>
                    @endif
                @endforeach
            </dl>
        </aside>
    @endif
@endif
