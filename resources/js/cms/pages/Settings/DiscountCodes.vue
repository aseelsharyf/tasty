<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { PaginatedResponse } from '../../types';
import type { TableColumn } from '@nuxt/ui';

const toast = useToast();
const { cmsPath } = useCmsPath();

interface DiscountCode {
    id: number;
    uuid: string;
    code: string;
    description: string | null;
    type: string;
    type_label: string;
    value: string;
    discount_label: string;
    min_order_amount: string | null;
    max_discount_amount: string | null;
    max_uses: number | null;
    times_used: number;
    starts_at: string | null;
    expires_at: string | null;
    is_active: boolean;
}

const props = defineProps<{
    discountCodes: PaginatedResponse<DiscountCode>;
    filters: { search?: string };
}>();

const UButton = resolveComponent('UButton');
const UBadge = resolveComponent('UBadge');
const UDropdownMenu = resolveComponent('UDropdownMenu');

const search = ref(props.filters.search || '');
const addModalOpen = ref(false);
const editModalOpen = ref(false);
const deleteModalOpen = ref(false);
const codeToEdit = ref<DiscountCode | null>(null);
const codeToDelete = ref<DiscountCode | null>(null);

const addForm = useForm({
    code: '',
    description: '',
    type: 'percentage',
    value: '',
    min_order_amount: '',
    max_discount_amount: '',
    max_uses: '',
    starts_at: '',
    expires_at: '',
    is_active: true,
});

const editForm = useForm({
    code: '',
    description: '',
    type: 'percentage',
    value: '',
    min_order_amount: '',
    max_discount_amount: '',
    max_uses: '',
    starts_at: '',
    expires_at: '',
    is_active: true,
});

function onSearch() {
    router.get(cmsPath('/settings/discount-codes'), {
        search: search.value || undefined,
    }, { preserveState: true, replace: true });
}

function openAdd() {
    addForm.reset();
    addModalOpen.value = true;
}

function submitAdd() {
    addForm.transform((data) => ({
        ...data,
        value: data.value ? parseFloat(data.value) : undefined,
        min_order_amount: data.min_order_amount ? parseFloat(data.min_order_amount) : null,
        max_discount_amount: data.max_discount_amount ? parseFloat(data.max_discount_amount) : null,
        max_uses: data.max_uses ? parseInt(data.max_uses) : null,
        starts_at: data.starts_at || null,
        expires_at: data.expires_at || null,
    })).post(cmsPath('/settings/discount-codes'), {
        onSuccess: () => {
            addModalOpen.value = false;
            toast.add({ title: 'Discount code created', color: 'success' });
        },
    });
}

function openEdit(code: DiscountCode) {
    codeToEdit.value = code;
    editForm.code = code.code;
    editForm.description = code.description || '';
    editForm.type = code.type;
    editForm.value = code.value;
    editForm.min_order_amount = code.min_order_amount || '';
    editForm.max_discount_amount = code.max_discount_amount || '';
    editForm.max_uses = code.max_uses !== null ? String(code.max_uses) : '';
    editForm.starts_at = code.starts_at || '';
    editForm.expires_at = code.expires_at || '';
    editForm.is_active = code.is_active;
    editModalOpen.value = true;
}

function submitEdit() {
    if (!codeToEdit.value) return;
    editForm.transform((data) => ({
        ...data,
        value: data.value ? parseFloat(data.value) : undefined,
        min_order_amount: data.min_order_amount ? parseFloat(data.min_order_amount) : null,
        max_discount_amount: data.max_discount_amount ? parseFloat(data.max_discount_amount) : null,
        max_uses: data.max_uses ? parseInt(data.max_uses) : null,
        starts_at: data.starts_at || null,
        expires_at: data.expires_at || null,
    })).put(cmsPath(`/settings/discount-codes/${codeToEdit.value.id}`), {
        onSuccess: () => {
            editModalOpen.value = false;
            toast.add({ title: 'Discount code updated', color: 'success' });
        },
    });
}

function confirmDelete(code: DiscountCode) {
    codeToDelete.value = code;
    deleteModalOpen.value = true;
}

function deleteCode() {
    if (!codeToDelete.value) return;
    router.delete(cmsPath(`/settings/discount-codes/${codeToDelete.value.id}`), {
        onSuccess: () => {
            deleteModalOpen.value = false;
            codeToDelete.value = null;
            toast.add({ title: 'Discount code deleted', color: 'success' });
        },
    });
}

const columns: TableColumn<DiscountCode>[] = [
    {
        accessorKey: 'code',
        header: 'Code',
        cell: ({ row }) => h('span', { class: 'font-mono font-medium text-highlighted' }, row.original.code),
    },
    {
        accessorKey: 'discount_label',
        header: 'Discount',
        cell: ({ row }) => h('span', {}, row.original.discount_label),
    },
    {
        accessorKey: 'times_used',
        header: 'Usage',
        cell: ({ row }) => h('span', { class: 'text-muted' },
            row.original.max_uses
                ? `${row.original.times_used} / ${row.original.max_uses}`
                : `${row.original.times_used} / ∞`
        ),
    },
    {
        accessorKey: 'expires_at',
        header: 'Expires',
        cell: ({ row }) => h('span', { class: 'text-muted text-sm' }, row.original.expires_at || '—'),
    },
    {
        accessorKey: 'is_active',
        header: 'Status',
        cell: ({ row }) => h(
            UBadge,
            { color: row.original.is_active ? 'success' : 'neutral', variant: 'subtle' },
            () => row.original.is_active ? 'Active' : 'Inactive'
        ),
    },
    {
        id: 'actions',
        cell: ({ row }) => h('div', { class: 'text-right' },
            h(UDropdownMenu, {
                content: { align: 'end' },
                items: [
                    [{ label: 'Edit', icon: 'i-lucide-pencil', onSelect: () => openEdit(row.original) }],
                    [{ label: 'Delete', icon: 'i-lucide-trash', color: 'error' as const, onSelect: () => confirmDelete(row.original) }],
                ],
            }, () => h(UButton, { icon: 'i-lucide-ellipsis-vertical', color: 'neutral', variant: 'ghost' }))
        ),
    },
];
</script>

<template>
    <Head title="Discount Codes" />

    <DashboardLayout>
        <UDashboardPanel id="discount-codes">
            <template #header>
                <UDashboardNavbar title="Discount Codes">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                    <template #right>
                        <UButton icon="i-lucide-plus" @click="openAdd">
                            Add Code
                        </UButton>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex items-center gap-2 mb-4">
                    <UInput
                        v-model="search"
                        placeholder="Search codes..."
                        icon="i-lucide-search"
                        :ui="{ base: 'w-64' }"
                        @keyup.enter="onSearch"
                    />
                </div>

                <UTable
                    :data="discountCodes.data"
                    :columns="columns"
                    :ui="{
                        base: 'table-fixed border-separate border-spacing-0',
                        thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                        tbody: '[&>tr]:last:[&>td]:border-b-0',
                        th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
                        td: 'border-b border-default',
                    }"
                />

                <div v-if="discountCodes.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-ticket" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No discount codes found.</p>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Add Modal -->
        <UModal v-model:open="addModalOpen" :ui="{ content: 'sm:max-w-xl' }">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <h3 class="text-lg font-semibold text-highlighted mb-5">Add Discount Code</h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <label class="text-sm font-medium mb-1 block">Code</label>
                                <UInput v-model="addForm.code" placeholder="e.g. SAVE10" class="w-full uppercase" />
                                <p v-if="addForm.errors.code" class="text-sm text-error mt-1">{{ addForm.errors.code }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Max Uses</label>
                                <UInput v-model="addForm.max_uses" type="number" placeholder="Unlimited" class="w-full" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">Description</label>
                            <UInput v-model="addForm.description" placeholder="Optional description" class="w-full" />
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="text-sm font-medium mb-1 block">Type</label>
                                <USelectMenu
                                    v-model="addForm.type"
                                    :items="[
                                        { label: 'Percentage', value: 'percentage' },
                                        { label: 'Fixed Amount', value: 'fixed' },
                                    ]"
                                    value-key="value"
                                    class="w-full"
                                />
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Value</label>
                                <UInput v-model="addForm.value" type="number" step="0.01" :placeholder="addForm.type === 'percentage' ? 'e.g. 10' : 'e.g. 50.00'" class="w-full" />
                                <p v-if="addForm.errors.value" class="text-sm text-error mt-1">{{ addForm.errors.value }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Max Discount Cap</label>
                                <UInput v-model="addForm.max_discount_amount" type="number" step="0.01" placeholder="No cap" class="w-full" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">Min Order Amount</label>
                            <UInput v-model="addForm.min_order_amount" type="number" step="0.01" placeholder="No minimum" class="w-full" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium mb-1 block">Starts At</label>
                                <UInput v-model="addForm.starts_at" type="datetime-local" class="w-full" />
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Expires At</label>
                                <UInput v-model="addForm.expires_at" type="datetime-local" class="w-full" />
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-elevated/50 border border-default">
                            <span class="text-sm font-medium">Active</span>
                            <USwitch v-model="addForm.is_active" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <UButton color="neutral" variant="outline" @click="addModalOpen = false">Cancel</UButton>
                        <UButton :loading="addForm.processing" @click="submitAdd">Create</UButton>
                    </div>
                </UCard>
            </template>
        </UModal>

        <!-- Edit Modal -->
        <UModal v-model:open="editModalOpen" :ui="{ content: 'sm:max-w-xl' }">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <h3 class="text-lg font-semibold text-highlighted mb-5">Edit Discount Code</h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <label class="text-sm font-medium mb-1 block">Code</label>
                                <UInput v-model="editForm.code" placeholder="e.g. SAVE10" class="w-full uppercase" />
                                <p v-if="editForm.errors.code" class="text-sm text-error mt-1">{{ editForm.errors.code }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Max Uses</label>
                                <UInput v-model="editForm.max_uses" type="number" placeholder="Unlimited" class="w-full" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">Description</label>
                            <UInput v-model="editForm.description" placeholder="Optional description" class="w-full" />
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="text-sm font-medium mb-1 block">Type</label>
                                <USelectMenu
                                    v-model="editForm.type"
                                    :items="[
                                        { label: 'Percentage', value: 'percentage' },
                                        { label: 'Fixed Amount', value: 'fixed' },
                                    ]"
                                    value-key="value"
                                    class="w-full"
                                />
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Value</label>
                                <UInput v-model="editForm.value" type="number" step="0.01" :placeholder="editForm.type === 'percentage' ? 'e.g. 10' : 'e.g. 50.00'" class="w-full" />
                                <p v-if="editForm.errors.value" class="text-sm text-error mt-1">{{ editForm.errors.value }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Max Discount Cap</label>
                                <UInput v-model="editForm.max_discount_amount" type="number" step="0.01" placeholder="No cap" class="w-full" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">Min Order Amount</label>
                            <UInput v-model="editForm.min_order_amount" type="number" step="0.01" placeholder="No minimum" class="w-full" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium mb-1 block">Starts At</label>
                                <UInput v-model="editForm.starts_at" type="datetime-local" class="w-full" />
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Expires At</label>
                                <UInput v-model="editForm.expires_at" type="datetime-local" class="w-full" />
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-elevated/50 border border-default">
                            <span class="text-sm font-medium">Active</span>
                            <USwitch v-model="editForm.is_active" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <UButton color="neutral" variant="outline" @click="editModalOpen = false">Cancel</UButton>
                        <UButton :loading="editForm.processing" @click="submitEdit">Update</UButton>
                    </div>
                </UCard>
            </template>
        </UModal>

        <!-- Delete Modal -->
        <UModal v-model:open="deleteModalOpen">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center size-12 rounded-full bg-error/10 shrink-0">
                            <UIcon name="i-lucide-alert-triangle" class="size-6 text-error" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-highlighted">Delete Discount Code</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted font-mono">{{ codeToDelete?.code }}</strong>?
                            </p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <UButton color="neutral" variant="outline" @click="deleteModalOpen = false">Cancel</UButton>
                        <UButton color="error" @click="deleteCode">Delete</UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
