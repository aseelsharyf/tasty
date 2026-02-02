<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import ProductStoreCreateSlideover from '../../components/ProductStoreCreateSlideover.vue';
import ProductStoreEditSlideover from '../../components/ProductStoreEditSlideover.vue';
import { usePermission } from '../../composables/usePermission';
import { useCmsPath } from '../../composables/useCmsPath';
import type { PaginatedResponse } from '../../types';
import type { TableColumn } from '@nuxt/ui';

interface MediaItem {
    id: number;
    url: string;
    title?: string;
}

interface ProductStore {
    id: number;
    uuid: string;
    name: string;
    business_type?: string | null;
    address?: string | null;
    location_label?: string | null;
    hotline?: string | null;
    contact_email?: string | null;
    website_url?: string | null;
    logo_url?: string | null;
    logo_media_id?: number | null;
    logo?: MediaItem | null;
    is_active: boolean;
    order: number;
    products_count: number;
    created_at: string;
}

const props = defineProps<{
    stores: PaginatedResponse<ProductStore>;
    filters: {
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
        is_active?: boolean;
    };
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
const storeToDelete = ref<ProductStore | null>(null);
const createSlideoverOpen = ref(false);
const editSlideoverOpen = ref(false);
const storeToEdit = ref<ProductStore | null>(null);
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
        .map(([index]) => props.stores.data[parseInt(index)]?.id)
        .filter(Boolean);
});

const businessTypeLabels: Record<string, string> = {
    retail: 'Retail',
    distributor: 'Distributor',
    restaurant: 'Restaurant',
};

function clearSelection() {
    rowSelection.value = {};
}

function confirmBulkDelete() {
    bulkDeleteModalOpen.value = true;
}

function bulkDelete() {
    if (selectedIds.value.length === 0) return;

    bulkDeleting.value = true;
    router.delete(cmsPath('/product-stores/bulk'), {
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
    router.get(cmsPath('/product-stores'), {
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

function confirmDelete(store: ProductStore) {
    storeToDelete.value = store;
    deleteModalOpen.value = true;
}

function deleteStore() {
    if (storeToDelete.value) {
        router.delete(cmsPath(`/product-stores/${storeToDelete.value.uuid}`), {
            onSuccess: () => {
                deleteModalOpen.value = false;
                storeToDelete.value = null;
            },
        });
    }
}

async function openEditSlideover(store: ProductStore) {
    try {
        const response = await fetch(cmsPath(`/product-stores/${store.uuid}/edit`), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) throw new Error('Failed to fetch client');

        const data = await response.json();
        storeToEdit.value = data.props.store;
        editSlideoverOpen.value = true;
    } catch (error) {
        console.error('Failed to load client for editing:', error);
    }
}

function onEditClose(updated: boolean) {
    editSlideoverOpen.value = false;
    storeToEdit.value = null;
    if (updated) {
        router.reload({ only: ['stores'] });
    }
}

function sortBy(field: string) {
    const newDirection = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(cmsPath('/product-stores'), {
        search: search.value || undefined,
        sort: field,
        direction: newDirection,
    }, {
        preserveState: true,
        replace: true,
    });
}

function getRowActions(row: ProductStore) {
    const actions: any[][] = [];

    if (can('product-stores.edit')) {
        actions.push([
            {
                label: 'Edit',
                icon: 'i-lucide-pencil',
                onSelect: () => openEditSlideover(row),
            },
        ]);
    }

    if (can('product-stores.delete')) {
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

const columns: TableColumn<ProductStore>[] = [
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
            'Client',
            props.filters.sort === 'name' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h('div', { class: 'flex items-center gap-3' }, [
                h(UAvatar, {
                    src: row.original.logo_url || undefined,
                    alt: row.original.name,
                    size: 'sm',
                    icon: 'i-lucide-building-2',
                    class: 'bg-elevated',
                }),
                h('div', {}, [
                    h('div', { class: 'font-medium text-highlighted' }, row.original.name),
                    row.original.location_label && h('p', {
                        class: 'text-xs text-muted',
                    }, row.original.location_label),
                ]),
            ]);
        },
    },
    {
        accessorKey: 'business_type',
        header: 'Type',
        cell: ({ row }) => {
            if (!row.original.business_type) return h('span', { class: 'text-muted' }, '-');
            return h(
                UBadge,
                { color: 'neutral', variant: 'subtle' },
                () => businessTypeLabels[row.original.business_type!] || row.original.business_type
            );
        },
    },
    {
        accessorKey: 'hotline',
        header: 'Hotline',
        cell: ({ row }) => {
            if (!row.original.hotline) return h('span', { class: 'text-muted' }, '-');
            return h('span', { class: 'text-muted font-mono text-sm' }, row.original.hotline);
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
    <Head title="Clients" />

    <DashboardLayout>
        <UDashboardPanel id="product-stores">
            <template #header>
                <UDashboardNavbar title="Clients">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <ProductStoreCreateSlideover
                            v-if="can('product-stores.create')"
                            v-model:open="createSlideoverOpen"
                        >
                            <UButton icon="i-lucide-plus">
                                Add Client
                            </UButton>
                        </ProductStoreCreateSlideover>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <UInput
                            v-model="search"
                            placeholder="Search clients..."
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
                        {{ stores.total }} {{ stores.total !== 1 ? 'clients' : 'client' }}
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
                            {{ selectedCount }} {{ selectedCount === 1 ? 'client' : 'clients' }} selected
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
                            v-if="can('product-stores.delete')"
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
                    :data="stores.data"
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
                <div v-if="stores.last_page > 1" class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-4">
                    <div class="text-sm text-muted">
                        {{ table?.tableApi?.getFilteredSelectedRowModel().rows.length || 0 }} of
                        {{ stores.total }} row(s) selected.
                    </div>
                    <UPagination
                        :page="stores.current_page"
                        :total="stores.total"
                        :items-per-page="stores.per_page"
                        @update:page="(page) => router.get(cmsPath('/product-stores'), { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="stores.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-building-2" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No clients found.</p>
                    <UButton
                        v-if="can('product-stores.create')"
                        class="mt-4"
                        variant="outline"
                        @click="createSlideoverOpen = true"
                    >
                        Add your first client
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete Client</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ storeToDelete?.name }}</strong>?
                            </p>
                            <p v-if="storeToDelete?.products_count" class="mt-1 text-sm text-warning">
                                This client has {{ storeToDelete.products_count }} products.
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
                            @click="deleteStore"
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete {{ selectedCount }} Clients</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ selectedCount }}</strong> selected clients?
                                This action cannot be undone.
                            </p>
                            <p class="mt-1 text-sm text-warning">
                                Clients with associated products cannot be deleted.
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
                            Delete {{ selectedCount }} Clients
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>

        <!-- Edit Store Slideover -->
        <ProductStoreEditSlideover
            v-model:open="editSlideoverOpen"
            :store="storeToEdit"
            @close="onEditClose"
        />
    </DashboardLayout>
</template>
