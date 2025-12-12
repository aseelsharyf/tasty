<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import type { Page, PaginatedResponse, Language } from '../../types';
import type { TableColumn } from '@nuxt/ui';

const props = defineProps<{
    pages: PaginatedResponse<Page>;
    filters: {
        search?: string;
        status?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
    counts: {
        total: number;
        published: number;
        draft: number;
    };
    statuses: Record<string, string>;
    layouts: Record<string, string>;
    languages: Language[];
    currentLanguage: Language;
}>();

const { can } = usePermission();

const UButton = resolveComponent('UButton');
const UBadge = resolveComponent('UBadge');
const UDropdownMenu = resolveComponent('UDropdownMenu');
const UCheckbox = resolveComponent('UCheckbox');

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const deleteModalOpen = ref(false);
const pageToDelete = ref<Page | null>(null);
const rowSelection = ref<Record<string, boolean>>({});
const bulkDeleteModalOpen = ref(false);
const bulkDeleting = ref(false);

const table = ref<{ tableApi?: any } | null>(null);

const isSearching = computed(() => search.value.length > 0);

const selectedCount = computed(() => {
    return Object.values(rowSelection.value).filter(Boolean).length;
});

const selectedIds = computed(() => {
    return Object.entries(rowSelection.value)
        .filter(([_, selected]) => selected)
        .map(([index]) => props.pages.data[parseInt(index)]?.id)
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
    router.delete(`/cms/pages/${props.currentLanguage.code}/bulk`, {
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

function applyFilters() {
    router.get(`/cms/pages/${props.currentLanguage.code}`, {
        search: search.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearSearch() {
    search.value = '';
    applyFilters();
}

function sortBy(field: string) {
    const newDirection = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(`/cms/pages/${props.currentLanguage.code}`, {
        search: search.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        sort: field,
        direction: newDirection,
    }, {
        preserveState: true,
        replace: true,
    });
}

function confirmDelete(page: Page) {
    pageToDelete.value = page;
    deleteModalOpen.value = true;
}

function deletePage() {
    if (pageToDelete.value) {
        router.delete(`/cms/pages/${props.currentLanguage.code}/${pageToDelete.value.uuid}`, {
            onSuccess: () => {
                deleteModalOpen.value = false;
                pageToDelete.value = null;
            },
        });
    }
}

function getRowActions(row: Page) {
    const actions: any[][] = [];

    if (can('pages.edit')) {
        actions.push([
            {
                label: 'Edit',
                icon: 'i-lucide-pencil',
                onSelect: () => router.visit(`/cms/pages/${props.currentLanguage.code}/${row.uuid}/edit`),
            },
        ]);
    }

    // View page action
    actions.push([
        {
            label: 'View Page',
            icon: 'i-lucide-external-link',
            onSelect: () => window.open(`/${row.slug}`, '_blank'),
        },
    ]);

    if (can('pages.delete')) {
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

function getSortIcon(field: string) {
    if (props.filters.sort !== field) return null;
    return props.filters.direction === 'asc' ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down';
}

function formatDate(dateString: string | null | undefined): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

const columns: TableColumn<Page>[] = [
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
        accessorKey: 'title',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('title'),
        }, [
            'Title',
            getSortIcon('title') && h('span', { class: getSortIcon('title') }),
        ]),
        cell: ({ row }) => {
            const page = row.original;
            return h('div', {}, [
                h('div', { class: 'font-medium text-highlighted' }, page.title),
                h('div', { class: 'text-xs text-muted font-mono' }, `/${page.slug}`),
            ]);
        },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.original.status;
            return h(
                UBadge,
                {
                    color: status === 'published' ? 'success' : 'neutral',
                    variant: 'subtle',
                },
                () => props.statuses[status] || status
            );
        },
    },
    {
        accessorKey: 'layout',
        header: 'Layout',
        cell: ({ row }) => {
            return h('span', { class: 'text-muted text-sm' }, props.layouts[row.original.layout] || row.original.layout);
        },
    },
    {
        id: 'blade',
        header: 'Type',
        cell: ({ row }) => {
            return h(
                UBadge,
                {
                    color: row.original.is_blade ? 'primary' : 'neutral',
                    variant: 'subtle',
                },
                () => row.original.is_blade ? 'Blade' : 'HTML'
            );
        },
    },
    {
        accessorKey: 'author',
        header: 'Author',
        cell: ({ row }) => {
            return h('span', { class: 'text-muted text-sm' }, row.original.author?.name || '-');
        },
    },
    {
        accessorKey: 'updated_at',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('updated_at'),
        }, [
            'Updated',
            getSortIcon('updated_at') && h('span', { class: getSortIcon('updated_at') }),
        ]),
        cell: ({ row }) => {
            return h('span', { class: 'text-muted text-sm' }, formatDate(row.original.updated_at));
        },
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

const statusFilterOptions = computed(() => [
    { label: 'All Statuses', value: 'all' },
    ...Object.entries(props.statuses).map(([value, label]) => ({
        label,
        value,
    })),
]);

const languageOptions = computed(() =>
    props.languages.map(l => ({
        label: l.native_name,
        value: l.code,
    }))
);

function switchLanguage(code: string) {
    router.visit(`/cms/pages/${code}`);
}
</script>

<template>
    <Head title="Pages" />

    <DashboardLayout>
        <UDashboardPanel id="pages">
            <template #header>
                <UDashboardNavbar title="Pages">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <div class="flex items-center gap-3">
                            <USelectMenu
                                v-if="languages.length > 1"
                                :model-value="currentLanguage.code"
                                :items="languageOptions"
                                value-key="value"
                                class="w-36"
                                @update:model-value="switchLanguage"
                            >
                                <template #leading>
                                    <UIcon name="i-lucide-globe" class="size-4" />
                                </template>
                            </USelectMenu>
                            <Link
                                v-if="can('pages.create')"
                                :href="`/cms/pages/${currentLanguage.code}/create`"
                            >
                                <UButton icon="i-lucide-plus">
                                    Add Page
                                </UButton>
                            </Link>
                        </div>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <UInput
                            v-model="search"
                            placeholder="Search pages..."
                            icon="i-lucide-search"
                            :ui="{ base: 'w-64' }"
                            @keyup.enter="applyFilters"
                        />
                        <UButton
                            v-if="isSearching"
                            color="neutral"
                            variant="ghost"
                            icon="i-lucide-x"
                            @click="clearSearch"
                        />
                        <USelectMenu
                            v-model="statusFilter"
                            :items="statusFilterOptions"
                            placeholder="Status"
                            value-key="value"
                            class="w-36"
                            @update:model-value="applyFilters"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted">
                            {{ counts.total }} pages
                            <span class="text-xs">({{ counts.published }} published, {{ counts.draft }} drafts)</span>
                        </span>
                    </div>
                </div>

                <!-- Bulk Actions Bar -->
                <div
                    v-if="selectedCount > 0"
                    class="flex items-center justify-between gap-3 p-3 mb-4 rounded-lg bg-elevated border border-default"
                >
                    <div class="flex items-center gap-2">
                        <UIcon name="i-lucide-check-square" class="size-5 text-primary" />
                        <span class="text-sm font-medium">
                            {{ selectedCount }} {{ selectedCount === 1 ? 'page' : 'pages' }} selected
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
                            v-if="can('pages.delete')"
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
                    :data="pages.data"
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
                <div v-if="pages.last_page > 1" class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-4">
                    <div class="text-sm text-muted">
                        {{ table?.tableApi?.getFilteredSelectedRowModel().rows.length || 0 }} of
                        {{ pages.total }} row(s) selected.
                    </div>
                    <UPagination
                        :page="pages.current_page"
                        :total="pages.total"
                        :items-per-page="pages.per_page"
                        @update:page="(page) => router.get(`/cms/pages/${currentLanguage.code}`, { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="pages.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-file-text" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No pages found.</p>
                    <Link
                        v-if="can('pages.create')"
                        :href="`/cms/pages/${currentLanguage.code}/create`"
                        class="inline-block mt-4"
                    >
                        <UButton variant="outline">
                            Create your first page
                        </UButton>
                    </Link>
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete Page</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ pageToDelete?.title }}</strong>?
                                This action cannot be undone.
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
                            @click="deletePage"
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete {{ selectedCount }} Pages</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ selectedCount }}</strong> selected pages?
                                This action cannot be undone.
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
                            Delete {{ selectedCount }} Pages
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
