<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import { useCmsPath } from '../../composables/useCmsPath';
import type { Badge, PaginatedResponse, Language } from '../../types';
import type { TableColumn } from '@nuxt/ui';

const props = defineProps<{
    badges: PaginatedResponse<Badge>;
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
const UIcon = resolveComponent('UIcon');

const search = ref(props.filters.search || '');
const deleteModalOpen = ref(false);
const badgeToDelete = ref<Badge | null>(null);
const rowSelection = ref<Record<string, boolean>>({});
const bulkDeleteModalOpen = ref(false);
const bulkDeleting = ref(false);

const selectedCount = computed(() => {
    return Object.values(rowSelection.value).filter(Boolean).length;
});

const selectedIds = computed(() => {
    return Object.entries(rowSelection.value)
        .filter(([_, selected]) => selected)
        .map(([index]) => props.badges.data[parseInt(index)]?.id)
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
    router.delete(cmsPath('/settings/badges/bulk'), {
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
    router.get(cmsPath('/settings/badges'), {
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

function confirmDelete(badge: Badge) {
    badgeToDelete.value = badge;
    deleteModalOpen.value = true;
}

function deleteBadge() {
    if (badgeToDelete.value) {
        router.delete(cmsPath(`/settings/badges/${badgeToDelete.value.uuid}`), {
            onSuccess: () => {
                deleteModalOpen.value = false;
                badgeToDelete.value = null;
            },
        });
    }
}

function sortBy(field: string) {
    const newDirection = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(cmsPath('/settings/badges'), {
        search: search.value || undefined,
        sort: field,
        direction: newDirection,
    }, {
        preserveState: true,
        replace: true,
    });
}

function getRowActions(row: Badge) {
    const actions: any[][] = [];

    if (can('badges.edit')) {
        actions.push([
            {
                label: 'Edit',
                icon: 'i-lucide-pencil',
                to: cmsPath(`/settings/badges/${row.uuid}/edit`),
            },
        ]);
    }

    if (can('badges.delete')) {
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

const columns: TableColumn<Badge>[] = [
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
            return h('div', { class: 'flex items-center gap-2' }, [
                row.original.icon ? h(UIcon, { name: row.original.icon, class: 'size-4' }) : null,
                h('span', { class: 'font-medium text-highlighted' }, row.original.name),
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
        accessorKey: 'color',
        header: 'Color',
        cell: ({ row }) => {
            return h(
                UBadge,
                { color: row.original.color as any, variant: 'subtle', size: 'sm' },
                () => row.original.color
            );
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
                    size: 'sm',
                },
                () => row.original.is_active ? 'Active' : 'Inactive'
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
    <Head title="Badges" />

    <DashboardLayout>
        <UDashboardPanel id="badges">
            <template #header>
                <UDashboardNavbar title="Badges">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <Link v-if="can('badges.create')" :href="cmsPath('/settings/badges/create')">
                            <UButton icon="i-lucide-plus">
                                Add Badge
                            </UButton>
                        </Link>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <UInput
                            v-model="search"
                            placeholder="Search badges..."
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
                        {{ badges.total }} badge{{ badges.total !== 1 ? 's' : '' }}
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
                            {{ selectedCount }} {{ selectedCount === 1 ? 'badge' : 'badges' }} selected
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
                            v-if="can('badges.delete')"
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
                    v-model:row-selection="rowSelection"
                    :data="badges.data"
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
                <div v-if="badges.last_page > 1" class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-4">
                    <div class="text-sm text-muted">
                        Showing {{ badges.from }} to {{ badges.to }} of {{ badges.total }}
                    </div>
                    <UPagination
                        :page="badges.current_page"
                        :total="badges.total"
                        :items-per-page="badges.per_page"
                        @update:page="(page) => router.get(cmsPath('/settings/badges'), { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="badges.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-award" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No badges found.</p>
                    <Link v-if="can('badges.create')" :href="cmsPath('/settings/badges/create')">
                        <UButton
                            class="mt-4"
                            variant="outline"
                        >
                            Create your first badge
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete Badge</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ badgeToDelete?.name }}</strong>?
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
                            @click="deleteBadge"
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete {{ selectedCount }} Badges</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ selectedCount }}</strong> selected badges?
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
                            Delete {{ selectedCount }} Badges
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
