<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, h, resolveComponent, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import { useCmsPath } from '../../composables/useCmsPath';
import type { User, PaginatedResponse, UserTypeCounts } from '../../types';
import type { TableColumn } from '@nuxt/ui';

const props = defineProps<{
    users: PaginatedResponse<User>;
    roles: string[];
    typeCounts: UserTypeCounts;
    filters: {
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
        roles?: string[];
        type?: string;
    };
}>();

const { can } = usePermission();
const { cmsPath } = useCmsPath();

const UAvatar = resolveComponent('UAvatar');
const UBadge = resolveComponent('UBadge');
const UButton = resolveComponent('UButton');
const UDropdownMenu = resolveComponent('UDropdownMenu');

const search = ref(props.filters.search || '');
const selectedRoles = ref<string[]>(props.filters.roles || []);
const deleteModalOpen = ref(false);
const userToDelete = ref<User | null>(null);

const roleFilterOptions = computed(() =>
    props.roles.map((role) => ({ label: role, value: role }))
);

function applyFilters(overrides: Record<string, any> = {}) {
    router.get(cmsPath('/users'), {
        search: search.value || undefined,
        roles: selectedRoles.value.length > 0 ? selectedRoles.value : undefined,
        type: props.filters.type || undefined,
        sort: props.filters.sort,
        direction: props.filters.direction,
        ...overrides,
    }, {
        preserveState: true,
        replace: true,
    });
}

const debouncedSearch = useDebounceFn(() => {
    applyFilters();
}, 300);

watch(search, () => {
    debouncedSearch();
});

watch(selectedRoles, () => {
    applyFilters();
}, { deep: true });

function clearRoleFilter() {
    selectedRoles.value = [];
}

function sortBy(field: string) {
    const currentSort = props.filters.sort || 'created_at';
    const currentDirection = props.filters.direction || 'desc';

    let newDirection: 'asc' | 'desc' = 'asc';
    if (currentSort === field) {
        newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
    }

    applyFilters({
        sort: field,
        direction: newDirection,
    });
}

function getSortIcon(field: string) {
    const currentSort = props.filters.sort || 'created_at';
    const currentDirection = props.filters.direction || 'desc';

    if (currentSort !== field) {
        return 'i-lucide-arrow-up-down';
    }
    return currentDirection === 'asc' ? 'i-lucide-arrow-up-narrow-wide' : 'i-lucide-arrow-down-wide-narrow';
}

function confirmDelete(user: User) {
    userToDelete.value = user;
    deleteModalOpen.value = true;
}

function deleteUser() {
    if (userToDelete.value) {
        router.delete(cmsPath(`/users/${userToDelete.value.uuid}`), {
            onSuccess: () => {
                deleteModalOpen.value = false;
                userToDelete.value = null;
            },
        });
    }
}

function getRowActions(row: User) {
    const actions: any[][] = [];

    if (can('users.edit')) {
        actions.push([
            {
                label: 'Edit',
                icon: 'i-lucide-pencil',
                to: cmsPath(`/users/${row.uuid}/edit`),
            },
        ]);
    }

    if (can('users.delete')) {
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

const columns: TableColumn<User>[] = [
    {
        accessorKey: 'name',
        header: () => {
            return h(UButton, {
                color: 'neutral',
                variant: 'ghost',
                label: 'User',
                icon: getSortIcon('name'),
                class: '-mx-2.5',
                onClick: () => sortBy('name'),
            });
        },
        cell: ({ row }) => {
            return h('div', { class: 'flex items-center gap-3' }, [
                h('div', { class: 'relative' }, [
                    h(UAvatar, {
                        src: row.original.avatar_url,
                        alt: row.original.name,
                        size: 'md',
                    }),
                    row.original.google_id ? h('div', {
                        class: 'absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-white rounded-full flex items-center justify-center shadow-sm border border-gray-200',
                        title: 'Connected via Google',
                        innerHTML: '<svg class="w-2.5 h-2.5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>',
                    }) : null,
                ]),
                h('div', undefined, [
                    h('p', { class: 'font-medium text-highlighted' }, row.original.name),
                    h('p', { class: 'text-muted text-sm' }, `@${row.original.username}`),
                ]),
            ]);
        },
    },
    {
        accessorKey: 'email',
        header: () => {
            return h(UButton, {
                color: 'neutral',
                variant: 'ghost',
                label: 'Email',
                icon: getSortIcon('email'),
                class: '-mx-2.5',
                onClick: () => sortBy('email'),
            });
        },
        cell: ({ row }) => row.original.email,
    },
    {
        accessorKey: 'roles',
        header: 'Roles',
        cell: ({ row }) => {
            const roles = row.original.roles || [];
            if (roles.length === 0) {
                return h('span', { class: 'text-muted' }, 'No roles');
            }
            return h(
                'div',
                { class: 'flex flex-wrap gap-1' },
                roles.map((role) =>
                    h(
                        UBadge,
                        {
                            color: role === 'Admin' ? 'primary' : 'neutral',
                            variant: 'subtle',
                            size: 'sm',
                        },
                        () => role
                    )
                )
            );
        },
    },
    {
        accessorKey: 'created_at',
        header: () => {
            return h(UButton, {
                color: 'neutral',
                variant: 'ghost',
                label: 'Created',
                icon: getSortIcon('created_at'),
                class: '-mx-2.5',
                onClick: () => sortBy('created_at'),
            });
        },
        cell: ({ row }) => {
            return new Date(row.original.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            });
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
    <Head title="Users" />

    <DashboardLayout>
        <UDashboardPanel id="users">
            <template #header>
                <UDashboardNavbar title="Users">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <Link v-if="can('users.create')" :href="cmsPath('/users/create')">
                            <UButton icon="i-lucide-plus">
                                Add User
                            </UButton>
                        </Link>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <!-- Type Tabs -->
                <div class="flex items-center gap-1 mb-4 border-b border-default -mx-4 px-4">
                    <Link
                        :href="cmsPath('/users')"
                        :class="[
                            'px-3 py-2 text-sm font-medium border-b-2 transition-colors',
                            !filters.type
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted hover:text-highlighted hover:border-muted',
                        ]"
                    >
                        All <span class="text-xs text-muted ml-1">({{ typeCounts.all }})</span>
                    </Link>
                    <Link
                        :href="cmsPath('/users?type=staff')"
                        :class="[
                            'px-3 py-2 text-sm font-medium border-b-2 transition-colors',
                            filters.type === 'staff'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted hover:text-highlighted hover:border-muted',
                        ]"
                    >
                        Staff <span class="text-xs text-muted ml-1">({{ typeCounts.staff }})</span>
                    </Link>
                    <Link
                        :href="cmsPath('/users?type=contributor')"
                        :class="[
                            'px-3 py-2 text-sm font-medium border-b-2 transition-colors',
                            filters.type === 'contributor'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted hover:text-highlighted hover:border-muted',
                        ]"
                    >
                        Contributors <span class="text-xs text-muted ml-1">({{ typeCounts.contributor }})</span>
                    </Link>
                    <Link
                        :href="cmsPath('/users?type=user')"
                        :class="[
                            'px-3 py-2 text-sm font-medium border-b-2 transition-colors',
                            filters.type === 'user'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted hover:text-highlighted hover:border-muted',
                        ]"
                    >
                        Users <span class="text-xs text-muted ml-1">({{ typeCounts.user }})</span>
                    </Link>
                </div>

                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <UInput
                        v-model="search"
                        class="w-full sm:max-w-xs"
                        icon="i-lucide-search"
                        placeholder="Search users..."
                    />

                    <USelectMenu
                        v-model="selectedRoles"
                        :items="roleFilterOptions"
                        value-key="value"
                        multiple
                        class="w-full sm:w-48"
                        placeholder="Filter by roles"
                        :search-input="false"
                    />

                    <UButton
                        v-if="selectedRoles.length > 0"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        icon="i-lucide-x"
                        @click="clearRoleFilter"
                    >
                        Clear
                    </UButton>

                    <div class="text-sm text-muted ml-auto">
                        {{ users.total }} user{{ users.total !== 1 ? 's' : '' }}
                    </div>
                </div>

                <UTable
                    :data="users.data"
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

                <div
                    v-if="users.last_page > 1"
                    class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-auto"
                >
                    <div class="text-sm text-muted">
                        Showing {{ users.from }} to {{ users.to }} of {{ users.total }}
                    </div>

                    <UPagination
                        :default-page="users.current_page"
                        :items-per-page="users.per_page"
                        :total="users.total"
                        @update:page="(p: number) => router.get(cmsPath('/users'), { ...filters, page: p })"
                    />
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete User</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ userToDelete?.name }}</strong>? This action cannot be undone.
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
                            @click="deleteUser"
                        >
                            Delete
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
