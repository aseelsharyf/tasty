<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useCmsPath } from '../../composables/useCmsPath';

interface LayoutItem {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    sectionsCount?: number;
    updatedAt?: string;
}

interface BasicItem {
    id: number;
    uuid: string;
    name: string;
    slug: string;
}

const props = defineProps<{
    categoriesWithLayouts: LayoutItem[];
    tagsWithLayouts: LayoutItem[];
    allCategories: BasicItem[];
    allTags: BasicItem[];
}>();

const { cmsPath } = useCmsPath();

// Modal state
const showCreateModal = ref(false);
const selectedType = ref<'category' | 'tag' | null>(null);
const searchQuery = ref('');
const selectedItem = ref<BasicItem | null>(null);

// Filter options based on search
const filteredOptions = computed(() => {
    if (!selectedType.value) return [];

    const items = selectedType.value === 'category' ? props.allCategories : props.allTags;
    if (!searchQuery.value) return items;

    const query = searchQuery.value.toLowerCase();
    return items.filter(item =>
        item.name.toLowerCase().includes(query) || item.slug.toLowerCase().includes(query)
    );
});

// Check if item already has a layout
const configuredCategoryUuids = computed(() =>
    new Set(props.categoriesWithLayouts.map(c => c.uuid))
);

const configuredTagUuids = computed(() =>
    new Set(props.tagsWithLayouts.map(t => t.uuid))
);

function hasExistingLayout(type: 'category' | 'tag', uuid: string): boolean {
    if (type === 'category') {
        return configuredCategoryUuids.value.has(uuid);
    }
    return configuredTagUuids.value.has(uuid);
}

// Reset search when type changes
watch(selectedType, () => {
    searchQuery.value = '';
    selectedItem.value = null;
});

// Reset modal state when closed
watch(showCreateModal, (open) => {
    if (!open) {
        selectedType.value = null;
        searchQuery.value = '';
        selectedItem.value = null;
    }
});

function selectItem(item: BasicItem) {
    selectedItem.value = item;
}

function goToLayout() {
    if (!selectedItem.value || !selectedType.value) return;

    const path = selectedType.value === 'category'
        ? cmsPath(`/layouts/categories/${selectedItem.value.uuid}`)
        : cmsPath(`/layouts/tags/${selectedItem.value.uuid}`);

    showCreateModal.value = false;
    router.visit(path);
}

const totalConfigured = computed(() =>
    props.categoriesWithLayouts.length + props.tagsWithLayouts.length
);
</script>

<template>
    <Head title="Layouts" />

    <DashboardLayout>
        <UDashboardPanel id="layouts-index">
            <template #header>
                <UDashboardNavbar title="Layouts">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <UButton
                            icon="i-lucide-plus"
                            @click="showCreateModal = true"
                        >
                            Configure Layout
                        </UButton>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex flex-col gap-6 max-w-5xl mx-auto py-6 px-4 lg:px-6">
                    <!-- Header -->
                    <div>
                        <h1 class="text-xl font-semibold text-highlighted">Page Layouts</h1>
                        <p class="text-sm text-muted mt-1">
                            Manage custom layouts for categories and tags. Pages without custom layouts use the default template.
                        </p>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="totalConfigured === 0"
                        class="flex flex-col items-center justify-center py-16 text-center"
                    >
                        <div class="flex items-center justify-center size-16 rounded-full bg-primary/10 mb-4">
                            <UIcon name="i-lucide-layout-template" class="size-8 text-primary" />
                        </div>
                        <h3 class="text-lg font-medium text-highlighted">No custom layouts yet</h3>
                        <p class="text-sm text-muted mt-1 mb-4 max-w-md">
                            Create custom layouts for your category and tag pages to display content in a unique way.
                        </p>
                        <UButton
                            icon="i-lucide-plus"
                            @click="showCreateModal = true"
                        >
                            Configure First Layout
                        </UButton>
                    </div>

                    <!-- Categories with layouts -->
                    <div v-if="categoriesWithLayouts.length > 0">
                        <h2 class="text-sm font-medium text-highlighted mb-3 flex items-center gap-2">
                            <UIcon name="i-lucide-folder" class="size-4" />
                            Categories
                            <UBadge color="primary" variant="subtle" size="xs">
                                {{ categoriesWithLayouts.length }}
                            </UBadge>
                        </h2>

                        <div class="grid gap-3">
                            <Link
                                v-for="category in categoriesWithLayouts"
                                :key="category.uuid"
                                :href="cmsPath(`/layouts/categories/${category.uuid}`)"
                                class="flex items-center justify-between p-4 rounded-lg border border-default hover:border-primary hover:bg-elevated/50 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center size-10 rounded-lg bg-primary/10">
                                        <UIcon name="i-lucide-folder" class="size-5 text-primary" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-highlighted">{{ category.name }}</div>
                                        <div class="text-xs text-muted">{{ category.slug }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <div class="text-sm text-muted">{{ category.sectionsCount }} sections</div>
                                        <div v-if="category.updatedAt" class="text-xs text-muted">Updated {{ category.updatedAt }}</div>
                                    </div>
                                    <UIcon name="i-lucide-chevron-right" class="size-5 text-muted" />
                                </div>
                            </Link>
                        </div>
                    </div>

                    <!-- Tags with layouts -->
                    <div v-if="tagsWithLayouts.length > 0">
                        <h2 class="text-sm font-medium text-highlighted mb-3 flex items-center gap-2">
                            <UIcon name="i-lucide-tag" class="size-4" />
                            Tags
                            <UBadge color="primary" variant="subtle" size="xs">
                                {{ tagsWithLayouts.length }}
                            </UBadge>
                        </h2>

                        <div class="grid gap-3">
                            <Link
                                v-for="tag in tagsWithLayouts"
                                :key="tag.uuid"
                                :href="cmsPath(`/layouts/tags/${tag.uuid}`)"
                                class="flex items-center justify-between p-4 rounded-lg border border-default hover:border-primary hover:bg-elevated/50 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center size-10 rounded-lg bg-success/10">
                                        <UIcon name="i-lucide-tag" class="size-5 text-success" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-highlighted">{{ tag.name }}</div>
                                        <div class="text-xs text-muted">{{ tag.slug }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <div class="text-sm text-muted">{{ tag.sectionsCount }} sections</div>
                                        <div v-if="tag.updatedAt" class="text-xs text-muted">Updated {{ tag.updatedAt }}</div>
                                    </div>
                                    <UIcon name="i-lucide-chevron-right" class="size-5 text-muted" />
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Configure Layout Modal -->
        <UModal v-model:open="showCreateModal">
            <template #content>
                <UCard>
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-highlighted">Configure Layout</h3>
                            <UButton
                                icon="i-lucide-x"
                                color="neutral"
                                variant="ghost"
                                size="sm"
                                @click="showCreateModal = false"
                            />
                        </div>
                    </template>

                    <div class="space-y-4">
                        <!-- Step 1: Choose Type -->
                        <div v-if="!selectedType">
                            <p class="text-sm text-muted mb-4">
                                Choose what type of page you want to configure a layout for:
                            </p>
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    class="flex flex-col items-center gap-3 p-6 rounded-lg border-2 border-default hover:border-primary hover:bg-elevated/50 transition-colors"
                                    @click="selectedType = 'category'"
                                >
                                    <div class="flex items-center justify-center size-12 rounded-full bg-primary/10">
                                        <UIcon name="i-lucide-folder" class="size-6 text-primary" />
                                    </div>
                                    <span class="font-medium text-highlighted">Category</span>
                                </button>
                                <button
                                    type="button"
                                    class="flex flex-col items-center gap-3 p-6 rounded-lg border-2 border-default hover:border-success hover:bg-elevated/50 transition-colors"
                                    @click="selectedType = 'tag'"
                                >
                                    <div class="flex items-center justify-center size-12 rounded-full bg-success/10">
                                        <UIcon name="i-lucide-tag" class="size-6 text-success" />
                                    </div>
                                    <span class="font-medium text-highlighted">Tag</span>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Select Item -->
                        <div v-else>
                            <div class="flex items-center gap-2 mb-4">
                                <UButton
                                    icon="i-lucide-arrow-left"
                                    color="neutral"
                                    variant="ghost"
                                    size="xs"
                                    @click="selectedType = null"
                                />
                                <span class="text-sm text-muted">
                                    Select a {{ selectedType }}:
                                </span>
                            </div>

                            <UInput
                                v-model="searchQuery"
                                :placeholder="`Search ${selectedType === 'category' ? 'categories' : 'tags'}...`"
                                icon="i-lucide-search"
                                class="mb-3"
                            />

                            <div class="max-h-64 overflow-y-auto space-y-1">
                                <button
                                    v-for="item in filteredOptions"
                                    :key="item.uuid"
                                    type="button"
                                    class="w-full flex items-center justify-between p-3 rounded-lg border transition-colors"
                                    :class="[
                                        selectedItem?.uuid === item.uuid
                                            ? 'border-primary bg-primary/5'
                                            : 'border-transparent hover:bg-elevated/50'
                                    ]"
                                    @click="selectItem(item)"
                                >
                                    <div class="flex items-center gap-3">
                                        <UIcon
                                            :name="selectedType === 'category' ? 'i-lucide-folder' : 'i-lucide-tag'"
                                            class="size-4 text-muted"
                                        />
                                        <div class="text-left">
                                            <div class="font-medium text-highlighted">{{ item.name }}</div>
                                            <div class="text-xs text-muted">{{ item.slug }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <UBadge
                                            v-if="hasExistingLayout(selectedType, item.uuid)"
                                            color="success"
                                            variant="subtle"
                                            size="xs"
                                        >
                                            Has Layout
                                        </UBadge>
                                        <UIcon
                                            v-if="selectedItem?.uuid === item.uuid"
                                            name="i-lucide-check"
                                            class="size-4 text-primary"
                                        />
                                    </div>
                                </button>

                                <div v-if="filteredOptions.length === 0" class="text-center py-8 text-muted">
                                    <UIcon name="i-lucide-search" class="size-8 mx-auto mb-2 opacity-50" />
                                    <p class="text-sm">No {{ selectedType === 'category' ? 'categories' : 'tags' }} found</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <template #footer>
                        <div class="flex justify-end gap-2">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                @click="showCreateModal = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                :disabled="!selectedItem"
                                @click="goToLayout"
                            >
                                {{ hasExistingLayout(selectedType!, selectedItem?.uuid ?? '') ? 'Edit Layout' : 'Configure Layout' }}
                            </UButton>
                        </div>
                    </template>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
