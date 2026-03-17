<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import { useCmsPath } from '../../composables/useCmsPath';
import type { PaginatedResponse } from '../../types';
import type { TableColumn } from '@nuxt/ui';

interface Order {
    id: number;
    uuid: string;
    order_number: string;
    status: string;
    status_label: string;
    status_color: string;
    payment_status: string;
    payment_status_label: string;
    payment_status_color: string;
    total: number;
    currency: string;
    contact_person: string;
    contact_number: string;
    items_count: number;
    has_affiliate_products: boolean;
    created_at: string;
}

interface StatusOption {
    value: string;
    label: string;
}

interface Stats {
    total_orders: number;
    pending_orders: number;
    total_revenue: number;
    unpaid_orders: number;
}

const props = defineProps<{
    orders: PaginatedResponse<Order>;
    filters: {
        search?: string;
        status?: string;
        payment_status?: string;
    };
    statusOptions: StatusOption[];
    stats: Stats;
}>();

const { can } = usePermission();
const { cmsPath } = useCmsPath();

const UButton = resolveComponent('UButton');
const UBadge = resolveComponent('UBadge');

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const showStats = ref(localStorage.getItem('orders_show_stats') !== 'false');

function toggleStats() {
    showStats.value = !showStats.value;
    localStorage.setItem('orders_show_stats', String(showStats.value));
}

function onSearch() {
    router.get(cmsPath('/orders'), {
        search: search.value || undefined,
        status: statusFilter.value === 'all' ? undefined : statusFilter.value,
    }, { preserveState: true, replace: true });
}

function onStatusChange() {
    onSearch();
}

function viewOrder(order: Order) {
    router.visit(cmsPath(`/orders/${order.uuid}`));
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

const columns: TableColumn<Order>[] = [
    {
        accessorKey: 'order_number',
        header: 'Order',
        cell: ({ row }) => h('div', {}, [
            h('span', { class: 'font-mono font-medium text-highlighted text-sm cursor-pointer hover:text-primary', onClick: () => viewOrder(row.original) }, row.original.order_number),
            h('div', { class: 'text-xs text-muted mt-0.5' }, formatDate(row.original.created_at)),
        ]),
    },
    {
        accessorKey: 'contact_person',
        header: 'Customer',
        cell: ({ row }) => h('div', {}, [
            h('span', { class: 'font-medium' }, row.original.contact_person),
            h('div', { class: 'text-xs text-muted' }, row.original.contact_number),
        ]),
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => h(
            UBadge,
            { color: row.original.status_color as any, variant: 'subtle' },
            () => row.original.status_label
        ),
    },
    {
        accessorKey: 'payment_status',
        header: 'Payment',
        cell: ({ row }) => h(
            UBadge,
            { color: row.original.payment_status_color as any, variant: 'subtle' },
            () => row.original.payment_status_label
        ),
    },
    {
        accessorKey: 'total',
        header: 'Total',
        cell: ({ row }) => h('span', { class: 'font-medium' }, `${Number(row.original.total).toFixed(2)} ${row.original.currency}`),
    },
    {
        accessorKey: 'items_count',
        header: 'Items',
        cell: ({ row }) => h('span', { class: 'text-muted' }, `${row.original.items_count}`),
    },
    {
        id: 'actions',
        cell: ({ row }) => h(UButton, {
            icon: 'i-lucide-eye',
            color: 'neutral',
            variant: 'ghost',
            size: 'sm',
            onClick: () => viewOrder(row.original),
        }),
    },
];
</script>

<template>
    <Head title="Orders" />

    <DashboardLayout>
        <UDashboardPanel id="orders">
            <template #header>
                <UDashboardNavbar title="Orders">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <!-- Stats Widgets -->
                <div class="relative mb-6">
                    <button
                        @click="toggleStats"
                        class="absolute top-2 right-2 z-10 p-1.5 rounded-md text-muted hover:text-highlighted hover:bg-elevated transition"
                        :title="showStats ? 'Hide numbers' : 'Show numbers'"
                    >
                        <UIcon :name="showStats ? 'i-lucide-eye' : 'i-lucide-eye-off'" class="size-4" />
                    </button>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-elevated/50 border border-default rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center size-10 rounded-lg bg-primary/10">
                                    <UIcon name="i-lucide-shopping-cart" class="size-5 text-primary" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-highlighted">{{ showStats ? stats.total_orders : '••••' }}</p>
                                    <p class="text-xs text-muted">Total Orders</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-elevated/50 border border-default rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center size-10 rounded-lg bg-warning/10">
                                    <UIcon name="i-lucide-clock" class="size-5 text-warning" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-highlighted">{{ showStats ? stats.pending_orders : '••••' }}</p>
                                    <p class="text-xs text-muted">Pending</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-elevated/50 border border-default rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center size-10 rounded-lg bg-success/10">
                                    <UIcon name="i-lucide-banknote" class="size-5 text-success" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-highlighted">{{ showStats ? Number(stats.total_revenue).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '••••' }}</p>
                                    <p class="text-xs text-muted">Revenue (MVR)</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-elevated/50 border border-default rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center size-10 rounded-lg bg-error/10">
                                    <UIcon name="i-lucide-alert-circle" class="size-5 text-error" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-highlighted">{{ showStats ? stats.unpaid_orders : '••••' }}</p>
                                    <p class="text-xs text-muted">Unpaid</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <UInput
                            v-model="search"
                            placeholder="Search orders..."
                            icon="i-lucide-search"
                            :ui="{ base: 'w-64' }"
                            @keyup.enter="onSearch"
                        />
                        <USelectMenu
                            v-model="statusFilter"
                            :items="[{ value: 'all', label: 'All Statuses' }, ...statusOptions]"
                            value-key="value"
                            class="w-48"
                            @update:model-value="onStatusChange"
                        />
                    </div>
                    <span class="text-sm text-muted">
                        {{ orders.total }} order{{ orders.total !== 1 ? 's' : '' }}
                    </span>
                </div>

                <UTable
                    :data="orders.data"
                    :columns="columns"
                    :ui="{
                        base: 'table-fixed border-separate border-spacing-0',
                        thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                        tbody: '[&>tr]:last:[&>td]:border-b-0',
                        th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
                        td: 'border-b border-default',
                    }"
                />

                <div v-if="orders.last_page > 1" class="flex items-center justify-end pt-4 mt-4 border-t border-default">
                    <UPagination
                        :page="orders.current_page"
                        :total="orders.total"
                        :items-per-page="orders.per_page"
                        @update:page="(page) => router.get(cmsPath('/orders'), { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <div v-if="orders.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-shopping-cart" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No orders found.</p>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
