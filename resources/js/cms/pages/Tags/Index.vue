<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import TagCreateSlideover from '../../components/TagCreateSlideover.vue';
import TagEditSlideover from '../../components/TagEditSlideover.vue';
import { usePermission } from '../../composables/usePermission';
import { useCmsPath } from '../../composables/useCmsPath';
import type { Tag, PaginatedResponse, Language } from '../../types';
import type { TableColumn } from '@nuxt/ui';

interface TagWithTranslations extends Tag {
    name_translations?: Record<string, string>;
}

const props = defineProps<{
    tags: PaginatedResponse<Tag>;
    filters: {
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
    languages: Language[];
}>();

const { can } = usePermission();
const { cmsPath } = useCmsPath();

const UButton = resolveComponent('UButton');
const UBadge = resolveComponent('UBadge');
const UDropdownMenu = resolveComponent('UDropdownMenu');
const UCheckbox = resolveComponent('UCheckbox');

const search = ref(props.filters.search || '');
const deleteModalOpen = ref(false);
const tagToDelete = ref<Tag | null>(null);
const createSlideoverOpen = ref(false);
const editSlideoverOpen = ref(false);
const tagToEdit = ref<TagWithTranslations | null>(null);
const rowSelection = ref<Record<string, boolean>>({});
const bulkDeleteModalOpen = ref(false);
const bulkDeleting = ref(false);

const table = ref<{ tableApi?: any } | null>(null);

const selectedCount = computed(() => {
    return Object.values(rowSelection.value).filter(Boolean).length;
});

const selectedIds = computed(() => {
    return Object.entries(rowSelection.value)
        .filter(([_, selected]) => selected)
        .map(([index]) => props.tags.data[parseInt(index)]?.id)
        .filter(Boolean);
});

function clearSelection() {
    rowSelection.value = {};
}

function confirmBulkDelete() {
    bulkDeleteModalOpen.value = true;
}

function bulkDelete() {
    if (selectedIds.value.length === 0) return;

    bulkDeleting.value = true;
    router.delete(cmsPath('/tags/bulk'), {
        data: { ids: selectedIds.value },
        onSuccess: () => {
            bulkDeleteModalOpen.value = false;
            rowSelection.value = {};
        },
        onFinish: () => {
            bulkDeleting.value = false;
        },
    });
}

function onSearch() {
    router.get(cmsPath('/tags'), {
        search: search.value || undefined,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearSearch() {
    search.value = '';
    onSearch();
}

function confirmDelete(tag: Tag) {
    tagToDelete.value = tag;
    deleteModalOpen.value = true;
}

function deleteTag() {
    if (tagToDelete.value) {
        router.delete(cmsPath(`/tags/${tagToDelete.value.uuid}`), {
            onSuccess: () => {
                deleteModalOpen.value = false;
                tagToDelete.value = null;
            },
        });
    }
}

async function openEditSlideover(tag: Tag) {
    try {
        // Fetch full tag data with translations via AJAX
        const response = await fetch(cmsPath(`/tags/${tag.uuid}/edit`), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) throw new Error('Failed to fetch tag');

        const data = await response.json();
        tagToEdit.value = data.props.tag;
        editSlideoverOpen.value = true;
    } catch (error) {
        console.error('Failed to load tag for editing:', error);
    }
}

function onEditClose(updated: boolean) {
    editSlideoverOpen.value = false;
    tagToEdit.value = null;
    if (updated) {
        router.reload({ only: ['tags'] });
    }
}

function sortBy(field: string) {
    const newDirection = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(cmsPath('/tags'), {
        search: search.value || undefined,
        sort: field,
        direction: newDirection,
    }, {
        preserveState: true,
        replace: true,
    });
}

function getRowActions(row: Tag) {
    const actions: any[][] = [];

    if (can('tags.edit')) {
        actions.push([
            {
                label: 'Edit',
                icon: 'i-lucide-pencil',
                onSelect: () => openEditSlideover(row),
            },
            {
                label: 'Layout',
                icon: 'i-lucide-layout-template',
                onSelect: () => router.visit(cmsPath(`/layouts/tags/${row.uuid}`)),
            },
        ]);
    }

    if (can('tags.delete')) {
        actions.push([
            {
                label: 'Delete',
                icon: 'i-lucide-trash',
                color: 'error' as const,
                onSelect: () => confirmDelete(row),
            },
        ]);
    }

    return actions;
}

// Helper to render translation status badges
function renderTranslationStatus(translatedLocales: string[] | undefined) {
    if (!translatedLocales || props.languages.length <= 1) return null;

    return h('div', { class: 'flex items-center gap-1' },
        props.languages.map(lang => {
            const isTranslated = translatedLocales.includes(lang.code);
            return h(
                'span',
                {
                    class: `inline-flex items-center justify-center size-5 text-[10px] font-medium rounded ${
                        isTranslated
                            ? 'bg-success/10 text-success'
                            : 'bg-muted/20 text-muted'
                    }`,
                    title: isTranslated ? `${lang.name} translation available` : `No ${lang.name} translation`,
                },
                lang.code.toUpperCase()
            );
        })
    );
}

const columns: TableColumn<Tag>[] = [
    {
        id: 'select',
        header: ({ table }) => h(UCheckbox, {
            'modelValue': table.getIsSomePageRowsSelected() ? 'indeterminate' : table.getIsAllPageRowsSelected(),
            'onUpdate:modelValue': (value: boolean | 'indeterminate') => table.toggleAllPageRowsSelected(!!value),
            'ariaLabel': 'Select all',
        }),
        cell: ({ row }) => h(UCheckbox, {
            'modelValue': row.getIsSelected(),
            'onUpdate:modelValue': (value: boolean | 'indeterminate') => row.toggleSelected(!!value),
            'ariaLabel': 'Select row',
        }),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: 'name',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('name'),
        }, [
            'Name',
            props.filters.sort === 'name' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h('div', { class: 'font-medium text-highlighted' }, row.original.name);
        },
    },
    {
        accessorKey: 'slug',
        header: 'Slug',
        cell: ({ row }) => {
            return h('span', { class: 'text-muted font-mono text-sm' }, row.original.slug);
        },
    },
    {
        accessorKey: 'posts_count',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('posts_count'),
        }, [
            'Posts',
            props.filters.sort === 'posts_count' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h(
                UBadge,
                { color: 'neutral', variant: 'subtle' },
                () => `${row.original.posts_count ?? 0} posts`
            );
        },
    },
    {
        id: 'translations',
        header: 'Translations',
        cell: ({ row }) => renderTranslationStatus(row.original.translated_locales),
    },
    {
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
    },
];
</script>

<template>
    <Head title="Tags" />

    <DashboardLayout>
        <UDashboardPanel id="tags">
            <template #header>
                <UDashboardNavbar title="Tags">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <TagCreateSlideover
                            v-if="can('tags.create')"
                            v-model:open="createSlideoverOpen"
                            :languages="languages"
                        >
                            <UButton icon="i-lucide-plus">
                                Add Tag
                            </UButton>
                        </TagCreateSlideover>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <UInput
                            v-model="search"
                            placeholder="Search tags..."
                            icon="i-lucide-search"
                            :ui="{ base: 'w-64' }"
                            @keyup.enter="onSearch"
                        />
                        <UButton
                            v-if="search"
                            color="neutral"
                            variant="ghost"
                            icon="i-lucide-x"
                            @click="clearSearch"
                        />
                    </div>

                    <span class="text-sm text-muted">
                        {{ tags.total }} tag{{ tags.total !== 1 ? 's' : '' }}
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
                            {{ selectedCount }} {{ selectedCount === 1 ? 'tag' : 'tags' }} selected
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
                            v-if="can('tags.delete')"
                            color="error"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-trash"
                            @click="confirmBulkDelete"
                        >
                            Delete Selected
                        </UButton>
                    </div>
                </div>

                <UTable
                    ref="table"
                    v-model:row-selection="rowSelection"
                    :data="tags.data"
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
                <div v-if="tags.last_page > 1" class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-4">
                    <div class="text-sm text-muted">
                        {{ table?.tableApi?.getFilteredSelectedRowModel().rows.length || 0 }} of
                        {{ tags.total }} row(s) selected.
                    </div>
                    <UPagination
                        :page="tags.current_page"
                        :total="tags.total"
                        :items-per-page="tags.per_page"
                        @update:page="(page) => router.get(cmsPath('/tags'), { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="tags.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-tags" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No tags found.</p>
                    <UButton
                        v-if="can('tags.create')"
                        class="mt-4"
                        variant="outline"
                        @click="createSlideoverOpen = true"
                    >
                        Create your first tag
                    </UButton>
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete Tag</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ tagToDelete?.name }}</strong>?
                            </p>
                            <p v-if="tagToDelete?.posts_count" class="mt-1 text-sm text-warning">
                                This tag is used in {{ tagToDelete.posts_count }} posts.
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
                            @click="deleteTag"
                        >
                            Delete
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>

        <!-- Bulk Delete Confirmation Modal -->
        <UModal v-model:open="bulkDeleteModalOpen">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center size-12 rounded-full bg-error/10 shrink-0">
                            <UIcon name="i-lucide-alert-triangle" class="size-6 text-error" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-highlighted">Delete {{ selectedCount }} Tags</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ selectedCount }}</strong> selected tags?
                                This action cannot be undone.
                            </p>
                            <p class="mt-1 text-sm text-warning">
                                Tags with associated posts cannot be deleted.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <UButton
                            color="neutral"
                            variant="outline"
                            :disabled="bulkDeleting"
                            @click="bulkDeleteModalOpen = false"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            color="error"
                            :loading="bulkDeleting"
                            @click="bulkDelete"
                        >
                            Delete {{ selectedCount }} Tags
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>

        <!-- Edit Tag Slideover -->
        <TagEditSlideover
            v-model:open="editSlideoverOpen"
            :tag="tagToEdit"
            :languages="languages"
            @close="onEditClose"
        />
    </DashboardLayout>
</template>
