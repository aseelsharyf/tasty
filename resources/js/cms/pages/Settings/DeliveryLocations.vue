<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useSettingsNav } from '../../composables/useSettingsNav';
import { useCmsPath } from '../../composables/useCmsPath';
import type { Language, PaginatedResponse } from '../../types';
import type { TableColumn } from '@nuxt/ui';

const toast = useToast();
const { mainNav: settingsNav } = useSettingsNav();
const { cmsPath } = useCmsPath();

interface DeliveryLocation {
    id: number;
    uuid: string;
    name: string;
    is_active: boolean;
    order: number;
    translated_locales?: string[];
}

const props = defineProps<{
    locations: PaginatedResponse<DeliveryLocation>;
    filters: { search?: string };
    languages: Language[];
}>();

const UButton = resolveComponent('UButton');
const UBadge = resolveComponent('UBadge');
const UDropdownMenu = resolveComponent('UDropdownMenu');

const search = ref(props.filters.search || '');
const addModalOpen = ref(false);
const editModalOpen = ref(false);
const deleteModalOpen = ref(false);
const locationToEdit = ref<DeliveryLocation | null>(null);
const locationToDelete = ref<DeliveryLocation | null>(null);

function initTranslations(): Record<string, string> {
    const translations: Record<string, string> = {};
    props.languages.forEach(lang => {
        translations[lang.code] = '';
    });
    return translations;
}

const addForm = useForm({
    name: initTranslations(),
    is_active: true,
});

const editForm = useForm({
    name: {} as Record<string, string>,
    is_active: true,
});

function onSearch() {
    router.get(cmsPath('/settings/delivery-locations'), {
        search: search.value || undefined,
    }, { preserveState: true, replace: true });
}

function openAdd() {
    addForm.reset();
    addForm.name = initTranslations();
    addModalOpen.value = true;
}

function submitAdd() {
    const nameData = Object.fromEntries(
        Object.entries(addForm.name).filter(([_, v]) => v?.trim())
    );
    addForm.transform(() => ({
        name: nameData,
        is_active: addForm.is_active,
    })).post(cmsPath('/settings/delivery-locations'), {
        onSuccess: () => {
            addModalOpen.value = false;
            toast.add({ title: 'Location created', color: 'success' });
        },
    });
}

function openEdit(location: DeliveryLocation) {
    locationToEdit.value = location;
    const nameTranslations: Record<string, string> = {};
    props.languages.forEach(lang => {
        nameTranslations[lang.code] = '';
    });
    // location.name could be a translated string, we need to fetch translations
    // For now, set the default language
    if (typeof location.name === 'string') {
        nameTranslations[props.languages[0]?.code || 'en'] = location.name;
    }
    editForm.name = nameTranslations;
    editForm.is_active = location.is_active;
    editModalOpen.value = true;
}

function submitEdit() {
    if (!locationToEdit.value) return;
    const nameData = Object.fromEntries(
        Object.entries(editForm.name).filter(([_, v]) => v?.trim())
    );
    editForm.transform(() => ({
        name: nameData,
        is_active: editForm.is_active,
    })).put(cmsPath(`/settings/delivery-locations/${locationToEdit.value.uuid}`), {
        onSuccess: () => {
            editModalOpen.value = false;
            toast.add({ title: 'Location updated', color: 'success' });
        },
    });
}

function confirmDelete(location: DeliveryLocation) {
    locationToDelete.value = location;
    deleteModalOpen.value = true;
}

function deleteLocation() {
    if (!locationToDelete.value) return;
    router.delete(cmsPath(`/settings/delivery-locations/${locationToDelete.value.uuid}`), {
        onSuccess: () => {
            deleteModalOpen.value = false;
            locationToDelete.value = null;
            toast.add({ title: 'Location deleted', color: 'success' });
        },
    });
}

const columns: TableColumn<DeliveryLocation>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
        cell: ({ row }) => h('span', { class: 'font-medium text-highlighted' }, row.original.name),
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
    <Head title="Delivery Locations" />

    <DashboardLayout>
        <UDashboardPanel id="delivery-locations">
            <template #header>
                <UDashboardNavbar title="Delivery Locations">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                    <template #right>
                        <UButton icon="i-lucide-plus" @click="openAdd">
                            Add Location
                        </UButton>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex items-center gap-2 mb-4">
                    <UInput
                        v-model="search"
                        placeholder="Search locations..."
                        icon="i-lucide-search"
                        :ui="{ base: 'w-64' }"
                        @keyup.enter="onSearch"
                    />
                </div>

                <UTable
                    :data="locations.data"
                    :columns="columns"
                    :ui="{
                        base: 'table-fixed border-separate border-spacing-0',
                        thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                        tbody: '[&>tr]:last:[&>td]:border-b-0',
                        th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
                        td: 'border-b border-default',
                    }"
                />

                <div v-if="locations.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-map-pin" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No delivery locations found.</p>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Add Modal -->
        <UModal v-model:open="addModalOpen">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <h3 class="text-lg font-semibold text-highlighted mb-4">Add Delivery Location</h3>
                    <div class="space-y-4">
                        <div v-for="lang in languages" :key="lang.code">
                            <label class="text-sm font-medium mb-1 block">Name ({{ lang.native_name }})</label>
                            <UInput v-model="addForm.name[lang.code]" :placeholder="`Location name in ${lang.name}`" :dir="lang.direction" />
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
        <UModal v-model:open="editModalOpen">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <h3 class="text-lg font-semibold text-highlighted mb-4">Edit Delivery Location</h3>
                    <div class="space-y-4">
                        <div v-for="lang in languages" :key="lang.code">
                            <label class="text-sm font-medium mb-1 block">Name ({{ lang.native_name }})</label>
                            <UInput v-model="editForm.name[lang.code]" :placeholder="`Location name in ${lang.name}`" :dir="lang.direction" />
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete Location</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ locationToDelete?.name }}</strong>?
                            </p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <UButton color="neutral" variant="outline" @click="deleteModalOpen = false">Cancel</UButton>
                        <UButton color="error" @click="deleteLocation">Delete</UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
