<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import type { Language, PaginatedResponse } from '../../types';
import type { TableColumn } from '@nuxt/ui';

interface ProductCategory {
    id: number;
    name: string;
}

interface Tag {
    id: number;
    name: string;
}

interface Product {
    id: number;
    uuid: string;
    title: string;
    slug: string;
    description?: string;
    featured_image_url?: string | null;
    category?: ProductCategory | null;
    price?: number | null;
    currency?: string;
    compare_at_price?: number | null;
    formatted_price?: string | null;
    affiliate_url?: string;
    affiliate_source?: string | null;
    is_active: boolean;
    order: number;
    clicks_count: number;
    created_at: string;
    translated_locales?: string[];
}

const props = defineProps<{
    products: PaginatedResponse<Product>;
    categories: ProductCategory[];
    tags: Tag[];
    filters: {
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
        category_id?: string;
        is_active?: boolean;
    };
    languages: Language[];
}>();

const { can } = usePermission();

const UButton = resolveComponent('UButton');
const UBadge = resolveComponent('UBadge');
const UDropdownMenu = resolveComponent('UDropdownMenu');
const UCheckbox = resolveComponent('UCheckbox');
const UAvatar = resolveComponent('UAvatar');

const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category_id || 'all');
const deleteModalOpen = ref(false);
const productToDelete = ref<Product | null>(null);
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
        .map(([index]) => props.products.data[parseInt(index)]?.id)
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
    router.delete('/cms/products/bulk', {
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
    router.get('/cms/products', {
        search: search.value || undefined,
        category_id: categoryFilter.value === 'all' ? undefined : categoryFilter.value,
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

function onCategoryChange() {
    router.get('/cms/products', {
        search: search.value || undefined,
        category_id: categoryFilter.value === 'all' ? undefined : categoryFilter.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
}

function confirmDelete(product: Product) {
    productToDelete.value = product;
    deleteModalOpen.value = true;
}

function deleteProduct() {
    if (productToDelete.value) {
        router.delete(`/cms/products/${productToDelete.value.uuid}`, {
            onSuccess: () => {
                deleteModalOpen.value = false;
                productToDelete.value = null;
            },
        });
    }
}

function editProduct(product: Product) {
    router.visit(`/cms/products/${product.uuid}/edit`);
}

function sortBy(field: string) {
    const newDirection = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get('/cms/products', {
        search: search.value || undefined,
        category_id: categoryFilter.value || undefined,
        sort: field,
        direction: newDirection,
    }, {
        preserveState: true,
        replace: true,
    });
}

function getRowActions(row: Product) {
    const actions: any[][] = [];

    if (can('products.edit')) {
        actions.push([
            {
                label: 'Edit',
                icon: 'i-lucide-pencil',
                onSelect: () => editProduct(row),
            },
        ]);
    }

    if (row.affiliate_url) {
        actions.push([
            {
                label: 'Visit Affiliate',
                icon: 'i-lucide-external-link',
                onSelect: () => window.open(row.affiliate_url, '_blank'),
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

function formatClickCount(count: number | undefined): string {
    const value = count ?? 0;
    return `${value} click${value !== 1 ? 's' : ''}`;
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

const columns: TableColumn<Product>[] = [
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
            'Product',
            props.filters.sort === 'title' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h('div', { class: 'flex items-center gap-3' }, [
                h(UAvatar, {
                    src: row.original.featured_image_url || undefined,
                    alt: row.original.title,
                    size: 'sm',
                    icon: 'i-lucide-package',
                    class: 'bg-elevated',
                }),
                h('div', {}, [
                    h('div', { class: 'font-medium text-highlighted' }, row.original.title),
                    row.original.affiliate_source && h('span', {
                        class: 'text-xs text-muted',
                    }, row.original.affiliate_source),
                ]),
            ]);
        },
    },
    {
        accessorKey: 'category',
        header: 'Category',
        cell: ({ row }) => {
            if (!row.original.category) {
                return h('span', { class: 'text-muted' }, '—');
            }
            return h(
                UBadge,
                { color: 'neutral', variant: 'subtle' },
                () => row.original.category?.name
            );
        },
    },
    {
        accessorKey: 'price',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('price'),
        }, [
            'Price',
            props.filters.sort === 'price' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            if (!row.original.price) {
                return h('span', { class: 'text-muted' }, '—');
            }
            return h('div', { class: 'flex flex-col' }, [
                h('span', { class: 'font-medium' }, row.original.formatted_price),
                row.original.compare_at_price && h('span', {
                    class: 'text-xs text-muted line-through',
                }, `${row.original.currency || 'USD'} ${row.original.compare_at_price.toFixed(2)}`),
            ]);
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
        accessorKey: 'clicks_count',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('click_count'),
        }, [
            'Clicks',
            props.filters.sort === 'click_count' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h(
                UBadge,
                { color: 'neutral', variant: 'subtle' },
                () => formatClickCount(row.original.clicks_count)
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
    <Head title="Products" />

    <DashboardLayout>
        <UDashboardPanel id="products">
            <template #header>
                <UDashboardNavbar title="Products">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <UButton
                            v-if="can('products.create')"
                            icon="i-lucide-plus"
                            as="a"
                            href="/cms/products/create"
                            @click.prevent="router.visit('/cms/products/create')"
                        >
                            Add Product
                        </UButton>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <UInput
                            v-model="search"
                            placeholder="Search products..."
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
                        <USelectMenu
                            v-model="categoryFilter"
                            :items="[{ value: 'all', label: 'All Categories' }, ...categories.map(c => ({ value: c.id, label: c.name }))]"
                            placeholder="Category"
                            value-key="value"
                            class="w-48"
                            @update:model-value="onCategoryChange"
                        />
                    </div>

                    <span class="text-sm text-muted">
                        {{ products.total }} product{{ products.total !== 1 ? 's' : '' }}
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
                            {{ selectedCount }} {{ selectedCount === 1 ? 'product' : 'products' }} selected
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
                    :data="products.data"
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
                <div v-if="products.last_page > 1" class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-4">
                    <div class="text-sm text-muted">
                        {{ table?.tableApi?.getFilteredSelectedRowModel().rows.length || 0 }} of
                        {{ products.total }} row(s) selected.
                    </div>
                    <UPagination
                        :page="products.current_page"
                        :total="products.total"
                        :items-per-page="products.per_page"
                        @update:page="(page) => router.get('/cms/products', { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="products.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-package" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No products found.</p>
                    <UButton
                        v-if="can('products.create')"
                        class="mt-4"
                        variant="outline"
                        as="a"
                        href="/cms/products/create"
                        @click.prevent="router.visit('/cms/products/create')"
                    >
                        Create your first product
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete Product</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ productToDelete?.title }}</strong>?
                            </p>
                            <p v-if="productToDelete?.clicks_count" class="mt-1 text-sm text-warning">
                                This product has {{ productToDelete.clicks_count }} recorded clicks.
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
                            @click="deleteProduct"
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete {{ selectedCount }} Products</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ selectedCount }}</strong> selected products?
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
                            Delete {{ selectedCount }} Products
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>

    </DashboardLayout>
</template>
