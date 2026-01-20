<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import { useSettingsNav } from '../../composables/useSettingsNav';
import type { PaginatedResponse } from '../../types';
import type { TableColumn } from '@nuxt/ui';

interface Ingredient {
    id: number;
    name: string;
    name_dv: string | null;
    slug: string;
    is_active: boolean;
    created_at: string;
}

const props = defineProps<{
    ingredients: PaginatedResponse<Ingredient>;
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
const ingredientToDelete = ref<Ingredient | null>(null);
const createModalOpen = ref(false);
const editModalOpen = ref(false);
const ingredientToEdit = ref<Ingredient | null>(null);
const rowSelection = ref<Record<string, boolean>>({});
const bulkDeleteModalOpen = ref(false);
const bulkDeleting = ref(false);

// Form state
const form = ref({
    name: '',
    name_dv: '',
    slug: '',
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
        .map(([index]) => props.ingredients.data[parseInt(index)]?.id)
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
    router.delete('/cms/ingredients/bulk', {
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
    router.get('/cms/ingredients', {
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

function confirmDelete(ingredient: Ingredient) {
    ingredientToDelete.value = ingredient;
    deleteModalOpen.value = true;
}

function deleteIngredient() {
    if (ingredientToDelete.value) {
        router.delete(`/cms/ingredients/${ingredientToDelete.value.id}`, {
            onSuccess: () => {
                deleteModalOpen.value = false;
                ingredientToDelete.value = null;
            },
        });
    }
}

function openCreateModal() {
    form.value = {
        name: '',
        name_dv: '',
        slug: '',
        is_active: true,
    };
    formErrors.value = {};
    createModalOpen.value = true;
}

async function openEditModal(ingredient: Ingredient) {
    try {
        const response = await fetch(`/cms/ingredients/${ingredient.id}/edit`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) throw new Error('Failed to fetch ingredient');

        const data = await response.json();
        ingredientToEdit.value = data.props.ingredient;
        form.value = {
            name: data.props.ingredient.name || '',
            name_dv: data.props.ingredient.name_dv || '',
            slug: data.props.ingredient.slug || '',
            is_active: data.props.ingredient.is_active,
        };
        formErrors.value = {};
        editModalOpen.value = true;
    } catch (error) {
        console.error('Failed to load ingredient for editing:', error);
    }
}

function saveIngredient() {
    saving.value = true;
    formErrors.value = {};

    router.post('/cms/ingredients', form.value, {
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

function updateIngredient() {
    if (!ingredientToEdit.value) return;

    saving.value = true;
    formErrors.value = {};

    router.put(`/cms/ingredients/${ingredientToEdit.value.id}`, form.value, {
        onSuccess: () => {
            editModalOpen.value = false;
            ingredientToEdit.value = null;
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
    router.get('/cms/ingredients', {
        search: search.value || undefined,
        sort: field,
        direction: newDirection,
    }, {
        preserveState: true,
        replace: true,
    });
}

function getRowActions(row: Ingredient) {
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

const columns: TableColumn<Ingredient>[] = [
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
        accessorKey: 'slug',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('slug'),
        }, [
            'Slug',
            props.filters.sort === 'slug' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h('span', { class: 'font-mono text-sm text-muted' }, row.original.slug);
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
    <Head title="Ingredients" />

    <DashboardLayout>
        <UDashboardPanel id="ingredients">
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
                            Add Ingredient
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
                            placeholder="Search ingredients..."
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
                        {{ ingredients.total }} ingredient{{ ingredients.total !== 1 ? 's' : '' }}
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
                            {{ selectedCount }} {{ selectedCount === 1 ? 'ingredient' : 'ingredients' }} selected
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
                    :data="ingredients.data"
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
                <div v-if="ingredients.last_page > 1" class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-4">
                    <div class="text-sm text-muted">
                        Showing {{ ingredients.from }} to {{ ingredients.to }} of {{ ingredients.total }}
                    </div>
                    <UPagination
                        :page="ingredients.current_page"
                        :total="ingredients.total"
                        :items-per-page="ingredients.per_page"
                        @update:page="(page) => router.get('/cms/ingredients', { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="ingredients.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-carrot" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No ingredients found.</p>
                    <UButton
                        v-if="can('settings.edit')"
                        class="mt-4"
                        variant="outline"
                        @click="openCreateModal"
                    >
                        Create your first ingredient
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
                            <h3 class="text-lg font-semibold">Add Ingredient</h3>
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
                            <UInput v-model="form.name" placeholder="e.g., Tuna" />
                        </UFormField>

                        <UFormField label="Name (Dhivehi)" :error="formErrors.name_dv?.[0]">
                            <UInput v-model="form.name_dv" placeholder="ދިވެހި ނަން" dir="rtl" />
                        </UFormField>

                        <UFormField label="Slug" :error="formErrors.slug?.[0]" hint="Leave empty to auto-generate">
                            <UInput v-model="form.slug" placeholder="tuna" />
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
                                @click="saveIngredient"
                            >
                                Create Ingredient
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
                            <h3 class="text-lg font-semibold">Edit Ingredient</h3>
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
                            <UInput v-model="form.name" placeholder="e.g., Tuna" />
                        </UFormField>

                        <UFormField label="Name (Dhivehi)" :error="formErrors.name_dv?.[0]">
                            <UInput v-model="form.name_dv" placeholder="ދިވެހި ނަން" dir="rtl" />
                        </UFormField>

                        <UFormField label="Slug" :error="formErrors.slug?.[0]">
                            <UInput v-model="form.slug" placeholder="tuna" />
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
                                @click="updateIngredient"
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete Ingredient</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ ingredientToDelete?.name }}</strong>?
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
                            @click="deleteIngredient"
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete {{ selectedCount }} Ingredients</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ selectedCount }}</strong> selected ingredients?
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
                            Delete {{ selectedCount }} Ingredients
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
