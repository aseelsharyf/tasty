<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useCmsPath } from '../../composables/useCmsPath';

interface TaxonomyItem {
    id: number;
    name: string;
    slug: string;
}

const props = defineProps<{
    action: string;
    params: Record<string, unknown>;
    supportedActions: string[];
    contentType?: string; // 'post' or 'product'
}>();

const emit = defineEmits<{
    'update:dataSource': [value: { action: string; params: Record<string, unknown> }];
}>();

// Tags and categories data
const tags = ref<TaxonomyItem[]>([]);
const categories = ref<TaxonomyItem[]>([]);
const loadingTags = ref(false);
const loadingCategories = ref(false);

const { cmsPath } = useCmsPath();

// Determine if this is a product section
const isProductSection = computed(() => props.contentType === 'product');

const actionOptions = computed(() => {
    // Different labels for posts vs products
    const postLabels: Record<string, string> = {
        recent: 'Recent Posts',
        trending: 'Trending Posts',
        byTag: 'Posts by Tag',
        byCategory: 'Posts by Category',
    };

    const productLabels: Record<string, string> = {
        recent: 'Recent Products',
        trending: 'Trending Products',
        byTag: 'Products by Tag',
        byCategory: 'Products by Category',
    };

    const labels = isProductSection.value ? productLabels : postLabels;

    return props.supportedActions.map(action => ({
        label: labels[action] || action,
        value: action,
    }));
});

// Fetch tags from API
async function fetchTags() {
    if (tags.value.length > 0) return;
    loadingTags.value = true;
    try {
        const response = await fetch(cmsPath('/layouts/homepage/tags'));
        const data = await response.json();
        tags.value = data.tags || [];
    } catch (error) {
        console.error('Failed to fetch tags:', error);
    } finally {
        loadingTags.value = false;
    }
}

// Fetch categories from API
async function fetchCategories() {
    if (categories.value.length > 0) return;
    loadingCategories.value = true;
    try {
        const response = await fetch(cmsPath('/layouts/homepage/categories'));
        const data = await response.json();
        categories.value = data.categories || [];
    } catch (error) {
        console.error('Failed to fetch categories:', error);
    } finally {
        loadingCategories.value = false;
    }
}

// Load data when action changes
watch(() => props.action, (newAction) => {
    if (newAction === 'byTag') {
        fetchTags();
    } else if (newAction === 'byCategory') {
        fetchCategories();
    }
}, { immediate: true });

function onActionChange(value: string) {
    // Reset params when action changes
    emit('update:dataSource', { action: value, params: {} });
}

function onSlugsChange(slugs: string[]) {
    emit('update:dataSource', {
        action: props.action,
        params: { ...props.params, slugs }
    });
}

// Get current slugs array from params
const selectedSlugs = computed(() => {
    const slugs = props.params?.slugs;
    if (Array.isArray(slugs)) return slugs as string[];
    // Support legacy single slug
    if (props.params?.slug) return [props.params.slug as string];
    return [];
});

// Format items for select menu
const tagOptions = computed(() =>
    tags.value.map(t => ({ label: t.name, value: t.slug }))
);

const categoryOptions = computed(() =>
    categories.value.map(c => ({ label: c.name, value: c.slug }))
);
</script>

<template>
    <div class="space-y-4">
        <!-- Action Selector -->
        <UFormField label="Data Source" help="How should this section fetch its content?">
            <USelectMenu
                :model-value="action"
                :items="actionOptions"
                value-key="value"
                class="w-full"
                @update:model-value="onActionChange"
            />
        </UFormField>

        <!-- Tag Selector (Multiple) -->
        <UFormField
            v-if="action === 'byTag'"
            label="Select Tags"
            help="Choose one or more tags to filter posts"
        >
            <USelectMenu
                :model-value="selectedSlugs"
                :items="tagOptions"
                value-key="value"
                multiple
                searchable
                :search-attributes="['label']"
                placeholder="Search tags..."
                :loading="loadingTags"
                class="w-full"
                @update:model-value="onSlugsChange"
            >
                <template #empty>
                    <div class="p-2 text-sm text-muted">
                        {{ loadingTags ? 'Loading tags...' : 'No tags found' }}
                    </div>
                </template>
            </USelectMenu>
        </UFormField>

        <!-- Category Selector (Multiple) -->
        <UFormField
            v-else-if="action === 'byCategory'"
            label="Select Categories"
            help="Choose one or more categories to filter posts"
        >
            <USelectMenu
                :model-value="selectedSlugs"
                :items="categoryOptions"
                value-key="value"
                multiple
                searchable
                :search-attributes="['label']"
                placeholder="Search categories..."
                :loading="loadingCategories"
                class="w-full"
                @update:model-value="onSlugsChange"
            >
                <template #empty>
                    <div class="p-2 text-sm text-muted">
                        {{ loadingCategories ? 'Loading categories...' : 'No categories found' }}
                    </div>
                </template>
            </USelectMenu>
        </UFormField>

        <!-- Info box -->
        <div class="p-3 bg-muted/30 rounded-lg">
            <div class="flex gap-2">
                <UIcon name="i-lucide-info" class="size-4 text-muted shrink-0 mt-0.5" />
                <p class="text-xs text-muted">
                    <template v-if="action === 'recent'">
                        Shows the most recently {{ isProductSection ? 'added products' : 'published posts' }}, sorted by {{ isProductSection ? 'creation' : 'publication' }} date.
                    </template>
                    <template v-else-if="action === 'trending'">
                        Shows {{ isProductSection ? 'products' : 'posts' }} based on engagement metrics and popularity.
                    </template>
                    <template v-else-if="action === 'byTag'">
                        Shows {{ isProductSection ? 'products' : 'posts' }} that have any of the selected tags.
                    </template>
                    <template v-else-if="action === 'byCategory'">
                        Shows {{ isProductSection ? 'products' : 'posts' }} from any of the selected categories.
                    </template>
                </p>
            </div>
        </div>
    </div>
</template>
