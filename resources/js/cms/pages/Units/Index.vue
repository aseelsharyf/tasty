<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import { useSettingsNav } from '../../composables/useSettingsNav';
import type { PaginatedResponse } from '../../types';
import type { TableColumn } from '@nuxt/ui';

interface Unit {
    id: number;
    name: string;
    name_dv: string | null;
    abbreviation: string;
    abbreviation_dv: string | null;
    is_active: boolean;
    created_at: string;
}

const props = defineProps<{
    units: PaginatedResponse<Unit>;
    filters: {
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
        is_active?: boolean;
    };
}>();

const { can } = usePermission();
const { mainNav } = useSettingsNav();

const UButton = resolveComponent('UButton');
const UBadge = resolveComponent('UBadge');
const UDropdownMenu = resolveComponent('UDropdownMenu');
const UCheckbox = resolveComponent('UCheckbox');

const search = ref(props.filters.search || '');
const deleteModalOpen = ref(false);
const unitToDelete = ref<Unit | null>(null);
const createModalOpen = ref(false);
const editModalOpen = ref(false);
const unitToEdit = ref<Unit | null>(null);
const rowSelection = ref<Record<string, boolean>>({});
const bulkDeleteModalOpen = ref(false);
const bulkDeleting = ref(false);

// Form state
const form = ref({
    name: '',
    name_dv: '',
    abbreviation: '',
    abbreviation_dv: '',
    is_active: true,
});
const formErrors = ref<Record<string, string[]>>({});
const saving = ref(false);

const selectedCount = computed(() => {
    return Object.values(rowSelection.value).filter(Boolean).length;
});

const selectedIds = computed(() => {
    return Object.entries(rowSelection.value)
        .filter(([_, selected]) => selected)
        .map(([index]) => props.units.data[parseInt(index)]?.id)
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
    router.delete('/cms/units/bulk', {
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
    router.get('/cms/units', {
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

function confirmDelete(unit: Unit) {
    unitToDelete.value = unit;
    deleteModalOpen.value = true;
}

function deleteUnit() {
    if (unitToDelete.value) {
        router.delete(`/cms/units/${unitToDelete.value.id}`, {
            onSuccess: () => {
                deleteModalOpen.value = false;
                unitToDelete.value = null;
            },
        });
    }
}

function openCreateModal() {
    form.value = {
        name: '',
        name_dv: '',
        abbreviation: '',
        abbreviation_dv: '',
        is_active: true,
    };
    formErrors.value = {};
    createModalOpen.value = true;
}

async function openEditModal(unit: Unit) {
    try {
        const response = await fetch(`/cms/units/${unit.id}/edit`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) throw new Error('Failed to fetch unit');

        const data = await response.json();
        unitToEdit.value = data.props.unit;
        form.value = {
            name: data.props.unit.name || '',
            name_dv: data.props.unit.name_dv || '',
            abbreviation: data.props.unit.abbreviation || '',
            abbreviation_dv: data.props.unit.abbreviation_dv || '',
            is_active: data.props.unit.is_active,
        };
        formErrors.value = {};
        editModalOpen.value = true;
    } catch (error) {
        console.error('Failed to load unit for editing:', error);
    }
}

function saveUnit() {
    saving.value = true;
    formErrors.value = {};

    router.post('/cms/units', form.value, {
        onSuccess: () => {
            createModalOpen.value = false;
        },
        onError: (errors) => {
            formErrors.value = errors;
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}

function updateUnit() {
    if (!unitToEdit.value) return;

    saving.value = true;
    formErrors.value = {};

    router.put(`/cms/units/${unitToEdit.value.id}`, form.value, {
        onSuccess: () => {
            editModalOpen.value = false;
            unitToEdit.value = null;
        },
        onError: (errors) => {
            formErrors.value = errors;
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}

function sortBy(field: string) {
    const newDirection = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get('/cms/units', {
        search: search.value || undefined,
        sort: field,
        direction: newDirection,
    }, {
        preserveState: true,
        replace: true,
    });
}

function getRowActions(row: Unit) {
    const actions: any[][] = [];

    if (can('settings.edit')) {
        actions.push([
            {
                label: 'Edit',
                icon: 'i-lucide-pencil',
                onSelect: () => openEditModal(row),
            },
        ]);
    }

    if (can('settings.edit')) {
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

const columns: TableColumn<Unit>[] = [
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
            return h('div', {}, [
                h('div', { class: 'font-medium text-highlighted' }, row.original.name),
                row.original.name_dv && h('div', { class: 'text-xs text-muted', dir: 'rtl' }, row.original.name_dv),
            ]);
        },
    },
    {
        accessorKey: 'abbreviation',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('abbreviation'),
        }, [
            'Abbreviation',
            props.filters.sort === 'abbreviation' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h('div', {}, [
                h('span', { class: 'font-mono text-sm' }, row.original.abbreviation),
                row.original.abbreviation_dv && h('span', { class: 'text-xs text-muted ml-2', dir: 'rtl' }, `(${row.original.abbreviation_dv})`),
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
    <Head title="Units" />

    <DashboardLayout>
        <UDashboardPanel id="units">
            <template #header>
                <UDashboardNavbar title="Settings">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <UButton
                            v-if="can('settings.edit')"
                            icon="i-lucide-plus"
                            @click="openCreateModal"
                        >
                            Add Unit
                        </UButton>
                    </template>
                </UDashboardNavbar>

                <UDashboardToolbar>
                    <UNavigationMenu :items="mainNav" highlight class="-mx-1 flex-1 overflow-x-auto" />
                </UDashboardToolbar>
            </template>

            <template #body>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <UInput
                            v-model="search"
                            placeholder="Search units..."
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
                        {{ units.total }} unit{{ units.total !== 1 ? 's' : '' }}
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
                            {{ selectedCount }} {{ selectedCount === 1 ? 'unit' : 'units' }} selected
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
                            v-if="can('settings.edit')"
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
                    :data="units.data"
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
                <div v-if="units.last_page > 1" class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-4">
                    <div class="text-sm text-muted">
                        Showing {{ units.from }} to {{ units.to }} of {{ units.total }}
                    </div>
                    <UPagination
                        :page="units.current_page"
                        :total="units.total"
                        :items-per-page="units.per_page"
                        @update:page="(page) => router.get('/cms/units', { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="units.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-scale" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No units found.</p>
                    <UButton
                        v-if="can('settings.edit')"
                        class="mt-4"
                        variant="outline"
                        @click="openCreateModal"
                    >
                        Create your first unit
                    </UButton>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Create Modal -->
        <UModal v-model:open="createModalOpen">
            <template #content>
                <UCard>
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold">Add Unit</h3>
                            <UButton
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-x"
                                @click="createModalOpen = false"
                            />
                        </div>
                    </template>

                    <div class="space-y-4">
                        <UFormField label="Name (English)" :error="formErrors.name?.[0]" required>
                            <UInput v-model="form.name" placeholder="e.g., Cup" />
                        </UFormField>

                        <UFormField label="Name (Dhivehi)" :error="formErrors.name_dv?.[0]">
                            <UInput v-model="form.name_dv" placeholder="ދިވެހި ނަން" dir="rtl" />
                        </UFormField>

                        <UFormField label="Abbreviation" :error="formErrors.abbreviation?.[0]" required>
                            <UInput v-model="form.abbreviation" placeholder="e.g., cup" />
                        </UFormField>

                        <UFormField label="Abbreviation (Dhivehi)" :error="formErrors.abbreviation_dv?.[0]">
                            <UInput v-model="form.abbreviation_dv" placeholder="ދިވެހި އަކުރު" dir="rtl" />
                        </UFormField>

                        <UFormField>
                            <UCheckbox v-model="form.is_active" label="Active" />
                        </UFormField>
                    </div>

                    <template #footer>
                        <div class="flex justify-end gap-3">
                            <UButton
                                color="neutral"
                                variant="outline"
                                @click="createModalOpen = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                :loading="saving"
                                @click="saveUnit"
                            >
                                Create Unit
                            </UButton>
                        </div>
                    </template>
                </UCard>
            </template>
        </UModal>

        <!-- Edit Modal -->
        <UModal v-model:open="editModalOpen">
            <template #content>
                <UCard>
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold">Edit Unit</h3>
                            <UButton
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-x"
                                @click="editModalOpen = false"
                            />
                        </div>
                    </template>

                    <div class="space-y-4">
                        <UFormField label="Name (English)" :error="formErrors.name?.[0]" required>
                            <UInput v-model="form.name" placeholder="e.g., Cup" />
                        </UFormField>

                        <UFormField label="Name (Dhivehi)" :error="formErrors.name_dv?.[0]">
                            <UInput v-model="form.name_dv" placeholder="ދިވެހި ނަން" dir="rtl" />
                        </UFormField>

                        <UFormField label="Abbreviation" :error="formErrors.abbreviation?.[0]" required>
                            <UInput v-model="form.abbreviation" placeholder="e.g., cup" />
                        </UFormField>

                        <UFormField label="Abbreviation (Dhivehi)" :error="formErrors.abbreviation_dv?.[0]">
                            <UInput v-model="form.abbreviation_dv" placeholder="ދިވެހި އަކުރު" dir="rtl" />
                        </UFormField>

                        <UFormField>
                            <UCheckbox v-model="form.is_active" label="Active" />
                        </UFormField>
                    </div>

                    <template #footer>
                        <div class="flex justify-end gap-3">
                            <UButton
                                color="neutral"
                                variant="outline"
                                @click="editModalOpen = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                :loading="saving"
                                @click="updateUnit"
                            >
                                Save Changes
                            </UButton>
                        </div>
                    </template>
                </UCard>
            </template>
        </UModal>

        <!-- Delete Confirmation Modal -->
        <UModal v-model:open="deleteModalOpen">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center size-12 rounded-full bg-error/10 shrink-0">
                            <UIcon name="i-lucide-alert-triangle" class="size-6 text-error" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-highlighted">Delete Unit</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ unitToDelete?.name }}</strong>?
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
                            @click="deleteUnit"
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete {{ selectedCount }} Units</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ selectedCount }}</strong> selected units?
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
                            Delete {{ selectedCount }} Units
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
