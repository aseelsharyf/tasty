<script setup lang="ts">
import { ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { useCmsPath } from '../composables/useCmsPath';

const props = defineProps<{
    excludeIds?: number[];
}>();

const emit = defineEmits<{
    (e: 'select', post: any): void;
}>();

const { cmsPath } = useCmsPath();

const query = ref('');
const results = ref<any[]>([]);
const isSearching = ref(false);
const showDropdown = ref(false);

const debouncedSearch = useDebounceFn(async () => {
    if (!query.value.trim()) {
        results.value = [];
        showDropdown.value = false;
        return;
    }

    isSearching.value = true;
    try {
        const params = new URLSearchParams();
        params.set('query', query.value);
        params.set('limit', '10');

        const response = await fetch(cmsPath(`/posts/search?${params.toString()}`));
        if (!response.ok) throw new Error('Failed to search');

        const data = await response.json();
        results.value = (data.posts || []).filter(
            (p: any) => !props.excludeIds?.includes(p.id)
        );
        showDropdown.value = true;
    } catch {
        results.value = [];
    } finally {
        isSearching.value = false;
    }
}, 300);

watch(query, () => {
    debouncedSearch();
});

function selectPost(post: any) {
    emit('select', {
        id: post.id,
        uuid: post.uuid,
        title: post.title,
        slug: post.slug,
        featured_image_thumb: post.image,
        author: null,
        categories: post.category ? [{ id: 0, name: post.category }] : [],
        status: 'published',
    });
    query.value = '';
    results.value = [];
    showDropdown.value = false;
}

function onBlur() {
    // Delay to allow click events on results
    setTimeout(() => {
        showDropdown.value = false;
    }, 200);
}
</script>

<template>
    <div class="relative">
        <UInput
            v-model="query"
            placeholder="Search posts to add..."
            icon="i-lucide-search"
            class="w-full"
            :loading="isSearching"
            @focus="showDropdown = results.length > 0"
            @blur="onBlur"
        />

        <div
            v-if="showDropdown && results.length > 0"
            class="absolute z-50 mt-1 w-full bg-default border border-default rounded-lg shadow-lg max-h-60 overflow-y-auto"
        >
            <button
                v-for="post in results"
                :key="post.id"
                type="button"
                class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-elevated transition-colors"
                @mousedown.prevent="selectPost(post)"
            >
                <img
                    v-if="post.image"
                    :src="post.image"
                    :alt="post.title"
                    class="size-8 rounded object-cover shrink-0"
                >
                <div v-else class="size-8 rounded bg-muted/20 shrink-0 flex items-center justify-center">
                    <UIcon name="i-lucide-image" class="size-3.5 text-muted" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-highlighted truncate">{{ post.title }}</p>
                    <p class="text-xs text-muted">{{ post.category }} &middot; {{ post.publishedAt }}</p>
                </div>
            </button>
        </div>

        <div
            v-if="showDropdown && query && results.length === 0 && !isSearching"
            class="absolute z-50 mt-1 w-full bg-default border border-default rounded-lg shadow-lg p-3"
        >
            <p class="text-sm text-muted text-center">No posts found</p>
        </div>
    </div>
</template>
