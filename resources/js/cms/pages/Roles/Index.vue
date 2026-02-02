<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import { useCmsPath } from '../../composables/useCmsPath';
import type { Role, GroupedPermissions } from '../../types';
import type { TableColumn } from '@nuxt/ui';

const props = defineProps<{
    roles: Role[];
    permissions: GroupedPermissions;
}>();

const { can } = usePermission();
const { cmsPath } = useCmsPath();

const UButton = resolveComponent('UButton');
const UBadge = resolveComponent('UBadge');
const UDropdownMenu = resolveComponent('UDropdownMenu');

const deleteModalOpen = ref(false);
const roleToDelete = ref<Role | null>(null);

function confirmDelete(role: Role) {
    roleToDelete.value = role;
    deleteModalOpen.value = true;
}

function deleteRole() {
    if (roleToDelete.value) {
        router.delete(cmsPath(`/roles/${roleToDelete.value.id}`), {
            onSuccess: () => {
                deleteModalOpen.value = false;
                roleToDelete.value = null;
            },
        });
    }
}

function getRowActions(row: Role) {
    const actions: any[][] = [];

    if (can('roles.edit')) {
        actions.push([
            {
                label: 'Edit',
                icon: 'i-lucide-pencil',
                to: cmsPath(`/roles/${row.id}/edit`),
            },
        ]);
    }

    if (can('roles.delete') && row.name !== 'Admin') {
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

const columns: TableColumn<Role>[] = [
    {
        accessorKey: 'name',
        header: 'Role',
        cell: ({ row }) => {
            return h('div', { class: 'font-medium text-highlighted' }, row.original.name);
        },
    },
    {
        accessorKey: 'permissions',
        header: 'Permissions',
        cell: ({ row }) => {
            const permCount = row.original.permissions.length;
            return h('div', { class: 'flex items-center gap-2' }, [
                h(
                    UBadge,
                    { color: 'neutral', variant: 'subtle' },
                    () => `${permCount} permission${permCount !== 1 ? 's' : ''}`
                ),
            ]);
        },
    },
    {
        accessorKey: 'users_count',
        header: 'Users',
        cell: ({ row }) => {
            return h('span', { class: 'text-muted' }, row.original.users_count ?? 0);
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
    <Head title="Roles & Permissions" />

    <DashboardLayout>
        <UDashboardPanel id="roles">
            <template #header>
                <UDashboardNavbar title="Roles & Permissions">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <Link v-if="can('roles.create')" :href="cmsPath('/roles/create')">
                            <UButton icon="i-lucide-plus">
                                Add Role
                            </UButton>
                        </Link>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex flex-wrap items-center justify-between gap-1.5 mb-4">
                    <div class="text-sm text-muted">
                        {{ roles.length }} role{{ roles.length !== 1 ? 's' : '' }}
                    </div>
                </div>

                <UTable
                    :data="roles"
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete Role</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ roleToDelete?.name }}</strong>? Users with this role will lose their permissions.
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
                            @click="deleteRole"
                        >
                            Delete
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
