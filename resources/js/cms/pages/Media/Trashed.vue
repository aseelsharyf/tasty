<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import { formatDistanceToNow } from 'date-fns';

interface MediaItem {
    id: number;
    uuid: string;
    type: 'image' | 'video_local' | 'video_embed';
    url: string | null;
    thumbnail_url: string | null;
    title: string | null;
    is_image: boolean;
    is_video: boolean;
    folder: {
        id: number;
        name: string;
    } | null;
    uploaded_by: {
        id: number;
        name: string;
    } | null;
    created_at: string;
    deleted_at: string;
}

interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    media: PaginatedResponse<MediaItem>;
    filters: {
        search?: string;
    };
}>();

const { can } = usePermission();

const search = ref(props.filters.search || '');
const selectedItems = ref<Set<string>>(new Set());

function applySearch() {
    router.get('/cms/media/trashed', {
        search: search.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearSearch() {
    search.value = '';
    applySearch();
}

// Selection management
function toggleSelect(uuid: string) {
    if (selectedItems.value.has(uuid)) {
        selectedItems.value.delete(uuid);
    } else {
        selectedItems.value.add(uuid);
    }
    selectedItems.value = new Set(selectedItems.value);
}

function selectAll() {
    if (selectedItems.value.size === props.media.data.length) {
        selectedItems.value.clear();
    } else {
        selectedItems.value = new Set(props.media.data.map(m => m.uuid));
    }
    selectedItems.value = new Set(selectedItems.value);
}

function clearSelection() {
    selectedItems.value.clear();
    selectedItems.value = new Set();
}

const selectedUuids = computed(() => Array.from(selectedItems.value));
const selectedCount = computed(() => selectedItems.value.size);

function restoreItem(item: MediaItem) {
    router.post(`/cms/media/${item.uuid}/restore`, {}, {
        preserveScroll: true,
    });
}

function forceDeleteItem(item: MediaItem) {
    if (confirm('Permanently delete this item? This cannot be undone.')) {
        router.delete(`/cms/media/${item.uuid}/force`, {
            preserveScroll: true,
        });
    }
}

function bulkAction(action: 'restore' | 'force_delete') {
    if (selectedUuids.value.length === 0) return;

    if (action === 'force_delete' && !confirm(`Permanently delete ${selectedUuids.value.length} items? This cannot be undone.`)) {
        return;
    }

    router.post('/cms/media/bulk', {
        action,
        ids: selectedUuids.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            clearSelection();
        },
    });
}

function getRowActions(item: MediaItem) {
    const actions: any[][] = [];

    if (can('media.delete')) {
        actions.push([
            {
                label: 'Restore',
                icon: 'i-lucide-undo',
                onSelect: () => restoreItem(item),
            },
        ]);

        actions.push([
            {
                label: 'Delete Permanently',
                icon: 'i-lucide-trash-2',
                color: 'error' as const,
                onSelect: () => forceDeleteItem(item),
            },
        ]);
    }

    return actions;
}
</script>

<template>
    <Head title="Trashed Media" />

    <DashboardLayout>
        <UDashboardPanel id="media-trashed">
            <template #header>
                <UDashboardNavbar title="Trashed Media">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <UButton
                            color="neutral"
                            variant="soft"
                            icon="i-lucide-arrow-left"
                            to="/cms/media"
                        >
                            Back to Media
                        </UButton>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <UInput
                        v-model="search"
                        placeholder="Search trashed media..."
                        icon="i-lucide-search"
                        :ui="{ base: 'w-64' }"
                        @keyup.enter="applySearch"
                    />
                    <UButton
                        v-if="search"
                        color="neutral"
                        variant="ghost"
                        icon="i-lucide-x"
                        size="sm"
                        @click="clearSearch"
                    />

                    <span class="ml-auto text-sm text-muted">
                        {{ media.total }} trashed item{{ media.total !== 1 ? 's' : '' }}
                    </span>
                </div>

                <!-- Bulk Actions Bar -->
                <div
                    v-if="selectedCount > 0"
                    class="flex items-center justify-between gap-3 p-3 mb-4 rounded-lg bg-elevated border border-default"
                >
                    <div class="flex items-center gap-2">
                        <UIcon name="i-lucide-check-square" class="size-5 text-primary" />
                        <span class="text-sm font-medium">
                            {{ selectedCount }} {{ selectedCount === 1 ? 'item' : 'items' }} selected
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <UButton
                            color="neutral"
                            variant="ghost"
                            size="sm"
                            @click="clearSelection"
                        >
                            Clear
                        </UButton>
                        <UButton
                            color="success"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-undo"
                            @click="bulkAction('restore')"
                        >
                            Restore
                        </UButton>
                        <UButton
                            color="error"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-trash-2"
                            @click="bulkAction('force_delete')"
                        >
                            Delete Permanently
                        </UButton>
                    </div>
                </div>

                <!-- Select All -->
                <div v-if="media.data.length > 0" class="flex items-center gap-2 mb-4">
                    <UCheckbox
                        :model-value="selectedCount === media.data.length && media.data.length > 0"
                        :indeterminate="selectedCount > 0 && selectedCount < media.data.length"
                        @update:model-value="selectAll"
                    />
                    <span class="text-sm text-muted">Select all on this page</span>
                </div>

                <!-- Media Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                    <div
                        v-for="item in media.data"
                        :key="item.uuid"
                        class="group relative rounded-lg border border-default bg-default/50 overflow-hidden opacity-75 hover:opacity-100 transition-opacity"
                        :class="{ 'ring-2 ring-primary opacity-100': selectedItems.has(item.uuid) }"
                    >
                        <!-- Thumbnail -->
                        <div class="aspect-square relative bg-muted/30">
                            <img
                                v-if="item.thumbnail_url"
                                :src="item.thumbnail_url"
                                :alt="item.title || 'Media'"
                                class="w-full h-full object-cover grayscale"
                            />
                            <div
                                v-else
                                class="w-full h-full flex items-center justify-center"
                            >
                                <UIcon
                                    :name="item.is_video ? 'i-lucide-video' : 'i-lucide-image'"
                                    class="size-12 text-muted"
                                />
                            </div>

                            <!-- Trashed Overlay -->
                            <div class="absolute inset-0 bg-black/10 flex items-center justify-center">
                                <UIcon name="i-lucide-trash" class="size-8 text-white/50" />
                            </div>

                            <!-- Selection Checkbox -->
                            <div
                                class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                :class="{ 'opacity-100': selectedItems.has(item.uuid) }"
                            >
                                <UCheckbox
                                    :model-value="selectedItems.has(item.uuid)"
                                    @click.stop
                                    @update:model-value="toggleSelect(item.uuid)"
                                />
                            </div>

                            <!-- Actions Overlay -->
                            <div class="absolute bottom-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
                                <UButton
                                    icon="i-lucide-undo"
                                    color="success"
                                    variant="solid"
                                    size="xs"
                                    title="Restore"
                                    @click.stop="restoreItem(item)"
                                />
                                <UDropdownMenu
                                    :items="getRowActions(item)"
                                    :content="{ align: 'end' }"
                                >
                                    <UButton
                                        icon="i-lucide-ellipsis"
                                        color="neutral"
                                        variant="solid"
                                        size="xs"
                                        @click.stop
                                    />
                                </UDropdownMenu>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="p-2">
                            <p class="text-sm font-medium text-highlighted truncate">
                                {{ item.title || 'Untitled' }}
                            </p>
                            <p class="text-xs text-muted mt-1">
                                Deleted {{ formatDistanceToNow(new Date(item.deleted_at), { addSuffix: true }) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="media.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-trash-2" class="size-12 mx-auto mb-4 opacity-50" />
                    <p v-if="search">No trashed media found matching "{{ search }}"</p>
                    <p v-else>No trashed media.</p>
                    <UButton
                        color="neutral"
                        variant="soft"
                        icon="i-lucide-arrow-left"
                        class="mt-4"
                        to="/cms/media"
                    >
                        Back to Media Library
                    </UButton>
                </div>

                <!-- Pagination -->
                <div v-if="media.last_page > 1" class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-4">
                    <div class="text-sm text-muted">
                        Showing {{ (media.current_page - 1) * media.per_page + 1 }} to
                        {{ Math.min(media.current_page * media.per_page, media.total) }} of
                        {{ media.total }}
                    </div>
                    <UPagination
                        :page="media.current_page"
                        :total="media.total"
                        :items-per-page="media.per_page"
                        @update:page="(page) => router.get('/cms/media/trashed', { ...filters, page }, { preserveState: true })"
                    />
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
