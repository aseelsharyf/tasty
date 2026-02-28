<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { useDebounceFn } from '@vueuse/core';
import { useCmsPath } from '../composables/useCmsPath';
import type { PostSearchResult } from '../types';

interface SlotUsage {
    layoutType: string;
    layoutName: string;
    sectionId: string;
    sectionType: string;
    sectionLabel: string;
    slotIndex: number;
    pageLayoutId?: number;
}

const { cmsPath } = useCmsPath();

const props = defineProps<{
    open: boolean;
    postId: number;
    postUuid: string;
    postTitle: string;
    usages: SlotUsage[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    unpublished: [];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

// Track the selected replacement for each usage (keyed by "sectionId-slotIndex")
const replacements = ref<Record<string, PostSearchResult | null>>({});

// Per-slot picker state
const activePickerKey = ref<string | null>(null);
const pickerOpen = computed({
    get: () => activePickerKey.value !== null,
    set: (value) => { if (!value) activePickerKey.value = null; },
});
const searchQuery = ref('');
const searchResults = ref<PostSearchResult[]>([]);
const isSearching = ref(false);
const isSubmitting = ref(false);
const submitError = ref<string | null>(null);

function usageKey(usage: SlotUsage): string {
    return `${usage.sectionId}-${usage.slotIndex}`;
}

// All slots have a replacement selected
const allReplacementsSelected = computed(() => {
    return props.usages.every((usage) => {
        const key = usageKey(usage);
        return replacements.value[key] != null;
    });
});

function layoutTypeLabel(type: string): string {
    switch (type) {
        case 'homepage': return 'Homepage';
        case 'category': return 'Category';
        case 'tag': return 'Tag';
        default: return type;
    }
}

// Search posts
const debouncedSearch = useDebounceFn(async () => {
    await searchPosts();
}, 300);

watch(searchQuery, () => {
    debouncedSearch();
});

async function searchPosts() {
    isSearching.value = true;
    try {
        const params = new URLSearchParams();
        if (searchQuery.value) {
            params.set('query', searchQuery.value);
        }
        params.set('limit', '20');
        params.set('manual', '1');

        const response = await fetch(cmsPath(`/layouts/homepage/search-posts?${params.toString()}`));
        if (!response.ok) throw new Error('Failed to fetch posts');
        const data = await response.json();
        searchResults.value = data.posts;
    } catch {
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
}

function openPicker(usage: SlotUsage) {
    const key = usageKey(usage);
    activePickerKey.value = key;
    searchQuery.value = '';
    searchResults.value = [];
    searchPosts();
}

function closePicker() {
    activePickerKey.value = null;
}

function selectReplacement(post: PostSearchResult) {
    if (activePickerKey.value) {
        replacements.value[activePickerKey.value] = post;
    }
    closePicker();
}

function removeReplacement(usage: SlotUsage) {
    const key = usageKey(usage);
    replacements.value[key] = null;
}

// Reset state when modal opens
watch(() => props.open, (open) => {
    if (open) {
        replacements.value = {};
        activePickerKey.value = null;
        searchQuery.value = '';
        searchResults.value = [];
        submitError.value = null;
    }
});

async function submit() {
    isSubmitting.value = true;
    submitError.value = null;

    try {
        const payload = props.usages.map((usage) => {
            const key = usageKey(usage);
            const replacement = replacements.value[key]!;
            return {
                layoutType: usage.layoutType,
                sectionId: usage.sectionId,
                slotIndex: usage.slotIndex,
                newPostId: replacement.id,
                pageLayoutId: usage.pageLayoutId ?? null,
            };
        });

        await axios.post(cmsPath(`/posts/${props.postUuid}/unpublish-with-replacements`), {
            replacements: payload,
        });

        isOpen.value = false;
        emit('unpublished');
    } catch (error: any) {
        submitError.value = error.response?.data?.message || 'Failed to unpublish. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <UModal v-model:open="isOpen" :ui="{ width: 'max-w-2xl' }">
        <template #content>
            <UCard :ui="{ body: 'p-0' }">
                <!-- Header -->
                <template #header>
                    <div class="flex items-center gap-2">
                        <div class="size-10 rounded-full flex items-center justify-center bg-warning/10">
                            <UIcon name="i-lucide-replace" class="size-5 text-warning" />
                        </div>
                        <div>
                            <h3 class="font-semibold">Replace Before Unpublishing</h3>
                            <p class="text-sm text-muted">
                                "{{ postTitle }}" is used in {{ usages.length }} layout slot{{ usages.length > 1 ? 's' : '' }}
                            </p>
                        </div>
                    </div>
                </template>

                <div class="p-4 space-y-3">
                    <p class="text-sm text-muted">
                        Choose a replacement post for each slot before unpublishing.
                    </p>

                    <!-- Slot usages list -->
                    <div class="space-y-3">
                        <div
                            v-for="usage in usages"
                            :key="usageKey(usage)"
                            class="rounded-lg border border-default p-3"
                        >
                            <!-- Slot info -->
                            <div class="flex items-center gap-2 mb-2">
                                <UBadge :color="usage.layoutType === 'homepage' ? 'primary' : 'neutral'" variant="subtle" size="xs">
                                    {{ layoutTypeLabel(usage.layoutType) }}
                                </UBadge>
                                <span v-if="usage.layoutType !== 'homepage'" class="text-sm font-medium text-highlighted">
                                    {{ usage.layoutName }}
                                </span>
                                <UIcon name="i-lucide-chevron-right" class="size-3.5 text-muted" />
                                <span class="text-sm text-muted">{{ usage.sectionLabel }}</span>
                                <span class="text-xs text-dimmed">(Slot {{ usage.slotIndex + 1 }})</span>
                            </div>

                            <!-- Selected replacement or pick button -->
                            <div v-if="replacements[usageKey(usage)]" class="flex items-center gap-3 bg-muted/30 rounded-md p-2">
                                <img
                                    v-if="replacements[usageKey(usage)]!.image"
                                    :src="replacements[usageKey(usage)]!.image"
                                    :alt="replacements[usageKey(usage)]!.title"
                                    class="size-10 rounded object-cover shrink-0"
                                />
                                <div v-else class="size-10 rounded bg-muted/50 flex items-center justify-center shrink-0">
                                    <UIcon name="i-lucide-image" class="size-4 text-muted" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-highlighted truncate">
                                        {{ replacements[usageKey(usage)]!.title }}
                                    </p>
                                    <p v-if="replacements[usageKey(usage)]!.category" class="text-xs text-muted">
                                        {{ replacements[usageKey(usage)]!.category }}
                                    </p>
                                </div>
                                <UButton
                                    icon="i-lucide-x"
                                    color="neutral"
                                    variant="ghost"
                                    size="xs"
                                    @click="removeReplacement(usage)"
                                />
                            </div>
                            <UButton
                                v-else
                                color="neutral"
                                variant="soft"
                                size="sm"
                                icon="i-lucide-search"
                                class="w-full"
                                @click="openPicker(usage)"
                            >
                                Select replacement post
                            </UButton>
                        </div>
                    </div>

                    <!-- Error -->
                    <div v-if="submitError" class="rounded-md bg-error/10 p-3 text-sm text-error">
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
                            color="error"
                            :loading="isSubmitting"
                            :disabled="!allReplacementsSelected"
                            @click="submit"
                        >
                            <UIcon name="i-lucide-globe-lock" class="size-4 mr-1" />
                            Replace &amp; Unpublish
                        </UButton>
                    </div>
                </template>
            </UCard>
        </template>
    </UModal>

    <!-- Post Picker Sub-Modal -->
    <UModal v-model:open="pickerOpen" :ui="{ width: 'max-w-xl' }">
        <template #content>
            <UCard :ui="{ body: 'p-0' }">
                <div class="flex items-center justify-between p-4 border-b border-default">
                    <div>
                        <h3 class="text-lg font-semibold text-highlighted">Select Replacement Post</h3>
                        <p class="text-sm text-muted mt-1">Choose a published post to replace the current one.</p>
                    </div>
                    <UButton
                        icon="i-lucide-x"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        @click="closePicker"
                    />
                </div>

                <!-- Search -->
                <div class="p-4 border-b border-default">
                    <UInput
                        v-model="searchQuery"
                        placeholder="Search posts..."
                        icon="i-lucide-search"
                        class="w-full"
                        autofocus
                    />
                </div>

                <!-- Results -->
                <div class="max-h-[350px] overflow-y-auto">
                    <div v-if="isSearching" class="p-8 text-center">
                        <UIcon name="i-lucide-loader-2" class="size-8 text-muted animate-spin" />
                        <p class="text-sm text-muted mt-2">Loading posts...</p>
                    </div>

                    <div v-else-if="searchResults.length === 0" class="p-8 text-center">
                        <UIcon name="i-lucide-file-search" class="size-8 text-muted" />
                        <p class="text-sm text-muted mt-2">No posts found</p>
                    </div>

                    <div v-else class="divide-y divide-default">
                        <div
                            v-for="post in searchResults"
                            :key="post.id"
                            :class="[
                                'w-full flex items-center gap-4 p-4 text-left',
                                post.id === postId
                                    ? 'opacity-50 cursor-not-allowed bg-muted/20'
                                    : 'hover:bg-muted/50 transition-colors cursor-pointer'
                            ]"
                            @click="post.id !== postId && selectReplacement(post)"
                        >
                            <img
                                v-if="post.image"
                                :src="post.image"
                                :alt="post.title"
                                class="size-12 rounded-lg object-cover shrink-0"
                            />
                            <div v-else class="size-12 rounded-lg bg-muted/50 flex items-center justify-center shrink-0">
                                <UIcon name="i-lucide-image" class="size-5 text-muted" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-medium text-highlighted truncate">{{ post.title }}</h4>
                                    <UBadge v-if="post.id === postId" color="warning" variant="subtle" size="xs">
                                        Current post
                                    </UBadge>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <UBadge v-if="post.category" color="neutral" variant="subtle" size="xs">
                                        {{ post.category }}
                                    </UBadge>
                                    <span v-if="post.publishedAt" class="text-xs text-muted">
                                        {{ post.publishedAt }}
                                    </span>
                                </div>
                            </div>
                            <UIcon
                                v-if="post.id !== postId"
                                name="i-lucide-chevron-right"
                                class="size-5 text-muted shrink-0"
                            />
                        </div>
                    </div>
                </div>
            </UCard>
        </template>
    </UModal>
</template>
