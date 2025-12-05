<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import CategoryCreateSlideover from '../../components/CategoryCreateSlideover.vue';
import { usePermission } from '../../composables/usePermission';
import type { Category, CategoryTreeItem, ParentOption, PaginatedResponse } from '../../types';
import type { TableColumn } from '@nuxt/ui';

const props = defineProps<{
    categories: PaginatedResponse<Category>;
    tree: CategoryTreeItem[];
    parentOptions: ParentOption[];
    filters: {
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
    counts: {
        total: number;
        root: number;
        child: number;
    };
}>();

const { can } = usePermission();

const UButton = resolveComponent('UButton');
const UBadge = resolveComponent('UBadge');
const UDropdownMenu = resolveComponent('UDropdownMenu');
const UIcon = resolveComponent('UIcon');
const UCheckbox = resolveComponent('UCheckbox');

const search = ref(props.filters.search || '');
const deleteModalOpen = ref(false);
const categoryToDelete = ref<Category | null>(null);
const viewMode = ref<'tree' | 'list'>('tree');
const createModalOpen = ref(false);
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
        .map(([index]) => props.categories.data[parseInt(index)]?.id)
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
    router.delete('/cms/categories/bulk', {
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
    router.get('/cms/categories', {
        search: search.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearSearch() {
    search.value = '';
    onSearch();
}

function sortBy(field: string) {
    const newDirection = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get('/cms/categories', {
        search: search.value || undefined,
        sort: field,
        direction: newDirection,
    }, {
        preserveState: true,
        replace: true,
    });
}

function confirmDelete(category: Category | CategoryTreeItem) {
    categoryToDelete.value = category as Category;
    deleteModalOpen.value = true;
}

function deleteCategory() {
    if (categoryToDelete.value) {
        router.delete(`/cms/categories/${categoryToDelete.value.id}`, {
            onSuccess: () => {
                deleteModalOpen.value = false;
                categoryToDelete.value = null;
            },
        });
    }
}

function getRowActions(row: Category) {
    const actions: any[][] = [];

    if (can('categories.edit')) {
        actions.push([
            {
                label: 'Edit',
                icon: 'i-lucide-pencil',
                to: `/cms/categories/${row.id}/edit`,
            },
        ]);
    }

    if (can('categories.delete')) {
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

const columns: TableColumn<Category>[] = [
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
            getSortIcon('name') && h('span', { class: getSortIcon('name') }),
        ]),
        cell: ({ row }) => {
            const category = row.original;
            const isChild = !!category.parent;

            return h('div', { class: 'flex items-center gap-2' }, [
                // Show hierarchy indicator for children
                isChild && h(UIcon, {
                    name: 'i-lucide-corner-down-right',
                    class: 'size-4 text-muted',
                }),
                h('div', {}, [
                    h('div', { class: 'font-medium text-highlighted' }, category.name),
                    // Show parent name for child categories
                    isChild && h('div', { class: 'text-xs text-muted' }, `in ${category.parent?.name}`),
                ]),
            ]);
        },
    },
    {
        accessorKey: 'slug',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('slug'),
        }, [
            'Slug',
            getSortIcon('slug') && h('span', { class: getSortIcon('slug') }),
        ]),
        cell: ({ row }) => {
            return h('span', { class: 'text-muted font-mono text-sm' }, row.original.slug);
        },
    },
    {
        id: 'type',
        header: 'Type',
        cell: ({ row }) => {
            const isParent = !row.original.parent;
            return h(
                UBadge,
                {
                    color: isParent ? 'primary' : 'neutral',
                    variant: 'subtle',
                },
                () => isParent ? 'Parent' : 'Child'
            );
        },
    },
    {
        accessorKey: 'posts_count',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('posts_count'),
        }, [
            'Posts',
            getSortIcon('posts_count') && h('span', { class: getSortIcon('posts_count') }),
        ]),
        cell: ({ row }) => {
            return h(
                UBadge,
                { color: 'neutral', variant: 'subtle' },
                () => `${row.original.posts_count ?? 0}`
            );
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

function flattenTree(items: CategoryTreeItem[], depth = 0): (CategoryTreeItem & { depth: number })[] {
    const result: (CategoryTreeItem & { depth: number })[] = [];
    for (const item of items) {
        result.push({ ...item, depth });
        if (item.children && item.children.length > 0) {
            result.push(...flattenTree(item.children, depth + 1));
        }
    }
    return result;
}

const flattenedTree = computed(() => flattenTree(props.tree));
</script>

<template>
    <Head title="Categories" />

    <DashboardLayout>
        <UDashboardPanel id="categories">
            <template #header>
                <UDashboardNavbar title="Categories">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <CategoryCreateSlideover
                            v-if="can('categories.create')"
                            v-model:open="createModalOpen"
                            :parent-options="parentOptions"
                        >
                            <UButton icon="i-lucide-plus">
                                Add Category
                            </UButton>
                        </CategoryCreateSlideover>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <UInput
                            v-model="search"
                            placeholder="Search categories..."
                            icon="i-lucide-search"
                            :ui="{ base: 'w-64' }"
                            @keyup.enter="onSearch"
                        />
                        <UButton
                            v-if="isSearching"
                            color="neutral"
                            variant="ghost"
                            icon="i-lucide-x"
                            @click="clearSearch"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <UButtonGroup>
                            <UButton
                                :color="viewMode === 'tree' ? 'primary' : 'neutral'"
                                :variant="viewMode === 'tree' ? 'solid' : 'ghost'"
                                icon="i-lucide-git-branch"
                                @click="viewMode = 'tree'"
                            />
                            <UButton
                                :color="viewMode === 'list' ? 'primary' : 'neutral'"
                                :variant="viewMode === 'list' ? 'solid' : 'ghost'"
                                icon="i-lucide-list"
                                @click="viewMode = 'list'"
                            />
                        </UButtonGroup>
                        <span class="text-sm text-muted">
                            {{ counts.total }} categories
                            <span class="text-xs">({{ counts.root }} parent, {{ counts.child }} child)</span>
                        </span>
                    </div>
                </div>

                <!-- Tree View -->
                <div v-if="viewMode === 'tree' && !isSearching" class="space-y-2">
                    <div
                        v-for="item in flattenedTree"
                        :key="item.id"
                        class="flex items-center gap-3 p-3 rounded-lg border border-default hover:bg-elevated/50 transition-colors"
                        :style="{ marginLeft: `${item.depth * 24}px` }"
                    >
                        <UIcon
                            v-if="item.children && item.children.length > 0"
                            name="i-lucide-folder"
                            class="size-5 text-primary"
                        />
                        <UIcon
                            v-else
                            name="i-lucide-folder-open"
                            class="size-5 text-muted"
                        />

                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-highlighted truncate">
                                {{ item.name }}
                            </div>
                            <div class="text-sm text-muted truncate">
                                {{ item.slug }}
                            </div>
                        </div>

                        <UBadge color="neutral" variant="subtle">
                            {{ item.posts_count }} posts
                        </UBadge>

                        <UDropdownMenu
                            :content="{ align: 'end' }"
                            :items="[
                                can('categories.edit') ? [{ label: 'Edit', icon: 'i-lucide-pencil', to: `/cms/categories/${item.id}/edit` }] : [],
                                can('categories.delete') ? [{ label: 'Delete', icon: 'i-lucide-trash', color: 'error', onSelect: () => confirmDelete(item) }] : [],
                            ].filter(g => g.length > 0)"
                        >
                            <UButton
                                icon="i-lucide-ellipsis-vertical"
                                color="neutral"
                                variant="ghost"
                                size="sm"
                            />
                        </UDropdownMenu>
                    </div>

                    <div v-if="flattenedTree.length === 0" class="text-center py-12 text-muted">
                        <UIcon name="i-lucide-folder-open" class="size-12 mx-auto mb-4 opacity-50" />
                        <p>No categories found.</p>
                        <UButton
                            v-if="can('categories.create')"
                            class="mt-4"
                            variant="outline"
                            @click="createModalOpen = true"
                        >
                            Create your first category
                        </UButton>
                    </div>
                </div>

                <!-- List/Table View -->
                <template v-else>
                    <!-- Bulk Actions Bar -->
                    <div
                        v-if="selectedCount > 0"
                        class="flex items-center justify-between gap-3 p-3 mb-4 rounded-lg bg-elevated border border-default"
                    >
                        <div class="flex items-center gap-2">
                            <UIcon name="i-lucide-check-square" class="size-5 text-primary" />
                            <span class="text-sm font-medium">
                                {{ selectedCount }} {{ selectedCount === 1 ? 'category' : 'categories' }} selected
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
                                v-if="can('categories.delete')"
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
                        :data="categories.data"
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
                    <div v-if="categories.last_page > 1" class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-4">
                        <div class="text-sm text-muted">
                            {{ table?.tableApi?.getFilteredSelectedRowModel().rows.length || 0 }} of
                            {{ categories.total }} row(s) selected.
                        </div>
                        <UPagination
                            :page="categories.current_page"
                            :total="categories.total"
                            :items-per-page="categories.per_page"
                            @update:page="(page) => router.get('/cms/categories', { ...filters, page }, { preserveState: true })"
                        />
                    </div>

                    <!-- Empty State -->
                    <div v-if="categories.data.length === 0" class="text-center py-12 text-muted">
                        <UIcon name="i-lucide-folder-open" class="size-12 mx-auto mb-4 opacity-50" />
                        <p>No categories found.</p>
                        <UButton
                            v-if="can('categories.create')"
                            class="mt-4"
                            variant="outline"
                            @click="createModalOpen = true"
                        >
                            Create your first category
                        </UButton>
                    </div>
                </template>
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete Category</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ categoryToDelete?.name }}</strong>?
                            </p>
                            <p v-if="categoryToDelete?.posts_count" class="mt-1 text-sm text-warning">
                                This category has {{ categoryToDelete.posts_count }} posts associated with it.
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
                            @click="deleteCategory"
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete {{ selectedCount }} Categories</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ selectedCount }}</strong> selected categories?
                                This action cannot be undone.
                            </p>
                            <p class="mt-1 text-sm text-warning">
                                Categories with children or posts cannot be deleted.
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
                            Delete {{ selectedCount }} Categories
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
