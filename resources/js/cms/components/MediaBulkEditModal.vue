<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

interface Tag {
    id: number;
    name: string | Record<string, string>;
    slug: string;
}

interface MediaCategory {
    slug: string;
    label: string;
}

interface MediaItem {
    uuid: string;
    title: string | null;
    thumbnail_url: string | null;
}

const props = defineProps<{
    open: boolean;
    tags: Tag[];
    mediaCategories: MediaCategory[];
    selectedItems: MediaItem[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'updated': [];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

// Form state
const title = ref('');
const caption = ref('');
const category = ref('');
const selectedTags = ref<Array<{ value: number | string; label: string }>>([]);
const tagMode = ref<'replace' | 'append'>('append');

// Loading state
const isSubmitting = ref(false);

// Tag options for multi-select
const tagOptions = computed(() => {
    return props.tags.map(tag => ({
        value: tag.id,
        label: typeof tag.name === 'string' ? tag.name : (tag.name?.en || tag.slug),
    }));
});

// Tag dropdown open state
const tagsDropdownOpen = ref(false);

// Handle creating a new tag
function onCreateTag(item: string) {
    const newTag = {
        value: `new-${Date.now()}`,
        label: item,
    };
    selectedTags.value = [...selectedTags.value, newTag];
    tagsDropdownOpen.value = false;
}

// Available tags (excluding already selected)
const availableTagOptions = computed(() => {
    const selectedValues = new Set(selectedTags.value.map(t => t.value));
    return tagOptions.value.filter(t => !selectedValues.has(t.value));
});

// Add a tag
function addTag(tag: { value: number | string; label: string } | null) {
    if (tag && !selectedTags.value.some(t => t.value === tag.value)) {
        selectedTags.value = [...selectedTags.value, tag];
    }
    tagsDropdownOpen.value = false;
}

// Remove a tag
function removeTag(tagValue: number | string) {
    selectedTags.value = selectedTags.value.filter(t => t.value !== tagValue);
}

// Media category options
const categoryOptions = computed(() => {
    return [
        { value: '', label: 'Keep existing' },
        ...props.mediaCategories.map(cat => ({
            value: cat.slug,
            label: cat.label,
        })),
    ];
});

// Check if form has any changes
const hasChanges = computed(() => {
    return title.value.trim() !== '' ||
           caption.value.trim() !== '' ||
           category.value !== '' ||
           selectedTags.value.length > 0;
});

// Submit bulk update
async function submit() {
    if (!hasChanges.value || isSubmitting.value) return;

    isSubmitting.value = true;

    const tagIds: number[] = [];
    const newTags: string[] = [];

    for (const tag of selectedTags.value) {
        if (typeof tag.value === 'number') {
            tagIds.push(tag.value);
        } else {
            newTags.push(tag.label);
        }
    }

    const data: Record<string, unknown> = {
        ids: props.selectedItems.map(item => item.uuid),
    };

    if (title.value.trim() !== '') {
        data.title = title.value;
    }

    if (caption.value.trim() !== '') {
        data.caption = caption.value;
    }

    if (category.value !== '') {
        data.category = category.value;
    }

    if (tagIds.length > 0 || newTags.length > 0) {
        data.tag_ids = tagIds;
        data.new_tags = newTags;
        data.tag_mode = tagMode.value;
    }

    router.post('/cms/media/bulk-update', data, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            emit('updated');
            isOpen.value = false;
            resetForm();
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
}

function resetForm() {
    title.value = '';
    caption.value = '';
    category.value = '';
    selectedTags.value = [];
    tagMode.value = 'append';
}

// Reset when closed
watch(isOpen, (open) => {
    if (!open) {
        resetForm();
    }
});
</script>

<template>
    <UModal v-model:open="isOpen" :ui="{ width: 'max-w-2xl' }">
        <template #content>
            <div class="flex flex-col bg-default">
                <!-- Header -->
                <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-default">
                    <div class="flex items-center gap-3">
                        <UButton
                            type="button"
                            icon="i-lucide-x"
                            color="neutral"
                            variant="ghost"
                            @click="isOpen = false"
                        />
                        <div>
                            <h2 class="text-lg font-semibold text-highlighted">Bulk Edit</h2>
                            <p class="text-sm text-muted mt-0.5">
                                Update {{ selectedItems.length }} selected item{{ selectedItems.length > 1 ? 's' : '' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-6">
                    <!-- Preview of selected items -->
                    <div class="flex flex-wrap gap-2 pb-4 border-b border-default">
                        <div
                            v-for="item in selectedItems.slice(0, 8)"
                            :key="item.uuid"
                            class="w-12 h-12 rounded-lg overflow-hidden bg-muted/30"
                        >
                            <img
                                v-if="item.thumbnail_url"
                                :src="item.thumbnail_url"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <UIcon name="i-lucide-image" class="size-5 text-muted" />
                            </div>
                        </div>
                        <div
                            v-if="selectedItems.length > 8"
                            class="w-12 h-12 rounded-lg bg-muted/30 flex items-center justify-center text-sm font-medium text-muted"
                        >
                            +{{ selectedItems.length - 8 }}
                        </div>
                    </div>

                    <p class="text-sm text-muted">
                        Only fill in the fields you want to update. Empty fields will be left unchanged.
                    </p>

                    <!-- Title -->
                    <UFormField label="Title">
                        <UInput
                            v-model="title"
                            placeholder="Enter new title (leave empty to keep existing)..."
                            class="w-full"
                        />
                    </UFormField>

                    <!-- Caption -->
                    <UFormField label="Caption">
                        <UTextarea
                            v-model="caption"
                            placeholder="Enter new caption (leave empty to keep existing)..."
                            class="w-full"
                            autoresize
                            :rows="2"
                        />
                    </UFormField>

                    <!-- Category -->
                    <UFormField label="Category">
                        <USelectMenu
                            v-model="category"
                            :items="categoryOptions"
                            value-key="value"
                            class="w-full"
                        />
                    </UFormField>

                    <!-- Tags -->
                    <UFormField label="Tags">
                        <div class="space-y-3">
                            <!-- Tag mode selector -->
                            <div class="flex gap-2">
                                <UButton
                                    type="button"
                                    :color="tagMode === 'append' ? 'primary' : 'neutral'"
                                    :variant="tagMode === 'append' ? 'soft' : 'ghost'"
                                    size="sm"
                                    @click="tagMode = 'append'"
                                >
                                    Add to existing
                                </UButton>
                                <UButton
                                    type="button"
                                    :color="tagMode === 'replace' ? 'primary' : 'neutral'"
                                    :variant="tagMode === 'replace' ? 'soft' : 'ghost'"
                                    size="sm"
                                    @click="tagMode = 'replace'"
                                >
                                    Replace all
                                </UButton>
                            </div>

                            <!-- Selected Tags as pills -->
                            <div v-if="selectedTags.length > 0" class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="tag in selectedTags"
                                    :key="tag.value"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary rounded text-sm font-medium"
                                >
                                    {{ tag.label }}
                                    <button
                                        type="button"
                                        class="hover:text-primary/70 transition-colors"
                                        @click="removeTag(tag.value)"
                                    >
                                        <UIcon name="i-lucide-x" class="size-3" />
                                    </button>
                                </span>
                            </div>

                            <!-- Add Tags Dropdown -->
                            <USelectMenu
                                v-model:open="tagsDropdownOpen"
                                :model-value="null"
                                :items="availableTagOptions"
                                placeholder="Add tags..."
                                create-item
                                class="w-full"
                                @update:model-value="addTag"
                                @create="onCreateTag"
                            />

                            <p v-if="tagMode === 'append'" class="text-xs text-muted">
                                Selected tags will be added to existing tags on each item.
                            </p>
                            <p v-else class="text-xs text-warning">
                                All existing tags will be replaced with the selected tags.
                            </p>
                        </div>
                    </UFormField>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-default">
                    <UButton
                        type="button"
                        color="neutral"
                        variant="outline"
                        @click="isOpen = false"
                    >
                        Cancel
                    </UButton>
                    <UButton
                        type="button"
                        color="primary"
                        icon="i-lucide-save"
                        :loading="isSubmitting"
                        :disabled="!hasChanges || isSubmitting"
                        @click="submit"
                    >
                        Update {{ selectedItems.length }} Item{{ selectedItems.length > 1 ? 's' : '' }}
                    </UButton>
                </div>
            </div>
        </template>
    </UModal>
</template>
