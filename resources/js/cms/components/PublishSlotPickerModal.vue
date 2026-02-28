<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { useCmsPath } from '../composables/useCmsPath';

interface ManualSlot {
    layoutType: string;
    layoutName: string;
    sectionId: string;
    sectionType: string;
    sectionLabel: string;
    slotIndex: number;
    slotLabel: string;
    currentPostId: number | null;
    currentPostTitle: string | null;
    currentPostStatus: string | null;
    currentPostImage: string | null;
    pageLayoutId: number | null;
}

const { cmsPath } = useCmsPath();

const props = defineProps<{
    open: boolean;
    postId: number;
    postUuid: string;
    versionUuid: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    published: [];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const slots = ref<ManualSlot[]>([]);
const isLoading = ref(false);
const selectedSlot = ref<ManualSlot | null>(null);
const isSubmitting = ref(false);
const submitError = ref<string | null>(null);

// Group slots by layout
const groupedSlots = computed(() => {
    const groups: { key: string; label: string; type: string; slots: ManualSlot[] }[] = [];
    const map = new Map<string, ManualSlot[]>();

    for (const slot of slots.value) {
        const key = `${slot.layoutType}-${slot.pageLayoutId ?? 'homepage'}`;
        if (!map.has(key)) {
            map.set(key, []);
        }
        map.get(key)!.push(slot);
    }

    // Sort: homepage first, then categories, then tags
    const order = { homepage: 0, category: 1, tag: 2 };
    const entries = [...map.entries()].sort((a, b) => {
        const aSlot = a[1][0];
        const bSlot = b[1][0];
        const aOrder = order[aSlot.layoutType as keyof typeof order] ?? 3;
        const bOrder = order[bSlot.layoutType as keyof typeof order] ?? 3;
        if (aOrder !== bOrder) return aOrder - bOrder;
        return aSlot.layoutName.localeCompare(bSlot.layoutName);
    });

    for (const [key, groupSlots] of entries) {
        const first = groupSlots[0];
        groups.push({
            key,
            label: first.layoutType === 'homepage' ? 'Homepage' : first.layoutName,
            type: first.layoutType,
            slots: groupSlots,
        });
    }

    return groups;
});

function isSelected(slot: ManualSlot): boolean {
    return selectedSlot.value?.sectionId === slot.sectionId
        && selectedSlot.value?.slotIndex === slot.slotIndex
        && selectedSlot.value?.layoutType === slot.layoutType
        && selectedSlot.value?.pageLayoutId === slot.pageLayoutId;
}

function toggleSlot(slot: ManualSlot) {
    if (isSelected(slot)) {
        selectedSlot.value = null;
    } else {
        selectedSlot.value = slot;
    }
}

function layoutTypeBadgeColor(type: string): string {
    switch (type) {
        case 'homepage': return 'primary';
        case 'category': return 'info';
        case 'tag': return 'warning';
        default: return 'neutral';
    }
}

// Fetch slots when modal opens
watch(() => props.open, async (open) => {
    if (open) {
        selectedSlot.value = null;
        submitError.value = null;
        isLoading.value = true;
        try {
            const response = await axios.get(cmsPath('/layouts/manual-slots'));
            slots.value = response.data.slots || [];
        } catch {
            slots.value = [];
        } finally {
            isLoading.value = false;
        }
    }
});

async function submit() {
    if (!selectedSlot.value) return;

    isSubmitting.value = true;
    submitError.value = null;

    try {
        await axios.post(cmsPath(`/posts/${props.postUuid}/publish-with-slot`), {
            versionUuid: props.versionUuid,
            sectionId: selectedSlot.value.sectionId,
            slotIndex: selectedSlot.value.slotIndex,
            layoutType: selectedSlot.value.layoutType,
            pageLayoutId: selectedSlot.value.pageLayoutId,
        });

        isOpen.value = false;
        emit('published');
    } catch (error: any) {
        submitError.value = error.response?.data?.message || 'Failed to publish and assign. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <UModal v-model:open="isOpen" :ui="{ width: 'max-w-xl' }">
        <template #content>
            <UCard :ui="{ body: 'p-0' }">
                <!-- Header -->
                <template #header>
                    <div class="flex items-center gap-2">
                        <div class="size-10 rounded-full flex items-center justify-center bg-success/10">
                            <UIcon name="i-lucide-layout-grid" class="size-5 text-success" />
                        </div>
                        <div>
                            <h3 class="font-semibold">Publish & Assign to Slot</h3>
                            <p class="text-sm text-muted">
                                Choose a layout slot to place this post in
                            </p>
                        </div>
                    </div>
                </template>

                <!-- Scrollable slot list -->
                <div class="max-h-[60vh] overflow-y-auto">
                    <!-- Loading -->
                    <div v-if="isLoading" class="py-12 text-center">
                        <UIcon name="i-lucide-loader-2" class="size-8 text-muted animate-spin" />
                        <p class="text-sm text-muted mt-2">Loading available slots...</p>
                    </div>

                    <!-- No slots available -->
                    <div v-else-if="slots.length === 0" class="py-12 text-center">
                        <UIcon name="i-lucide-layout-grid" class="size-8 text-muted" />
                        <p class="text-sm text-muted mt-2">No manual slots configured in any layout</p>
                        <p class="text-xs text-dimmed mt-1">Set slot modes to "Manual" in the layout editor first</p>
                    </div>

                    <!-- Slot groups -->
                    <template v-else>
                        <div v-for="group in groupedSlots" :key="group.key">
                            <!-- Group header -->
                            <div class="flex items-center gap-2 px-4 py-2 bg-elevated/50 border-b border-default">
                                <UBadge :color="layoutTypeBadgeColor(group.type) as any" variant="subtle" size="xs">
                                    {{ group.type === 'homepage' ? 'Homepage' : group.type === 'category' ? 'Category' : 'Tag' }}
                                </UBadge>
                                <span v-if="group.type !== 'homepage'" class="text-sm font-medium text-highlighted">
                                    {{ group.label }}
                                </span>
                            </div>

                            <!-- Slots in group -->
                            <div class="divide-y divide-default">
                                <div
                                    v-for="slot in group.slots"
                                    :key="`${slot.sectionId}-${slot.slotIndex}`"
                                    :class="[
                                        'flex items-center gap-3 px-4 py-3 cursor-pointer transition-colors',
                                        isSelected(slot)
                                            ? 'bg-success/5'
                                            : 'hover:bg-muted/50'
                                    ]"
                                    @click="toggleSlot(slot)"
                                >
                                    <!-- Thumbnail -->
                                    <img
                                        v-if="slot.currentPostImage"
                                        :src="slot.currentPostImage"
                                        :alt="slot.currentPostTitle ?? ''"
                                        class="size-10 rounded-lg object-cover shrink-0"
                                    />
                                    <div
                                        v-else-if="slot.currentPostTitle"
                                        class="size-10 rounded-lg bg-muted/30 flex items-center justify-center shrink-0"
                                    >
                                        <UIcon name="i-lucide-image" class="size-4 text-muted" />
                                    </div>
                                    <div
                                        v-else
                                        class="size-10 rounded-lg border-2 border-dashed border-default flex items-center justify-center shrink-0"
                                    >
                                        <UIcon name="i-lucide-plus" class="size-4 text-dimmed" />
                                    </div>

                                    <!-- Slot info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-medium text-highlighted">{{ slot.sectionLabel }}</span>
                                            <UIcon name="i-lucide-chevron-right" class="size-3 text-muted shrink-0" />
                                            <span class="text-sm text-muted">{{ slot.slotLabel }}</span>
                                        </div>
                                        <p v-if="slot.currentPostTitle" class="text-xs text-dimmed mt-0.5 truncate">
                                            Replaces: {{ slot.currentPostTitle }}
                                        </p>
                                        <p v-else class="text-xs text-dimmed mt-0.5">
                                            Empty slot
                                        </p>
                                    </div>

                                    <!-- Selection indicator -->
                                    <UIcon
                                        v-if="isSelected(slot)"
                                        name="i-lucide-circle-check"
                                        class="size-5 text-success shrink-0"
                                    />
                                    <UIcon
                                        v-else
                                        name="i-lucide-circle"
                                        class="size-5 text-muted/40 shrink-0"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Error -->
                    <div v-if="submitError" class="mx-4 my-3 rounded-md bg-error/10 p-3 text-sm text-error">
                        {{ submitError }}
                    </div>
                </div>

                <!-- Footer -->
                <template #footer>
                    <div class="flex justify-end gap-2">
                        <UButton color="neutral" variant="ghost" @click="isOpen = false">
                            Cancel
                        </UButton>
                        <UButton
                            color="success"
                            :loading="isSubmitting"
                            :disabled="!selectedSlot"
                            @click="submit"
                        >
                            <UIcon name="i-lucide-rocket" class="size-4 mr-1" />
                            Publish &amp; Assign
                        </UButton>
                    </div>
                </template>
            </UCard>
        </template>
    </UModal>
</template>
