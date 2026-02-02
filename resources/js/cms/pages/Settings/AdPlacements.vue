<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import { useSettingsNav } from '../../composables/useSettingsNav';
import { useCmsPath } from '../../composables/useCmsPath';
import type { PaginatedResponse } from '../../types';
import type { TableColumn } from '@nuxt/ui';

interface Category {
    id: number;
    name: string;
}

interface Slot {
    value: string;
    label: string;
}

interface PageType {
    value: string;
    label: string;
}

interface AdPlacement {
    id: number;
    uuid: string;
    name: string;
    page_type: string;
    page_type_label: string;
    slot: string;
    slot_label: string;
    category_id: number | null;
    category_name: string | null;
    ad_code: string;
    is_active: boolean;
    priority: number;
    created_at: string;
}

const props = defineProps<{
    adPlacements: PaginatedResponse<AdPlacement>;
    filters: {
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
        slot?: string;
        category_id?: string | number;
        is_active?: boolean;
    };
    categories: Category[];
    slots: Slot[];
    pageTypes: PageType[];
}>();

const { can } = usePermission();
const { mainNav } = useSettingsNav();
const { cmsPath } = useCmsPath();

const UButton = resolveComponent('UButton');
const UBadge = resolveComponent('UBadge');
const UDropdownMenu = resolveComponent('UDropdownMenu');
const UCheckbox = resolveComponent('UCheckbox');

const search = ref(props.filters.search || '');
const deleteModalOpen = ref(false);
const adPlacementToDelete = ref<AdPlacement | null>(null);
const createModalOpen = ref(false);
const editModalOpen = ref(false);
const adPlacementToEdit = ref<AdPlacement | null>(null);
const rowSelection = ref<Record<string, boolean>>({});
const bulkDeleteModalOpen = ref(false);
const bulkDeleting = ref(false);

// Form state
const form = ref({
    name: '',
    page_type: 'article_detail',
    slot: 'after_header',
    category_id: null as number | null,
    ad_code: '',
    is_active: true,
    priority: 0,
});
const formErrors = ref<Record<string, string[]>>({});
const saving = ref(false);

const selectedCount = computed(() => {
    return Object.values(rowSelection.value).filter(Boolean).length;
});

const selectedIds = computed(() => {
    return Object.entries(rowSelection.value)
        .filter(([_, selected]) => selected)
        .map(([index]) => props.adPlacements.data[parseInt(index)]?.id)
        .filter(Boolean);
});

const categoryOptions = computed(() => {
    return [
        { value: null, label: 'All Categories (Global)' },
        ...props.categories.map(c => ({ value: c.id, label: c.name })),
    ];
});

const slotOptions = computed(() => {
    return props.slots.map(s => ({ value: s.value, label: s.label }));
});

const pageTypeOptions = computed(() => {
    return props.pageTypes.map(p => ({ value: p.value, label: p.label }));
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
    router.delete(cmsPath('/ad-placements/bulk'), {
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
    router.get(cmsPath('/ad-placements'), {
        search: search.value || undefined,
        sort: props.filters.sort,
        direction: props.filters.direction,
        slot: props.filters.slot,
        category_id: props.filters.category_id,
        is_active: props.filters.is_active,
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearSearch() {
    search.value = '';
    onSearch();
}

function confirmDelete(adPlacement: AdPlacement) {
    adPlacementToDelete.value = adPlacement;
    deleteModalOpen.value = true;
}

function deleteAdPlacement() {
    if (adPlacementToDelete.value) {
        router.delete(cmsPath(`/ad-placements/${adPlacementToDelete.value.id}`), {
            onSuccess: () => {
                deleteModalOpen.value = false;
                adPlacementToDelete.value = null;
            },
        });
    }
}

function openCreateModal() {
    form.value = {
        name: '',
        page_type: 'article_detail',
        slot: 'after_header',
        category_id: null,
        ad_code: '',
        is_active: true,
        priority: 0,
    };
    formErrors.value = {};
    createModalOpen.value = true;
}

async function openEditModal(adPlacement: AdPlacement) {
    try {
        const response = await fetch(cmsPath(`/ad-placements/${adPlacement.id}/edit`), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) throw new Error('Failed to fetch ad placement');

        const data = await response.json();
        adPlacementToEdit.value = data.props.adPlacement;
        form.value = {
            name: data.props.adPlacement.name || '',
            page_type: data.props.adPlacement.page_type || 'article_detail',
            slot: data.props.adPlacement.slot || 'after_header',
            category_id: data.props.adPlacement.category_id,
            ad_code: data.props.adPlacement.ad_code || '',
            is_active: data.props.adPlacement.is_active,
            priority: data.props.adPlacement.priority || 0,
        };
        formErrors.value = {};
        editModalOpen.value = true;
    } catch (error) {
        console.error('Failed to load ad placement for editing:', error);
    }
}

function saveAdPlacement() {
    saving.value = true;
    formErrors.value = {};

    router.post(cmsPath('/ad-placements'), form.value, {
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

function updateAdPlacement() {
    if (!adPlacementToEdit.value) return;

    saving.value = true;
    formErrors.value = {};

    router.put(cmsPath(`/ad-placements/${adPlacementToEdit.value.id}`), form.value, {
        onSuccess: () => {
            editModalOpen.value = false;
            adPlacementToEdit.value = null;
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
    router.get(cmsPath('/ad-placements'), {
        search: search.value || undefined,
        sort: field,
        direction: newDirection,
        slot: props.filters.slot,
        category_id: props.filters.category_id,
        is_active: props.filters.is_active,
    }, {
        preserveState: true,
        replace: true,
    });
}

function getRowActions(row: AdPlacement) {
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

const columns: TableColumn<AdPlacement>[] = [
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
        accessorKey: 'slot',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('slot'),
        }, [
            'Slot',
            props.filters.sort === 'slot' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h('span', { class: 'text-sm' }, row.original.slot_label);
        },
    },
    {
        accessorKey: 'category_name',
        header: 'Category',
        cell: ({ row }) => {
            return h(
                UBadge,
                {
                    color: row.original.category_id ? 'info' : 'neutral',
                    variant: 'subtle',
                },
                () => row.original.category_name || 'All Categories'
            );
        },
    },
    {
        accessorKey: 'priority',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('priority'),
        }, [
            'Priority',
            props.filters.sort === 'priority' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h('span', { class: 'text-sm text-muted' }, row.original.priority.toString());
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
    <Head title="Ad Placements" />

    <DashboardLayout>
        <UDashboardPanel id="ad-placements">
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
                            Add Ad Placement
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
                            placeholder="Search ad placements..."
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
                        {{ adPlacements.total }} ad placement{{ adPlacements.total !== 1 ? 's' : '' }}
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
                            {{ selectedCount }} {{ selectedCount === 1 ? 'ad placement' : 'ad placements' }} selected
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
                    :data="adPlacements.data"
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
                <div v-if="adPlacements.last_page > 1" class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-4">
                    <div class="text-sm text-muted">
                        Showing {{ adPlacements.from }} to {{ adPlacements.to }} of {{ adPlacements.total }}
                    </div>
                    <UPagination
                        :page="adPlacements.current_page"
                        :total="adPlacements.total"
                        :items-per-page="adPlacements.per_page"
                        @update:page="(page) => router.get(cmsPath('/ad-placements'), { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="adPlacements.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-megaphone" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No ad placements found.</p>
                    <UButton
                        v-if="can('settings.edit')"
                        class="mt-4"
                        variant="outline"
                        @click="openCreateModal"
                    >
                        Create your first ad placement
                    </UButton>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Create Modal -->
        <UModal v-model:open="createModalOpen" :ui="{ content: 'max-w-2xl' }">
            <template #content>
                <UCard>
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold">Add Ad Placement</h3>
                            <UButton
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-x"
                                @click="createModalOpen = false"
                            />
                        </div>
                    </template>

                    <div class="space-y-4">
                        <UFormField label="Name" :error="formErrors.name?.[0]" required hint="Internal name for this ad placement">
                            <UInput v-model="form.name" placeholder="e.g., Homepage Header Ad" class="w-full" />
                        </UFormField>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <UFormField label="Page Type" :error="formErrors.page_type?.[0]" required>
                                <USelect
                                    v-model="form.page_type"
                                    :items="pageTypeOptions"
                                    value-key="value"
                                    label-key="label"
                                    class="w-full"
                                />
                            </UFormField>

                            <UFormField label="Slot" :error="formErrors.slot?.[0]" required>
                                <USelect
                                    v-model="form.slot"
                                    :items="slotOptions"
                                    value-key="value"
                                    label-key="label"
                                    class="w-full"
                                />
                            </UFormField>

                            <UFormField label="Category" :error="formErrors.category_id?.[0]">
                                <USelect
                                    v-model="form.category_id"
                                    :items="categoryOptions"
                                    value-key="value"
                                    label-key="label"
                                    class="w-full"
                                />
                            </UFormField>
                        </div>

                        <UFormField label="Ad Code" :error="formErrors.ad_code?.[0]" required hint="Paste your HTML/JS ad code here">
                            <UTextarea
                                v-model="form.ad_code"
                                placeholder="<script>...</script> or <div>...</div>"
                                :rows="6"
                                class="font-mono text-sm w-full"
                            />
                        </UFormField>

                        <div class="flex items-center gap-6">
                            <UFormField label="Priority" :error="formErrors.priority?.[0]">
                                <UInput v-model.number="form.priority" type="number" min="0" max="999" class="w-24" />
                            </UFormField>

                            <UCheckbox v-model="form.is_active" label="Active" class="mt-6" />
                        </div>
                        <p class="text-sm text-muted -mt-2">Higher priority ads are shown first when multiple ads match the same slot.</p>
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
                                @click="saveAdPlacement"
                            >
                                Create Ad Placement
                            </UButton>
                        </div>
                    </template>
                </UCard>
            </template>
        </UModal>

        <!-- Edit Modal -->
        <UModal v-model:open="editModalOpen" :ui="{ content: 'max-w-2xl' }">
            <template #content>
                <UCard>
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold">Edit Ad Placement</h3>
                            <UButton
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-x"
                                @click="editModalOpen = false"
                            />
                        </div>
                    </template>

                    <div class="space-y-4">
                        <UFormField label="Name" :error="formErrors.name?.[0]" required hint="Internal name for this ad placement">
                            <UInput v-model="form.name" placeholder="e.g., Homepage Header Ad" class="w-full" />
                        </UFormField>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <UFormField label="Page Type" :error="formErrors.page_type?.[0]" required>
                                <USelect
                                    v-model="form.page_type"
                                    :items="pageTypeOptions"
                                    value-key="value"
                                    label-key="label"
                                    class="w-full"
                                />
                            </UFormField>

                            <UFormField label="Slot" :error="formErrors.slot?.[0]" required>
                                <USelect
                                    v-model="form.slot"
                                    :items="slotOptions"
                                    value-key="value"
                                    label-key="label"
                                    class="w-full"
                                />
                            </UFormField>

                            <UFormField label="Category" :error="formErrors.category_id?.[0]">
                                <USelect
                                    v-model="form.category_id"
                                    :items="categoryOptions"
                                    value-key="value"
                                    label-key="label"
                                    class="w-full"
                                />
                            </UFormField>
                        </div>

                        <UFormField label="Ad Code" :error="formErrors.ad_code?.[0]" required hint="Paste your HTML/JS ad code here">
                            <UTextarea
                                v-model="form.ad_code"
                                placeholder="<script>...</script> or <div>...</div>"
                                :rows="6"
                                class="font-mono text-sm w-full"
                            />
                        </UFormField>

                        <div class="flex items-center gap-6">
                            <UFormField label="Priority" :error="formErrors.priority?.[0]">
                                <UInput v-model.number="form.priority" type="number" min="0" max="999" class="w-24" />
                            </UFormField>

                            <UCheckbox v-model="form.is_active" label="Active" class="mt-6" />
                        </div>
                        <p class="text-sm text-muted -mt-2">Higher priority ads are shown first when multiple ads match the same slot.</p>
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
                                @click="updateAdPlacement"
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete Ad Placement</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ adPlacementToDelete?.name }}</strong>?
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
                            @click="deleteAdPlacement"
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete {{ selectedCount }} Ad Placements</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ selectedCount }}</strong> selected ad placements?
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
                            Delete {{ selectedCount }} Ad Placements
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
