<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { useCmsPath } from '../../composables/useCmsPath';
import type { PostSearchResult } from '../../types';

const { cmsPath } = useCmsPath();

const props = defineProps<{
    open: boolean;
    excludedPostIds?: number[];
    sectionType?: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    select: [post: PostSearchResult];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const searchQuery = ref('');
const isLoading = ref(false);
const posts = ref<PostSearchResult[]>([]);
const error = ref<string | null>(null);

// Check if a post is excluded (already assigned)
function isPostExcluded(postId: number): boolean {
    return props.excludedPostIds?.includes(postId) ?? false;
}

// Debounced search
const debouncedSearch = useDebounceFn(async () => {
    await searchPosts();
}, 300);

watch(searchQuery, () => {
    debouncedSearch();
});

// Load initial posts when modal opens
watch(() => props.open, async (open) => {
    if (open) {
        searchQuery.value = '';
        await searchPosts();
    }
});

async function searchPosts() {
    isLoading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();
        if (searchQuery.value) {
            params.set('query', searchQuery.value);
        }
        params.set('limit', '20');
        if (props.sectionType) {
            params.set('sectionType', props.sectionType);
        }

        const response = await fetch(cmsPath(`/layouts/homepage/search-posts?${params.toString()}`));

        if (!response.ok) {
            throw new Error('Failed to fetch posts');
        }

        const data = await response.json();
        posts.value = data.posts;
    } catch (e) {
        error.value = 'Failed to load posts. Please try again.';
        console.error('Post search error:', e);
    } finally {
        isLoading.value = false;
    }
}

function selectPost(post: PostSearchResult) {
    emit('select', post);
}
</script>

<template>
    <UModal v-model:open="isOpen" :ui="{ width: 'max-w-2xl' }">
        <template #content>
            <UCard :ui="{ body: 'p-0' }">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-default">
                    <div>
                        <h3 class="text-lg font-semibold text-highlighted">Select Post</h3>
                        <p class="text-sm text-muted mt-1">Search and select a post to assign to this slot.</p>
                    </div>
                    <UButton
                        icon="i-lucide-x"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        @click="isOpen = false"
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
                <div class="max-h-[400px] overflow-y-auto">
                    <!-- Loading -->
                    <div v-if="isLoading" class="p-8 text-center">
                        <UIcon name="i-lucide-loader-2" class="size-8 text-muted animate-spin" />
                        <p class="text-sm text-muted mt-2">Loading posts...</p>
                    </div>

                    <!-- Error -->
                    <div v-else-if="error" class="p-8 text-center">
                        <UIcon name="i-lucide-alert-circle" class="size-8 text-error" />
                        <p class="text-sm text-error mt-2">{{ error }}</p>
                        <UButton
                            color="neutral"
                            variant="soft"
                            size="sm"
                            class="mt-4"
                            @click="searchPosts"
                        >
                            Try Again
                        </UButton>
                    </div>

                    <!-- Empty -->
                    <div v-else-if="posts.length === 0" class="p-8 text-center">
                        <UIcon name="i-lucide-file-search" class="size-8 text-muted" />
                        <p class="text-sm text-muted mt-2">No posts found</p>
                    </div>

                    <!-- Posts List -->
                    <div v-else class="divide-y divide-default">
                        <div
                            v-for="post in posts"
                            :key="post.id"
                            :class="[
                                'w-full flex items-center gap-4 p-4 text-left',
                                isPostExcluded(post.id)
                                    ? 'opacity-50 cursor-not-allowed bg-muted/20'
                                    : 'hover:bg-muted/50 transition-colors cursor-pointer'
                            ]"
                            @click="!isPostExcluded(post.id) && selectPost(post)"
                        >
                            <img
                                v-if="post.image"
                                :src="post.image"
                                :alt="post.title"
                                class="size-16 rounded-lg object-cover shrink-0"
                            />
                            <div v-else class="size-16 rounded-lg bg-muted/50 flex items-center justify-center shrink-0">
                                <UIcon name="i-lucide-image" class="size-6 text-muted" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-medium text-highlighted truncate">{{ post.title }}</h4>
                                    <UBadge v-if="isPostExcluded(post.id)" color="warning" variant="subtle" size="xs">
                                        Already assigned
                                    </UBadge>
                                </div>
                                <p v-if="post.excerpt" class="text-sm text-muted line-clamp-1 mt-0.5">
                                    {{ post.excerpt }}
                                </p>
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
                                v-if="!isPostExcluded(post.id)"
                                name="i-lucide-chevron-right"
                                class="size-5 text-muted shrink-0"
                            />
                            <UIcon
                                v-else
                                name="i-lucide-ban"
                                class="size-5 text-warning shrink-0"
                            />
                        </div>
                    </div>
                </div>
            </UCard>
        </template>
    </UModal>
</template>
