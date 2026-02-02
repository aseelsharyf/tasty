<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import ProductCategoryCreateSlideover from '../../components/ProductCategoryCreateSlideover.vue';
import ProductCategoryEditSlideover from '../../components/ProductCategoryEditSlideover.vue';
import { usePermission } from '../../composables/usePermission';
import { useCmsPath } from '../../composables/useCmsPath';
import type { Language, PaginatedResponse } from '../../types';
import type { TableColumn } from '@nuxt/ui';

interface ProductCategory {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    description?: string | null;
    parent_id?: number | null;
    parent_name?: string | null;
    featured_image_url?: string | null;
    is_active: boolean;
    order: number;
    products_count: number;
    created_at: string;
    translated_locales?: string[];
}

interface ProductCategoryWithTranslations extends ProductCategory {
    name_translations?: Record<string, string>;
    description_translations?: Record<string, string>;
    parent_id?: number | null;
    featured_media_id?: number | null;
    featured_media?: {
        id: number;
        url: string;
        title?: string;
    } | null;
}

interface ParentCategory {
    id: number;
    name: string;
}

const props = defineProps<{
    categories: PaginatedResponse<ProductCategory>;
    parentCategories: ParentCategory[];
    filters: {
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
        is_active?: boolean;
    };
    languages: Language[];
}>();

const { can } = usePermission();
const { cmsPath } = useCmsPath();

const UButton = resolveComponent('UButton');
const UBadge = resolveComponent('UBadge');
const UDropdownMenu = resolveComponent('UDropdownMenu');
const UCheckbox = resolveComponent('UCheckbox');
const UAvatar = resolveComponent('UAvatar');

const search = ref(props.filters.search || '');
const deleteModalOpen = ref(false);
const categoryToDelete = ref<ProductCategory | null>(null);
const createSlideoverOpen = ref(false);
const editSlideoverOpen = ref(false);
const categoryToEdit = ref<ProductCategoryWithTranslations | null>(null);
const editParentCategories = ref<ParentCategory[]>([]);
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
    router.delete(cmsPath('/product-categories/bulk'), {
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
    router.get(cmsPath('/product-categories'), {
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

function confirmDelete(category: ProductCategory) {
    categoryToDelete.value = category;
    deleteModalOpen.value = true;
}

function deleteCategory() {
    if (categoryToDelete.value) {
        router.delete(cmsPath(`/product-categories/${categoryToDelete.value.uuid}`), {
            onSuccess: () => {
                deleteModalOpen.value = false;
                categoryToDelete.value = null;
            },
        });
    }
}

async function openEditSlideover(category: ProductCategory) {
    try {
        const response = await fetch(cmsPath(`/product-categories/${category.uuid}/edit`), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) throw new Error('Failed to fetch category');

        const data = await response.json();
        categoryToEdit.value = data.props.category;
        editParentCategories.value = data.props.parentCategories || [];
        editSlideoverOpen.value = true;
    } catch (error) {
        console.error('Failed to load category for editing:', error);
    }
}

function onEditClose(updated: boolean) {
    editSlideoverOpen.value = false;
    categoryToEdit.value = null;
    if (updated) {
        router.reload({ only: ['categories'] });
    }
}

function sortBy(field: string) {
    const newDirection = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(cmsPath('/product-categories'), {
        search: search.value || undefined,
        sort: field,
        direction: newDirection,
    }, {
        preserveState: true,
        replace: true,
    });
}

function getRowActions(row: ProductCategory) {
    const actions: any[][] = [];

    if (can('products.edit')) {
        actions.push([
            {
                label: 'Edit',
                icon: 'i-lucide-pencil',
                onSelect: () => openEditSlideover(row),
            },
        ]);
    }

    if (can('products.delete')) {
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

const columns: TableColumn<ProductCategory>[] = [
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
            'Category',
            props.filters.sort === 'name' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h('div', { class: 'flex items-center gap-3' }, [
                h(UAvatar, {
                    src: row.original.featured_image_url || undefined,
                    alt: row.original.name,
                    size: 'sm',
                    icon: 'i-lucide-folder',
                    class: 'bg-elevated',
                }),
                h('div', {}, [
                    h('div', { class: 'font-medium text-highlighted' }, row.original.name),
                    row.original.description && h('p', {
                        class: 'text-xs text-muted truncate max-w-48',
                    }, row.original.description),
                ]),
            ]);
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
        accessorKey: 'parent_name',
        header: 'Parent',
        cell: ({ row }) => {
            if (!row.original.parent_name) {
                return h('span', { class: 'text-muted' }, '—');
            }
            return h('span', { class: 'text-sm' }, row.original.parent_name);
        },
    },
    {
        accessorKey: 'is_active',
        header: 'Status',
        cell: ({ row }) => {
            return h(
                UBadge,
                {
                    color: row.original.is_active ? 'success' : 'neutral',
                    variant: 'subtle',
                },
                () => row.original.is_active ? 'Active' : 'Inactive'
            );
        },
    },
    {
        accessorKey: 'products_count',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('products_count'),
        }, [
            'Products',
            props.filters.sort === 'products_count' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h(
                UBadge,
                { color: 'neutral', variant: 'subtle' },
                () => `${row.original.products_count ?? 0} products`
            );
        },
    },
    {
        accessorKey: 'order',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('order'),
        }, [
            'Order',
            props.filters.sort === 'order' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => h('span', { class: 'text-muted' }, row.original.order),
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
    <Head title="Product Categories" />

    <DashboardLayout>
        <UDashboardPanel id="product-categories">
            <template #header>
                <UDashboardNavbar title="Product Categories">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <ProductCategoryCreateSlideover
                            v-if="can('products.create')"
                            v-model:open="createSlideoverOpen"
                            :languages="languages"
                            :parent-categories="parentCategories"
                        >
                            <UButton icon="i-lucide-plus">
                                Add Category
                            </UButton>
                        </ProductCategoryCreateSlideover>
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
                            v-if="search"
                            color="neutral"
                            variant="ghost"
                            icon="i-lucide-x"
                            @click="clearSearch"
                        />
                    </div>

                    <span class="text-sm text-muted">
                        {{ categories.total }} {{ categories.total !== 1 ? 'categories' : 'category' }}
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
                            v-if="can('products.delete')"
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
                        @update:page="(page) => router.get(cmsPath('/product-categories'), { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="categories.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-folder" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No product categories found.</p>
                    <UButton
                        v-if="can('products.create')"
                        class="mt-4"
                        variant="outline"
                        @click="createSlideoverOpen = true"
                    >
                        Create your first category
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete Category</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ categoryToDelete?.name }}</strong>?
                            </p>
                            <p v-if="categoryToDelete?.products_count" class="mt-1 text-sm text-warning">
                                This category has {{ categoryToDelete.products_count }} products.
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
                                Categories with associated products cannot be deleted.
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

        <!-- Edit Category Slideover -->
        <ProductCategoryEditSlideover
            v-model:open="editSlideoverOpen"
            :category="categoryToEdit"
            :languages="languages"
            :parent-categories="editParentCategories"
            @close="onEditClose"
        />
    </DashboardLayout>
</template>
