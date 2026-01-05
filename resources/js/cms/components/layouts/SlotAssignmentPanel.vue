<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import PostPickerModal from './PostPickerModal.vue';
import type { HomepageSectionSlot, PostSearchResult } from '../../types';

const props = defineProps<{
    modelValue: HomepageSectionSlot[];
    slotCount: number;
    sectionType: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [slots: HomepageSectionSlot[]];
}>();

const slots = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

// Post picker state
const showPostPicker = ref(false);
const editingSlotIndex = ref<number | null>(null);

// Cache for loaded post data
const postCache = ref<Record<number, PostSearchResult>>({});

// Ensure we have the correct number of slots
watch(() => props.slotCount, (count) => {
    if (slots.value.length < count) {
        const newSlots = [...slots.value];
        for (let i = slots.value.length; i < count; i++) {
            newSlots.push({ index: i, mode: 'dynamic', postId: null });
        }
        emit('update:modelValue', newSlots);
    }
}, { immediate: true });

function toggleSlotMode(index: number) {
    const slot = slots.value[index];
    if (slot) {
        const newSlots = [...slots.value];
        newSlots[index] = {
            ...slot,
            mode: slot.mode === 'dynamic' ? 'manual' : 'dynamic',
            postId: null,
        };
        emit('update:modelValue', newSlots);
    }
}

function openPostPicker(index: number) {
    editingSlotIndex.value = index;
    showPostPicker.value = true;
}

function selectPost(post: PostSearchResult) {
    if (editingSlotIndex.value === null) return;

    const newSlots = [...slots.value];
    newSlots[editingSlotIndex.value] = {
        ...newSlots[editingSlotIndex.value],
        mode: 'manual',
        postId: post.id,
    };

    // Cache the post data
    postCache.value[post.id] = post;

    emit('update:modelValue', newSlots);
    showPostPicker.value = false;
    editingSlotIndex.value = null;
}

function clearSlot(index: number) {
    const newSlots = [...slots.value];
    newSlots[index] = {
        ...newSlots[index],
        postId: null,
    };
    emit('update:modelValue', newSlots);
}

function getSlotLabel(index: number): string {
    if (props.sectionType === 'latest-updates') {
        return index === 0 ? 'Featured Post' : `Grid Post ${index}`;
    }
    if (props.sectionType === 'recipe') {
        return index === 0 ? 'Featured Recipe' : `Recipe ${index}`;
    }
    if (props.sectionType === 'add-to-cart') {
        return `Product ${index + 1}`;
    }
    return `Slot ${index + 1}`;
}
</script>

<template>
    <div class="space-y-4">
        <div class="p-4 bg-muted/30 rounded-lg">
            <p class="text-sm text-muted">
                Each slot can be filled dynamically (from the data source) or manually (by selecting a specific post).
            </p>
        </div>

        <div class="space-y-3">
            <div
                v-for="(slot, index) in slots"
                :key="index"
                class="border border-default rounded-lg p-4"
            >
                <div class="flex items-center justify-between mb-3">
                    <span class="font-medium text-highlighted">{{ getSlotLabel(index) }}</span>
                    <UButton
                        :color="slot.mode === 'manual' ? 'primary' : 'neutral'"
                        variant="soft"
                        size="xs"
                        @click="toggleSlotMode(index)"
                    >
                        {{ slot.mode === 'manual' ? 'Manual' : 'Dynamic' }}
                    </UButton>
                </div>

                <!-- Dynamic Mode -->
                <div v-if="slot.mode === 'dynamic'" class="text-sm text-muted">
                    <div class="flex items-center gap-2">
                        <UIcon name="i-lucide-zap" class="size-4" />
                        <span>Automatically filled from data source</span>
                    </div>
                </div>

                <!-- Manual Mode -->
                <div v-else>
                    <!-- No post selected -->
                    <div v-if="!slot.postId" class="flex items-center gap-3">
                        <button
                            type="button"
                            class="flex-1 p-3 border-2 border-dashed border-default rounded-lg text-muted hover:border-primary hover:text-primary transition-colors flex items-center justify-center gap-2"
                            @click="openPostPicker(index)"
                        >
                            <UIcon name="i-lucide-plus" class="size-4" />
                            <span class="text-sm">Select Post</span>
                        </button>
                    </div>

                    <!-- Post selected -->
                    <div v-else class="flex items-center gap-3 p-3 bg-muted/30 rounded-lg">
                        <img
                            v-if="postCache[slot.postId]?.image"
                            :src="postCache[slot.postId]?.image"
                            :alt="postCache[slot.postId]?.title"
                            class="size-12 rounded-lg object-cover shrink-0"
                        />
                        <div v-else class="size-12 rounded-lg bg-muted/50 flex items-center justify-center shrink-0">
                            <UIcon name="i-lucide-image" class="size-5 text-muted" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-highlighted truncate">
                                {{ postCache[slot.postId]?.title || `Post #${slot.postId}` }}
                            </p>
                            <p v-if="postCache[slot.postId]?.category" class="text-xs text-muted">
                                {{ postCache[slot.postId]?.category }}
                            </p>
                        </div>
                        <div class="flex items-center gap-1">
                            <UButton
                                icon="i-lucide-pencil"
                                color="neutral"
                                variant="ghost"
                                size="xs"
                                @click="openPostPicker(index)"
                            />
                            <UButton
                                icon="i-lucide-x"
                                color="neutral"
                                variant="ghost"
                                size="xs"
                                @click="clearSlot(index)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Post Picker Modal -->
        <PostPickerModal
            v-model:open="showPostPicker"
            :section-type="sectionType"
            @select="selectPost"
        />
    </div>
</template>
