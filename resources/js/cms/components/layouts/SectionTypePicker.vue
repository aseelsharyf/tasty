<script setup lang="ts">
import { computed } from 'vue';
import type { SectionTypeDefinition } from '../../types';

const props = defineProps<{
    open: boolean;
    sectionTypes: Record<string, SectionTypeDefinition>;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    select: [type: string];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const sectionTypesList = computed(() => {
    return Object.values(props.sectionTypes);
});

function selectSection(type: string) {
    emit('select', type);
    isOpen.value = false;
}
</script>

<template>
    <UModal v-model:open="isOpen">
        <template #content>
            <UCard :ui="{ body: 'p-6' }">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-highlighted">Add Section</h3>
                        <p class="text-sm text-muted mt-1">Choose a section type to add to your homepage.</p>
                    </div>
                    <UButton
                        icon="i-lucide-x"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        @click="isOpen = false"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3 max-h-[60vh] overflow-y-auto">
                    <button
                        v-for="section in sectionTypesList"
                        :key="section.type"
                        type="button"
                        class="flex items-start gap-3 p-4 rounded-xl border border-default hover:border-primary hover:bg-primary/5 transition-colors text-left"
                        @click="selectSection(section.type)"
                    >
                        <div class="flex items-center justify-center size-10 rounded-lg bg-primary/10 shrink-0">
                            <UIcon :name="section.icon" class="size-5 text-primary" />
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-medium text-highlighted">{{ section.name }}</h4>
                            <p class="text-xs text-muted mt-0.5 line-clamp-2">{{ section.description }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <UBadge v-if="section.slotCount > 0" color="neutral" variant="subtle" size="xs">
                                    {{ section.slotCount }} {{ section.slotCount === 1 ? 'slot' : 'slots' }}
                                </UBadge>
                                <UBadge v-if="section.supportedActions.length === 0" color="neutral" variant="subtle" size="xs">
                                    Static
                                </UBadge>
                            </div>
                        </div>
                    </button>
                </div>
            </UCard>
        </template>
    </UModal>
</template>
