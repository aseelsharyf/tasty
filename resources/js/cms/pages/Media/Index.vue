<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import MediaEditSlideover from '../../components/MediaEditSlideover.vue';
import MediaUploadSlideover from '../../components/MediaUploadSlideover.vue';
import { usePermission } from '../../composables/usePermission';
import type { NavigationMenuItem } from '@nuxt/ui';
import { formatDistanceToNow } from 'date-fns';

interface MediaFolder {
    id: number;
    uuid: string;
    name: string;
    children?: MediaFolder[];
}

interface Tag {
    id: number;
    name: string | Record<string, string>;
    slug: string;
}

interface MediaItem {
    id: number;
    uuid: string;
    type: 'image' | 'video_local' | 'video_embed';
    url: string | null;
    thumbnail_url: string | null;
    embed_url: string | null;
    embed_provider: string | null;
    title: string | null;
    title_translations: Record<string, string>;
    caption: string | null;
    caption_translations: Record<string, string>;
    description: string | null;
    description_translations: Record<string, string>;
    alt_text: string | null;
    alt_text_translations: Record<string, string>;
    credit_user_id: number | null;
    credit_name: string | null;
    credit_url: string | null;
    credit_role: string | null;
    credit_display: {
        name: string;
        url: string | null;
        role: string | null;
        is_user: boolean;
        user_id: number | null;
    } | null;
    width: number | null;
    height: number | null;
    file_size: number | null;
    mime_type: string | null;
    is_image: boolean;
    is_video: boolean;
    folder: {
        id: number;
        uuid: string;
        name: string;
        path: string;
    } | null;
    folder_id: number | null;
    tags: Tag[];
    tag_ids: number[];
    uploaded_by: {
        id: number;
        name: string;
    } | null;
    created_at: string;
    updated_at: string;
}

interface Language {
    id: number;
    code: string;
    name: string;
    native_name: string;
    direction: 'ltr' | 'rtl';
    is_default: boolean;
}

interface User {
    id: number;
    name: string;
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
    counts: {
        all: number;
        images: number;
        videos: number;
        trashed: number;
    };
    folders: MediaFolder[];
    tags: Tag[];
    languages: Language[];
    users: User[];
    creditRoles: Record<string, string>;
    filters: {
        type?: string;
        folder?: string;
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
        tags?: number[];
    };
}>();

const { can } = usePermission();

const search = ref(props.filters.search || '');
const selectedType = ref(props.filters.type || '');
const selectedTagIds = ref<number[]>(props.filters.tags || []);

// Slidevers
const editSlideoverOpen = ref(false);
const uploadSlideoverOpen = ref(false);
const selectedMedia = ref<MediaItem | null>(null);

// Selection
const selectedItems = ref<Set<string>>(new Set());

// Navigation menu items for type tabs
const typeLinks = computed<NavigationMenuItem[][]>(() => [[
    {
        label: 'All Media',
        icon: 'i-lucide-grid-3x3',
        badge: props.counts.all,
        to: '/cms/media',
        active: !selectedType.value,
        onSelect: () => changeType(''),
    },
    {
        label: 'Images',
        icon: 'i-lucide-image',
        badge: props.counts.images,
        to: '/cms/media?type=images',
        active: selectedType.value === 'images',
        onSelect: () => changeType('images'),
    },
    {
        label: 'Videos',
        icon: 'i-lucide-video',
        badge: props.counts.videos,
        to: '/cms/media?type=videos',
        active: selectedType.value === 'videos',
        onSelect: () => changeType('videos'),
    },
    {
        label: 'Trashed',
        icon: 'i-lucide-trash',
        badge: props.counts.trashed,
        to: '/cms/media/trashed',
        active: false,
    },
]]);

// Tag options for filter
const tagOptions = computed(() => {
    return props.tags.map(tag => ({
        value: tag.id,
        label: typeof tag.name === 'string' ? tag.name : (tag.name?.en || tag.slug),
    }));
});

const debouncedSearch = useDebounceFn(() => {
    applyFilters();
}, 300);

watch(search, () => {
    debouncedSearch();
});

function changeType(type: string) {
    selectedType.value = type;
    applyFilters();
}

function applyFilters() {
    router.get('/cms/media', {
        type: selectedType.value || undefined,
        search: search.value || undefined,
        tags: selectedTagIds.value.length > 0 ? selectedTagIds.value : undefined,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearSearch() {
    search.value = '';
    applyFilters();
}

function clearFilters() {
    selectedType.value = '';
    selectedTagIds.value = [];
    search.value = '';
    router.get('/cms/media', {}, { preserveState: true, replace: true });
}

function openMedia(item: MediaItem) {
    selectedMedia.value = item;
    editSlideoverOpen.value = true;
}

function openUpload() {
    uploadSlideoverOpen.value = true;
}

function deleteMedia(item: MediaItem) {
    if (confirm('Move this item to trash?')) {
        router.delete(`/cms/media/${item.uuid}`, {
            preserveScroll: true,
        });
    }
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

function bulkAction(action: 'move' | 'delete', folderId?: number | null) {
    if (selectedUuids.value.length === 0) return;

    if (action === 'delete' && !confirm(`Move ${selectedUuids.value.length} items to trash?`)) {
        return;
    }

    router.post('/cms/media/bulk', {
        action,
        ids: selectedUuids.value,
        folder_id: folderId,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            clearSelection();
        },
    });
}

// Format file size
function formatFileSize(bytes: number | null): string {
    if (!bytes) return '';
    const units = ['B', 'KB', 'MB', 'GB'];
    let unitIndex = 0;
    let size = bytes;
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    return `${size.toFixed(1)} ${units[unitIndex]}`;
}

// Get row actions
function getRowActions(item: MediaItem) {
    const actions: any[][] = [];

    actions.push([
        {
            label: 'View / Edit',
            icon: 'i-lucide-eye',
            onSelect: () => openMedia(item),
        },
    ]);

    if (can('media.delete')) {
        actions.push([
            {
                label: 'Delete',
                icon: 'i-lucide-trash',
                color: 'error' as const,
                onSelect: () => deleteMedia(item),
            },
        ]);
    }

    return actions;
}
</script>

<template>
    <Head title="Media Library" />

    <DashboardLayout>
        <UDashboardPanel id="media">
            <template #header>
                <UDashboardNavbar title="Media Library">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <UButton
                            v-if="can('media.upload')"
                            color="primary"
                            icon="i-lucide-upload"
                            @click="openUpload"
                        >
                            Upload
                        </UButton>
                    </template>
                </UDashboardNavbar>

                <UDashboardToolbar>
                    <UNavigationMenu :items="typeLinks" highlight class="-mx-1 flex-1" />
                </UDashboardToolbar>
            </template>

            <template #body>
                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <UInput
                        v-model="search"
                        placeholder="Search media or tags..."
                        icon="i-lucide-search"
                        :ui="{ base: 'w-64' }"
                    />
                    <UButton
                        v-if="search"
                        color="neutral"
                        variant="ghost"
                        icon="i-lucide-x"
                        size="sm"
                        @click="clearSearch"
                    />

                    <USelectMenu
                        v-model="selectedTagIds"
                        :items="tagOptions"
                        value-key="value"
                        placeholder="Filter by tags..."
                        multiple
                        searchable
                        class="w-56"
                        @update:model-value="applyFilters"
                    />

                    <UButton
                        v-if="search || selectedType || selectedTagIds.length > 0"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        @click="clearFilters"
                    >
                        Clear Filters
                    </UButton>

                    <span class="ml-auto text-sm text-muted">
                        {{ media.total }} item{{ media.total !== 1 ? 's' : '' }}
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
                            color="error"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-trash"
                            @click="bulkAction('delete')"
                        >
                            Delete
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
                        class="group relative rounded-lg border border-default bg-default/50 overflow-hidden hover:border-primary/50 transition-colors cursor-pointer"
                        :class="{ 'ring-2 ring-primary': selectedItems.has(item.uuid) }"
                        @click="openMedia(item)"
                    >
                        <!-- Thumbnail -->
                        <div class="aspect-square relative bg-muted/30">
                            <img
                                v-if="item.thumbnail_url"
                                :src="item.thumbnail_url"
                                :alt="item.title || 'Media'"
                                class="w-full h-full object-cover"
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

                            <!-- Video Overlay -->
                            <div
                                v-if="item.is_video"
                                class="absolute inset-0 flex items-center justify-center bg-black/20"
                            >
                                <div class="rounded-full bg-black/50 p-2">
                                    <UIcon name="i-lucide-play" class="size-6 text-white" />
                                </div>
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

                            <!-- Type Badge -->
                            <div class="absolute top-2 right-2">
                                <UBadge
                                    :color="item.is_video ? 'primary' : 'neutral'"
                                    variant="solid"
                                    size="xs"
                                >
                                    {{ item.is_video ? (item.embed_provider || 'Video') : 'Image' }}
                                </UBadge>
                            </div>

                            <!-- Actions Overlay -->
                            <div class="absolute bottom-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
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
                            <div class="flex items-center gap-2 text-xs text-muted mt-1">
                                <span v-if="item.file_size">{{ formatFileSize(item.file_size) }}</span>
                                <span v-if="item.width && item.height">{{ item.width }}x{{ item.height }}</span>
                            </div>
                            <p v-if="item.credit_display" class="text-xs text-muted mt-1 truncate">
                                {{ item.credit_display.name }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="media.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-image-off" class="size-12 mx-auto mb-4 opacity-50" />
                    <p v-if="search">No media found matching "{{ search }}"</p>
                    <p v-else-if="selectedTagIds.length > 0">No media found with the selected tags.</p>
                    <p v-else-if="selectedType === 'images'">No images uploaded yet.</p>
                    <p v-else-if="selectedType === 'videos'">No videos uploaded yet.</p>
                    <p v-else>No media uploaded yet.</p>
                    <UButton
                        v-if="can('media.upload')"
                        color="primary"
                        variant="soft"
                        icon="i-lucide-upload"
                        class="mt-4"
                        @click="openUpload"
                    >
                        Upload Media
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
                        @update:page="(page) => router.get('/cms/media', { ...filters, page }, { preserveState: true })"
                    />
                </div>
            </template>
        </UDashboardPanel>

        <!-- Upload Slideover -->
        <MediaUploadSlideover
            v-model:open="uploadSlideoverOpen"
            :tags="tags"
            :languages="languages"
            :users="users"
            :credit-roles="creditRoles"
        />

        <!-- Edit Slideover -->
        <MediaEditSlideover
            v-model:open="editSlideoverOpen"
            :media="selectedMedia"
            :tags="tags"
            :languages="languages"
            :users="users"
            :credit-roles="creditRoles"
            @delete="deleteMedia"
        />
    </DashboardLayout>
</template>
