<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import { DEFAULT_BLOCK_ORDER, BLOCK_REGISTRY_META, type BlockRegistryEntry } from './BlockEditor.vue';
import { useCmsPath } from '../composables/useCmsPath';

interface BlockItem {
    key: string;
    label: string;
    icon: string;
    enabled: boolean;
}

const props = defineProps<{
    open: boolean;
    currentOrder?: string[] | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'saved', order: string[]): void;
}>();

const { cmsPath } = useCmsPath();
const saving = ref(false);

// Build the block list from current order or defaults
function buildBlockList(order?: string[] | null): BlockItem[] {
    const metaMap = new Map<string, BlockRegistryEntry>();
    for (const entry of BLOCK_REGISTRY_META) {
        metaMap.set(entry.key, entry);
    }

    const items: BlockItem[] = [];

    // First add blocks in the specified order (enabled)
    const orderedKeys = order ?? DEFAULT_BLOCK_ORDER;
    for (const key of orderedKeys) {
        const meta = metaMap.get(key);
        if (meta) {
            // paragraph (Text) is always enabled — it's a built-in EditorJS block
            items.push({ key: meta.key, label: meta.label, icon: meta.icon, enabled: true });
            metaMap.delete(key);
        }
    }

    // Then add any remaining blocks as disabled (not in order)
    for (const [, meta] of metaMap) {
        // paragraph is always enabled even if not in the saved order
        const alwaysEnabled = meta.key === 'paragraph';
        items.push({ key: meta.key, label: meta.label, icon: meta.icon, enabled: alwaysEnabled });
    }

    return items;
}

const blocks = ref<BlockItem[]>(buildBlockList(props.currentOrder));

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        blocks.value = buildBlockList(props.currentOrder);
    }
});

function resetToDefault(): void {
    blocks.value = buildBlockList(DEFAULT_BLOCK_ORDER);
}

function getEnabledOrder(): string[] {
    return blocks.value.filter(b => b.enabled).map(b => b.key);
}

function save(): void {
    const order = getEnabledOrder();
    if (order.length === 0) return;

    saving.value = true;
    router.put(cmsPath('/profile/editor-preferences'), {
        editor_block_order: order,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved', order);
            emit('update:open', false);
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}
</script>

<template>
    <UModal
        :open="open"
        title="Block Editor Settings"
        description="Drag to reorder blocks. Toggle to show or hide blocks in the editor toolbar."
        @update:open="emit('update:open', $event)"
    >
        <template #body>
            <draggable
                v-model="blocks"
                item-key="key"
                handle=".drag-handle"
                ghost-class="opacity-50"
                :animation="150"
            >
                <template #item="{ element }">
                    <div
                        class="flex items-center gap-3 px-3 py-2.5 rounded-[var(--ui-radius)] border border-[var(--ui-border)] mb-2 transition-colors"
                        :class="element.enabled ? 'bg-[var(--ui-bg)]' : 'bg-[var(--ui-bg-muted)] opacity-60'"
                    >
                        <div class="drag-handle cursor-grab active:cursor-grabbing text-[var(--ui-text-dimmed)] hover:text-[var(--ui-text-muted)]">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                        </div>
                        <UIcon :name="element.icon" class="size-4 text-[var(--ui-text-muted)] shrink-0" />
                        <span class="flex-1 text-sm" :class="element.enabled ? 'text-[var(--ui-text-highlighted)]' : 'text-[var(--ui-text-muted)]'">
                            {{ element.label }}
                        </span>
                        <USwitch
                            v-model="element.enabled"
                            size="sm"
                            :disabled="element.key === 'paragraph'"
                        />
                    </div>
                </template>
            </draggable>
        </template>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <UButton
                    variant="ghost"
                    color="neutral"
                    size="sm"
                    @click="resetToDefault"
                >
                    Reset to Default
                </UButton>
                <div class="flex gap-2">
                    <UButton
                        variant="outline"
                        color="neutral"
                        size="sm"
                        @click="emit('update:open', false)"
                    >
                        Cancel
                    </UButton>
                    <UButton
                        size="sm"
                        :loading="saving"
                        :disabled="getEnabledOrder().length === 0"
                        @click="save"
                    >
                        Save
                    </UButton>
                </div>
            </div>
        </template>
    </UModal>
</template>
