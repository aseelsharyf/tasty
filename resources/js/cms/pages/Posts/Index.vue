<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, h, resolveComponent, computed, onMounted } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import type { Post, PostCounts, PostFilters, Author, Category, PaginatedResponse } from '../../types';
import type { TableColumn, NavigationMenuItem } from '@nuxt/ui';

interface LanguageInfo {
    code: string;
    name: string;
    native_name: string;
    direction: 'ltr' | 'rtl';
    is_rtl: boolean;
}

const props = defineProps<{
    posts: PaginatedResponse<Post>;
    counts: PostCounts;
    authors: Author[];
    categories: Category[];
    filters: PostFilters;
    language: LanguageInfo;
}>();

const { can } = usePermission();

// Current language from props
const currentLanguageCode = computed(() => props.language.code);
const isRtlLanguage = computed(() => props.language.is_rtl);

function createNewPost() {
    router.visit(`/cms/posts/${currentLanguageCode.value}/create`);
}

const UAvatar = resolveComponent('UAvatar');
const UBadge = resolveComponent('UBadge');
const UButton = resolveComponent('UButton');
const UDropdownMenu = resolveComponent('UDropdownMenu');

const search = ref(props.filters.search || '');
const currentStatus = ref(props.filters.status || 'all');
const selectedPostType = ref<string | null>(props.filters.post_type || null);
const selectedAuthor = ref<string | null>(props.filters.author?.toString() || null);
const selectedCategory = ref<string | null>(props.filters.category?.toString() || null);
const deleteModalOpen = ref(false);
const postToDelete = ref<Post | null>(null);

// Navigation menu items for status tabs
const statusLinks = computed<NavigationMenuItem[][]>(() => [[
    {
        label: 'All',
        icon: 'i-lucide-layout-list',
        badge: props.counts.all,
        to: `/cms/posts/${currentLanguageCode.value}`,
        active: currentStatus.value === 'all',
        onSelect: () => changeStatus('all'),
    },
    {
        label: 'Drafts',
        icon: 'i-lucide-file-edit',
        badge: props.counts.draft,
        to: `/cms/posts/${currentLanguageCode.value}?status=draft`,
        active: currentStatus.value === 'draft',
        onSelect: () => changeStatus('draft'),
    },
    {
        label: 'Pending',
        icon: 'i-lucide-clock',
        badge: props.counts.pending,
        to: `/cms/posts/${currentLanguageCode.value}?status=pending`,
        active: currentStatus.value === 'pending',
        onSelect: () => changeStatus('pending'),
    },
    {
        label: 'Published',
        icon: 'i-lucide-globe',
        badge: props.counts.published,
        to: `/cms/posts/${currentLanguageCode.value}?status=published`,
        active: currentStatus.value === 'published',
        onSelect: () => changeStatus('published'),
    },
    {
        label: 'Scheduled',
        icon: 'i-lucide-calendar-clock',
        badge: props.counts.scheduled,
        to: `/cms/posts/${currentLanguageCode.value}?status=scheduled`,
        active: currentStatus.value === 'scheduled',
        onSelect: () => changeStatus('scheduled'),
    },
    {
        label: 'Trash',
        icon: 'i-lucide-trash',
        badge: props.counts.trashed,
        to: `/cms/posts/${currentLanguageCode.value}?status=trashed`,
        active: currentStatus.value === 'trashed',
        onSelect: () => changeStatus('trashed'),
    },
]]);

const postTypeOptions = [
    { label: 'All Types', value: null },
    { label: 'Articles', value: 'article' },
    { label: 'Recipes', value: 'recipe' },
];

const authorOptions = computed(() => [
    { label: 'All Authors', value: null },
    ...props.authors.map((a) => ({ label: a.name, value: a.id.toString() })),
]);

const categoryOptions = computed(() => [
    { label: 'All Categories', value: null },
    ...props.categories.map((c) => ({ label: c.name, value: c.id.toString() })),
]);

function applyFilters(overrides: Record<string, any> = {}) {
    router.get(`/cms/posts/${currentLanguageCode.value}`, {
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
        search: search.value || undefined,
        post_type: selectedPostType.value ?? undefined,
        author: selectedAuthor.value ?? undefined,
        category: selectedCategory.value ?? undefined,
        sort: props.filters.sort,
        direction: props.filters.direction,
        ...overrides,
    }, {
        preserveState: true,
        replace: true,
    });
}

const debouncedSearch = useDebounceFn(() => {
    applyFilters();
}, 300);

watch(search, () => {
    debouncedSearch();
});

watch([selectedPostType, selectedAuthor, selectedCategory], () => {
    applyFilters();
});

function changeStatus(status: string) {
    currentStatus.value = status;
    applyFilters({ status: status !== 'all' ? status : undefined });
}

function sortBy(field: string) {
    const currentSort = props.filters.sort || 'created_at';
    const currentDirection = props.filters.direction || 'desc';

    let newDirection: 'asc' | 'desc' = 'asc';
    if (currentSort === field) {
        newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
    }

    applyFilters({
        sort: field,
        direction: newDirection,
    });
}

function getSortIcon(field: string) {
    const currentSort = props.filters.sort || 'created_at';
    const currentDirection = props.filters.direction || 'desc';

    if (currentSort !== field) {
        return 'i-lucide-arrow-up-down';
    }
    return currentDirection === 'asc' ? 'i-lucide-arrow-up-narrow-wide' : 'i-lucide-arrow-down-wide-narrow';
}

function confirmDelete(post: Post) {
    postToDelete.value = post;
    deleteModalOpen.value = true;
}

function deletePost() {
    if (postToDelete.value) {
        const langCode = postToDelete.value.language_code || currentLanguageCode.value;
        router.delete(`/cms/posts/${langCode}/${postToDelete.value.uuid}`, {
            onSuccess: () => {
                deleteModalOpen.value = false;
                postToDelete.value = null;
            },
        });
    }
}

function restorePost(post: Post) {
    const langCode = post.language_code || currentLanguageCode.value;
    router.post(`/cms/posts/${langCode}/${post.uuid}/restore`);
}

function forceDeletePost(post: Post) {
    const langCode = post.language_code || currentLanguageCode.value;
    router.delete(`/cms/posts/${langCode}/${post.uuid}/force`);
}

function publishPost(post: Post) {
    const langCode = post.language_code || currentLanguageCode.value;
    router.post(`/cms/posts/${langCode}/${post.uuid}/publish`);
}

function unpublishPost(post: Post) {
    const langCode = post.language_code || currentLanguageCode.value;
    router.post(`/cms/posts/${langCode}/${post.uuid}/unpublish`);
}

function getStatusColor(status: string): 'success' | 'warning' | 'primary' | 'info' | 'neutral' {
    switch (status) {
        case 'published':
            return 'success';
        case 'draft':
            return 'neutral';
        case 'pending':
            return 'warning';
        case 'scheduled':
            return 'info';
        default:
            return 'neutral';
    }
}

function getPostTypeIcon(type: string) {
    return type === 'recipe' ? 'i-lucide-chef-hat' : 'i-lucide-file-text';
}

function getRowActions(row: Post) {
    const actions: any[][] = [];

    if (row.deleted_at) {
        // Trashed post actions
        if (can('posts.edit')) {
            actions.push([
                {
                    label: 'Restore',
                    icon: 'i-lucide-undo',
                    onSelect: () => restorePost(row),
                },
            ]);
        }
        if (can('posts.delete')) {
            actions.push([
                {
                    label: 'Delete Permanently',
                    icon: 'i-lucide-trash',
                    color: 'error' as const,
                    onSelect: () => forceDeletePost(row),
                },
            ]);
        }
    } else {
        // Normal post actions
        if (can('posts.edit')) {
            const langCode = row.language_code || currentLanguageCode.value;
            actions.push([
                {
                    label: 'Edit',
                    icon: 'i-lucide-pencil',
                    to: `/cms/posts/${langCode}/${row.uuid}/edit`,
                },
            ]);
        }

        if (can('posts.publish')) {
            if (row.status === 'published') {
                actions.push([
                    {
                        label: 'Unpublish',
                        icon: 'i-lucide-eye-off',
                        onSelect: () => unpublishPost(row),
                    },
                ]);
            } else if (row.status !== 'scheduled') {
                actions.push([
                    {
                        label: 'Publish',
                        icon: 'i-lucide-globe',
                        onSelect: () => publishPost(row),
                    },
                ]);
            }
        }

        if (can('posts.delete')) {
            actions.push([
                {
                    label: 'Move to Trash',
                    icon: 'i-lucide-trash',
                    color: 'error' as const,
                    onSelect: () => confirmDelete(row),
                },
            ]);
        }
    }

    return actions;
}

// Column definitions
const titleColumn: TableColumn<Post> = {
    accessorKey: 'title',
    header: () => {
        return h(UButton, {
            color: 'neutral',
            variant: 'ghost',
            label: 'Title',
            icon: getSortIcon('title'),
            class: '-mx-2.5',
            onClick: () => sortBy('title'),
        });
    },
    cell: ({ row }) => {
        // Apply RTL styling only to title and excerpt text
        const textClass = isRtlLanguage.value ? 'font-dhivehi text-right' : '';
        const textDir = isRtlLanguage.value ? 'rtl' : 'ltr';

        return h('div', { class: 'flex items-center gap-3' }, [
            row.original.featured_image_thumb
                ? h('img', {
                    src: row.original.featured_image_thumb,
                    alt: row.original.title,
                    class: 'size-10 rounded object-cover shrink-0',
                })
                : h('div', { class: 'size-10 rounded bg-muted flex items-center justify-center shrink-0' }, [
                    h('span', { class: getPostTypeIcon(row.original.post_type) }),
                ]),
            h('div', { class: 'min-w-0 flex-1' }, [
                h('p', {
                    class: ['font-medium text-highlighted line-clamp-1', textClass],
                    dir: textDir,
                }, row.original.title),
                h('p', {
                    class: ['text-muted text-sm line-clamp-1', textClass],
                    dir: textDir,
                }, row.original.excerpt || 'No excerpt'),
            ]),
        ]);
    },
};

const authorColumn: TableColumn<Post> = {
    accessorKey: 'author',
    header: () => {
        return h(UButton, {
            color: 'neutral',
            variant: 'ghost',
            label: 'Author',
            icon: getSortIcon('author'),
            class: '-mx-2.5',
            onClick: () => sortBy('author'),
        });
    },
    cell: ({ row }) => {
        if (!row.original.author) {
            return h('span', { class: 'text-muted' }, '—');
        }
        return h('div', { class: 'flex items-center gap-2' }, [
            h(UAvatar, {
                src: row.original.author.avatar_url,
                alt: row.original.author.name,
                size: 'sm',
            }),
            h('span', undefined, row.original.author.name),
        ]);
    },
};

const categoriesColumn: TableColumn<Post> = {
    accessorKey: 'categories',
    header: 'Categories',
    cell: ({ row }) => {
        const categories = row.original.categories || [];
        if (categories.length === 0) {
            return h('span', { class: 'text-muted' }, '—');
        }
        return h(
            'div',
            { class: 'flex flex-wrap gap-1' },
            categories.slice(0, 2).map((cat) =>
                h(
                    UBadge,
                    { color: 'neutral', variant: 'subtle', size: 'sm' },
                    () => cat.name
                )
            ).concat(
                categories.length > 2
                    ? [h('span', { class: 'text-muted text-sm' }, `+${categories.length - 2}`)]
                    : []
            )
        );
    },
};

const typeColumn: TableColumn<Post> = {
    accessorKey: 'post_type',
    header: 'Type',
    cell: ({ row }) => {
        return h(
            UBadge,
            {
                color: row.original.post_type === 'recipe' ? 'warning' : 'primary',
                variant: 'subtle',
                size: 'sm',
            },
            () => row.original.post_type === 'recipe' ? 'Recipe' : 'Article'
        );
    },
};

const statusColumn: TableColumn<Post> = {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
        return h(
            UBadge,
            {
                color: getStatusColor(row.original.status),
                variant: 'subtle',
                size: 'sm',
            },
            () => row.original.status.charAt(0).toUpperCase() + row.original.status.slice(1)
        );
    },
};

const dateColumn: TableColumn<Post> = {
    accessorKey: 'created_at',
    header: () => {
        return h(UButton, {
            color: 'neutral',
            variant: 'ghost',
            label: 'Date',
            icon: getSortIcon('created_at'),
            class: '-mx-2.5',
            onClick: () => sortBy('created_at'),
        });
    },
    cell: ({ row }) => {
        const date = row.original.published_at || row.original.created_at;
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    },
};

const actionsColumn: TableColumn<Post> = {
    id: 'actions',
    cell: ({ row }) => {
        const actions = getRowActions(row.original);
        if (actions.length === 0) return null;

        return h(
            'div',
            { class: 'text-right' },
            h(
                UDropdownMenu,
                {
                    content: { align: 'end' },
                    items: actions,
                },
                () =>
                    h(UButton, {
                        icon: 'i-lucide-ellipsis-vertical',
                        color: 'neutral',
                        variant: 'ghost',
                        class: 'ml-auto',
                    })
            )
        );
    },
};

// Computed columns - RTL order: Date, Status, Type, Categories, Author, Title, Actions
const columns = computed<TableColumn<Post>[]>(() => {
    if (isRtlLanguage.value) {
        return [dateColumn, statusColumn, typeColumn, categoriesColumn, authorColumn, titleColumn, actionsColumn];
    }
    // LTR order: Title, Author, Categories, Type, Status, Date, Actions
    return [titleColumn, authorColumn, categoriesColumn, typeColumn, statusColumn, dateColumn, actionsColumn];
});
</script>

<template>
    <Head title="Posts" />

    <DashboardLayout>
        <UDashboardPanel id="posts">
            <template #header>
                <UDashboardNavbar title="Posts">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <UButton
                            v-if="can('posts.create')"
                            icon="i-lucide-plus"
                            @click="createNewPost"
                        >
                            New Post
                        </UButton>
                    </template>
                </UDashboardNavbar>

                <UDashboardToolbar>
                    <UNavigationMenu :items="statusLinks" highlight class="-mx-1 flex-1" />
                </UDashboardToolbar>
            </template>

            <template #body>
                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <UInput
                        v-model="search"
                        class="w-full sm:max-w-xs"
                        icon="i-lucide-search"
                        placeholder="Search posts..."
                    />

                    <USelectMenu
                        v-model="selectedPostType"
                        :items="postTypeOptions"
                        value-key="value"
                        class="w-full sm:w-40"
                        placeholder="Post Type"
                    />

                    <USelectMenu
                        v-model="selectedAuthor"
                        :items="authorOptions"
                        value-key="value"
                        class="w-full sm:w-44"
                        placeholder="Author"
                    />

                    <USelectMenu
                        v-model="selectedCategory"
                        :items="categoryOptions"
                        value-key="value"
                        class="w-full sm:w-44"
                        placeholder="Category"
                    />

                    <!-- Language indicator -->
                    <UBadge
                        :color="isRtlLanguage ? 'warning' : 'primary'"
                        variant="subtle"
                        size="lg"
                        class="gap-1.5"
                    >
                        <UIcon :name="isRtlLanguage ? 'i-lucide-align-right' : 'i-lucide-align-left'" class="size-3.5" />
                        {{ language.name }}
                    </UBadge>

                    <div class="text-sm text-muted ml-auto">
                        {{ posts.total }} post{{ posts.total !== 1 ? 's' : '' }}
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="posts.data.length === 0"
                    class="flex flex-col items-center justify-center py-12 text-center"
                >
                    <div class="size-12 rounded-full bg-muted/50 flex items-center justify-center mb-4">
                        <UIcon name="i-lucide-file-text" class="size-6 text-muted" />
                    </div>
                    <h3 class="text-lg font-medium text-highlighted">No posts found</h3>
                    <p class="text-muted mt-1">
                        {{ search || selectedPostType || selectedAuthor || selectedCategory
                            ? 'Try adjusting your filters'
                            : 'Get started by creating your first post' }}
                    </p>
                    <UButton
                        v-if="can('posts.create') && !search"
                        icon="i-lucide-plus"
                        class="mt-4"
                        @click="createNewPost"
                    >
                        Create Post
                    </UButton>
                </div>

                <!-- Posts Table -->
                <UTable
                    v-else
                    :data="posts.data"
                    :columns="columns"
                    :ui="{
                        base: 'table-fixed border-separate border-spacing-0',
                        thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                        tbody: '[&>tr]:last:[&>td]:border-b-0',
                        th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
                        td: 'border-b border-default',
                        separator: 'h-0',
                    }"
                />

                <!-- Pagination -->
                <div
                    v-if="posts.last_page > 1"
                    class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-auto"
                >
                    <div class="text-sm text-muted">
                        Showing {{ posts.from }} to {{ posts.to }} of {{ posts.total }}
                    </div>

                    <UPagination
                        :default-page="posts.current_page"
                        :items-per-page="posts.per_page"
                        :total="posts.total"
                        @update:page="(p: number) => router.get(`/cms/posts/${currentLanguageCode}`, { ...filters, page: p })"
                    />
                </div>
            </template>
        </UDashboardPanel>

        <!-- Delete Confirmation Modal -->
        <UModal v-model:open="deleteModalOpen">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center size-12 rounded-full bg-error/10 shrink-0">
                            <UIcon name="i-lucide-alert-triangle" class="size-6 text-error" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-highlighted">Move to Trash</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to trash <strong class="text-highlighted">{{ postToDelete?.title }}</strong>? You can restore it later from the trash.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <UButton
                            color="neutral"
                            variant="outline"
                            @click="deleteModalOpen = false"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            color="error"
                            @click="deletePost"
                        >
                            Move to Trash
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>

    </DashboardLayout>
</template>
