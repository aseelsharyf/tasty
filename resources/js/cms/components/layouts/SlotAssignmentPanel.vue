<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import PostPickerModal from './PostPickerModal.vue';
import ProductPickerModal from './ProductPickerModal.vue';
import type { HomepageSectionSlot, PostSearchResult, ProductSearchResult } from '../../types';

const props = defineProps<{
    modelValue: HomepageSectionSlot[];
    slotCount: number;
    minSlots?: number;
    maxSlots?: number;
    sectionType: string;
    contentType?: string; // 'post' or 'product'
}>();

const emit = defineEmits<{
    'update:modelValue': [slots: HomepageSectionSlot[]];
}>();

const slots = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

// Compute actual min/max (fallback to slotCount if not provided)
const minSlotCount = computed(() => props.minSlots ?? props.slotCount);
const maxSlotCount = computed(() => props.maxSlots ?? 0); // 0 = unlimited

// Can add/remove slots?
const canAddSlot = computed(() => {
    if (maxSlotCount.value === 0) return true; // unlimited
    return slots.value.length < maxSlotCount.value;
});

const canRemoveSlot = computed(() => {
    return slots.value.length > minSlotCount.value;
});

// Determine if this section uses products instead of posts
const isProductSection = computed(() => props.contentType === 'product');

// Post picker state
const showPostPicker = ref(false);
const showProductPicker = ref(false);
const editingSlotIndex = ref<number | null>(null);

// Cache for loaded post/product data
const postCache = ref<Record<number, PostSearchResult>>({});
const productCache = ref<Record<number, ProductSearchResult>>({});

// Ensure we have at least minimum slots on mount
watch(() => minSlotCount.value, (minCount) => {
    if (slots.value.length < minCount) {
        const newSlots = [...slots.value];
        for (let i = slots.value.length; i < minCount; i++) {
            newSlots.push({
                index: i,
                mode: 'dynamic',
                postId: null,
                productId: null,
            });
        }
        emit('update:modelValue', newSlots);
    }
}, { immediate: true });

function addSlot() {
    if (!canAddSlot.value) return;

    const newSlots = [...slots.value];
    newSlots.push({
        index: newSlots.length,
        mode: 'dynamic',
        postId: null,
        productId: null,
    });
    emit('update:modelValue', newSlots);
}

function removeSlot(index: number) {
    if (!canRemoveSlot.value) return;

    const newSlots = slots.value.filter((_, i) => i !== index);
    // Re-index slots
    newSlots.forEach((slot, i) => {
        slot.index = i;
    });
    emit('update:modelValue', newSlots);
}

function toggleSlotMode(index: number) {
    const slot = slots.value[index];
    if (slot) {
        const newSlots = [...slots.value];
        newSlots[index] = {
            ...slot,
            mode: slot.mode === 'dynamic' ? 'manual' : 'dynamic',
            postId: null,
            productId: null,
        };
        emit('update:modelValue', newSlots);
    }
}

function openPicker(index: number) {
    editingSlotIndex.value = index;
    if (isProductSection.value) {
        showProductPicker.value = true;
    } else {
        showPostPicker.value = true;
    }
}

function selectPost(post: PostSearchResult) {
    if (editingSlotIndex.value === null) return;

    const newSlots = [...slots.value];
    newSlots[editingSlotIndex.value] = {
        ...newSlots[editingSlotIndex.value],
        mode: 'manual',
        postId: post.id,
        productId: null,
    };

    // Cache the post data
    postCache.value[post.id] = post;

    emit('update:modelValue', newSlots);
    showPostPicker.value = false;
    editingSlotIndex.value = null;
}

function selectProduct(product: ProductSearchResult) {
    if (editingSlotIndex.value === null) return;

    const newSlots = [...slots.value];
    newSlots[editingSlotIndex.value] = {
        ...newSlots[editingSlotIndex.value],
        mode: 'manual',
        postId: null,
        productId: product.id,
    };

    // Cache the product data
    productCache.value[product.id] = product;

    emit('update:modelValue', newSlots);
    showProductPicker.value = false;
    editingSlotIndex.value = null;
}

function clearSlot(index: number) {
    const newSlots = [...slots.value];
    newSlots[index] = {
        ...newSlots[index],
        postId: null,
        productId: null,
    };
    emit('update:modelValue', newSlots);
}

// Get the ID for display (postId or productId depending on section type)
function getSlotItemId(slot: HomepageSectionSlot): number | null {
    return isProductSection.value ? slot.productId ?? null : slot.postId ?? null;
}

// Get cached item data
function getCachedItem(slot: HomepageSectionSlot) {
    const itemId = getSlotItemId(slot);
    if (!itemId) return null;
    return isProductSection.value ? productCache.value[itemId] : postCache.value[itemId];
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
        <!-- Header with slot count info -->
        <div class="flex items-center justify-between">
            <p class="text-sm text-muted">
                {{ slots.length }} {{ slots.length === 1 ? 'slot' : 'slots' }}
                <span v-if="maxSlotCount > 0" class="text-muted/70">(max {{ maxSlotCount }})</span>
            </p>
            <UButton
                v-if="canAddSlot"
                icon="i-lucide-plus"
                color="primary"
                variant="soft"
                size="xs"
                @click="addSlot"
            >
                Add Slot
            </UButton>
        </div>

        <div class="space-y-3">
            <div
                v-for="(slot, index) in slots"
                :key="index"
                class="border border-default rounded-lg p-4"
            >
                <div class="flex items-center justify-between mb-3">
                    <span class="font-medium text-highlighted">{{ getSlotLabel(index) }}</span>
                    <div class="flex items-center gap-2">
                        <UButton
                            :color="slot.mode === 'manual' ? 'primary' : 'neutral'"
                            variant="soft"
                            size="xs"
                            @click="toggleSlotMode(index)"
                        >
                            {{ slot.mode === 'manual' ? 'Manual' : 'Dynamic' }}
                        </UButton>
                        <UButton
                            v-if="canRemoveSlot"
                            icon="i-lucide-trash-2"
                            color="error"
                            variant="ghost"
                            size="xs"
                            @click="removeSlot(index)"
                        />
                    </div>
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
                    <!-- No item selected -->
                    <div v-if="!getSlotItemId(slot)" class="flex items-center gap-3">
                        <button
                            type="button"
                            class="flex-1 p-3 border-2 border-dashed border-default rounded-lg text-muted hover:border-primary hover:text-primary transition-colors flex items-center justify-center gap-2"
                            @click="openPicker(index)"
                        >
                            <UIcon name="i-lucide-plus" class="size-4" />
                            <span class="text-sm">Select {{ isProductSection ? 'Product' : 'Post' }}</span>
                        </button>
                    </div>

                    <!-- Item selected -->
                    <div v-else class="flex items-center gap-3 p-3 bg-muted/30 rounded-lg">
                        <img
                            v-if="getCachedItem(slot)?.image"
                            :src="getCachedItem(slot)?.image"
                            :alt="getCachedItem(slot)?.title"
                            class="size-12 rounded-lg object-cover shrink-0"
                        />
                        <div v-else class="size-12 rounded-lg bg-muted/50 flex items-center justify-center shrink-0">
                            <UIcon :name="isProductSection ? 'i-lucide-package' : 'i-lucide-image'" class="size-5 text-muted" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-highlighted truncate">
                                {{ getCachedItem(slot)?.title || (isProductSection ? `Product #${getSlotItemId(slot)}` : `Post #${getSlotItemId(slot)}`) }}
                            </p>
                            <p v-if="getCachedItem(slot)?.category" class="text-xs text-muted">
                                {{ getCachedItem(slot)?.category }}
                            </p>
                            <p v-if="isProductSection && getCachedItem(slot)?.price" class="text-xs text-primary font-medium">
                                {{ getCachedItem(slot)?.price }}
                            </p>
                        </div>
                        <div class="flex items-center gap-1">
                            <UButton
                                icon="i-lucide-pencil"
                                color="neutral"
                                variant="ghost"
                                size="xs"
                                @click="openPicker(index)"
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

        <!-- Product Picker Modal -->
        <ProductPickerModal
            v-model:open="showProductPicker"
            @select="selectProduct"
        />
    </div>
</template>
