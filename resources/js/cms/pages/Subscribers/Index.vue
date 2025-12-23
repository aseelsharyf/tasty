<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import type { TableColumn } from '@nuxt/ui';

interface Subscriber {
    id: number;
    email: string;
    status: 'active' | 'inactive';
    subscribed_at: string | null;
    unsubscribed_at: string | null;
    created_at: string;
}

interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    subscribers: PaginatedResponse<Subscriber>;
    filters: {
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
        status?: string;
    };
    stats: {
        total: number;
        active: number;
        inactive: number;
    };
}>();

const { can } = usePermission();

const UButton = resolveComponent('UButton');
const UBadge = resolveComponent('UBadge');
const UDropdownMenu = resolveComponent('UDropdownMenu');
const UCheckbox = resolveComponent('UCheckbox');

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const deleteModalOpen = ref(false);
const subscriberToDelete = ref<Subscriber | null>(null);
const rowSelection = ref<Record<string, boolean>>({});
const bulkDeleteModalOpen = ref(false);
const bulkStatusModalOpen = ref(false);
const bulkDeleting = ref(false);
const bulkStatusAction = ref<'active' | 'inactive'>('active');

const table = ref<{ tableApi?: any } | null>(null);

const selectedCount = computed(() => {
    return Object.values(rowSelection.value).filter(Boolean).length;
});

const selectedIds = computed(() => {
    return Object.entries(rowSelection.value)
        .filter(([_, selected]) => selected)
        .map(([index]) => props.subscribers.data[parseInt(index)]?.id)
        .filter(Boolean);
});

function clearSelection() {
    rowSelection.value = {};
}

function onSearch() {
    router.get('/cms/subscribers', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
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

function onStatusFilterChange(status: string) {
    statusFilter.value = status;
    router.get('/cms/subscribers', {
        search: search.value || undefined,
        status: status || undefined,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
}

function sortBy(field: string) {
    const newDirection = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get('/cms/subscribers', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        sort: field,
        direction: newDirection,
    }, {
        preserveState: true,
        replace: true,
    });
}

function toggleStatus(subscriber: Subscriber) {
    router.post(`/cms/subscribers/${subscriber.id}/toggle-status`, {}, {
        preserveState: true,
    });
}

function confirmDelete(subscriber: Subscriber) {
    subscriberToDelete.value = subscriber;
    deleteModalOpen.value = true;
}

function deleteSubscriber() {
    if (subscriberToDelete.value) {
        router.delete(`/cms/subscribers/${subscriberToDelete.value.id}`, {
            onSuccess: () => {
                deleteModalOpen.value = false;
                subscriberToDelete.value = null;
            },
        });
    }
}

function confirmBulkDelete() {
    bulkDeleteModalOpen.value = true;
}

function bulkDelete() {
    if (selectedIds.value.length === 0) return;

    bulkDeleting.value = true;
    router.delete('/cms/subscribers/bulk', {
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

function confirmBulkStatus(status: 'active' | 'inactive') {
    bulkStatusAction.value = status;
    bulkStatusModalOpen.value = true;
}

function bulkStatus() {
    if (selectedIds.value.length === 0) return;

    router.post('/cms/subscribers/bulk-status', {
        ids: selectedIds.value,
        status: bulkStatusAction.value,
    }, {
        onSuccess: () => {
            bulkStatusModalOpen.value = false;
            rowSelection.value = {};
        },
    });
}

function exportSubscribers() {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (statusFilter.value) params.append('status', statusFilter.value);

    window.location.href = `/cms/subscribers/export?${params.toString()}`;
}

function getRowActions(row: Subscriber) {
    const actions: any[][] = [];

    actions.push([
        {
            label: row.status === 'active' ? 'Deactivate' : 'Activate',
            icon: row.status === 'active' ? 'i-lucide-user-x' : 'i-lucide-user-check',
            onSelect: () => toggleStatus(row),
        },
    ]);

    actions.push([
        {
            label: 'Delete',
            icon: 'i-lucide-trash',
            color: 'error' as const,
            onSelect: () => confirmDelete(row),
        },
    ]);

    return actions;
}

const columns: TableColumn<Subscriber>[] = [
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
        accessorKey: 'email',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('email'),
        }, [
            'Email',
            props.filters.sort === 'email' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h('div', { class: 'font-medium text-highlighted' }, row.original.email);
        },
    },
    {
        accessorKey: 'status',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('status'),
        }, [
            'Status',
            props.filters.sort === 'status' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            const isActive = row.original.status === 'active';
            return h(
                UBadge,
                {
                    color: isActive ? 'success' : 'neutral',
                    variant: 'subtle',
                },
                () => isActive ? 'Active' : 'Inactive'
            );
        },
    },
    {
        accessorKey: 'subscribed_at',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('subscribed_at'),
        }, [
            'Subscribed',
            props.filters.sort === 'subscribed_at' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h('span', { class: 'text-muted text-sm' }, row.original.subscribed_at || '-');
        },
    },
    {
        accessorKey: 'created_at',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('created_at'),
        }, [
            'Created',
            props.filters.sort === 'created_at' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h('span', { class: 'text-muted text-sm' }, row.original.created_at);
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

const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
];
</script>

<template>
    <Head title="Subscribers" />

    <DashboardLayout>
        <UDashboardPanel id="subscribers">
            <template #header>
                <UDashboardNavbar title="Newsletter Subscribers">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <UButton
                            icon="i-lucide-download"
                            color="neutral"
                            variant="outline"
                            @click="exportSubscribers"
                        >
                            Export CSV
                        </UButton>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <!-- Stats Cards -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="p-4 rounded-lg bg-elevated border border-default">
                        <div class="text-sm text-muted">Total Subscribers</div>
                        <div class="text-2xl font-semibold text-highlighted">{{ stats.total }}</div>
                    </div>
                    <div class="p-4 rounded-lg bg-elevated border border-default">
                        <div class="text-sm text-muted">Active</div>
                        <div class="text-2xl font-semibold text-success">{{ stats.active }}</div>
                    </div>
                    <div class="p-4 rounded-lg bg-elevated border border-default">
                        <div class="text-sm text-muted">Inactive</div>
                        <div class="text-2xl font-semibold text-muted">{{ stats.inactive }}</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <UInput
                            v-model="search"
                            placeholder="Search by email..."
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
                            :model-value="statusFilter"
                            :items="statusOptions"
                            value-key="value"
                            placeholder="Status"
                            class="w-32"
                            @update:model-value="onStatusFilterChange"
                        />
                    </div>

                    <span class="text-sm text-muted">
                        {{ subscribers.total }} subscriber{{ subscribers.total !== 1 ? 's' : '' }}
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
                            {{ selectedCount }} {{ selectedCount === 1 ? 'subscriber' : 'subscribers' }} selected
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
                            icon="i-lucide-user-check"
                            @click="confirmBulkStatus('active')"
                        >
                            Activate
                        </UButton>
                        <UButton
                            color="neutral"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-user-x"
                            @click="confirmBulkStatus('inactive')"
                        >
                            Deactivate
                        </UButton>
                        <UButton
                            color="error"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-trash"
                            @click="confirmBulkDelete"
                        >
                            Delete
                        </UButton>
                    </div>
                </div>

                <UTable
                    ref="table"
                    v-model:row-selection="rowSelection"
                    :data="subscribers.data"
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
                <div v-if="subscribers.last_page > 1" class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-4">
                    <div class="text-sm text-muted">
                        {{ table?.tableApi?.getFilteredSelectedRowModel().rows.length || 0 }} of
                        {{ subscribers.total }} row(s) selected.
                    </div>
                    <UPagination
                        :page="subscribers.current_page"
                        :total="subscribers.total"
                        :items-per-page="subscribers.per_page"
                        @update:page="(page) => router.get('/cms/subscribers', { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="subscribers.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-mail" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No subscribers found.</p>
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete Subscriber</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ subscriberToDelete?.email }}</strong>?
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
                            @click="deleteSubscriber"
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete {{ selectedCount }} Subscribers</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ selectedCount }}</strong> selected subscribers?
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
                            Delete {{ selectedCount }} Subscribers
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>

        <!-- Bulk Status Confirmation Modal -->
        <UModal v-model:open="bulkStatusModalOpen">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center size-12 rounded-full shrink-0" :class="bulkStatusAction === 'active' ? 'bg-success/10' : 'bg-neutral/10'">
                            <UIcon :name="bulkStatusAction === 'active' ? 'i-lucide-user-check' : 'i-lucide-user-x'" class="size-6" :class="bulkStatusAction === 'active' ? 'text-success' : 'text-muted'" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-highlighted">
                                {{ bulkStatusAction === 'active' ? 'Activate' : 'Deactivate' }} {{ selectedCount }} Subscribers
                            </h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to {{ bulkStatusAction === 'active' ? 'activate' : 'deactivate' }} <strong class="text-highlighted">{{ selectedCount }}</strong> selected subscribers?
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <UButton
                            color="neutral"
                            variant="outline"
                            @click="bulkStatusModalOpen = false"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            :color="bulkStatusAction === 'active' ? 'success' : 'neutral'"
                            @click="bulkStatus"
                        >
                            {{ bulkStatusAction === 'active' ? 'Activate' : 'Deactivate' }} {{ selectedCount }} Subscribers
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
