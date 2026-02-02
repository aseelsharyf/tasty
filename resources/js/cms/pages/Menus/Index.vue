<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import MenuCreateSlideover from '../../components/MenuCreateSlideover.vue';
import { usePermission } from '../../composables/usePermission';
import { useCmsPath } from '../../composables/useCmsPath';
import type { Menu, PaginatedResponse, Language } from '../../types';
import type { TableColumn } from '@nuxt/ui';

const props = defineProps<{
    menus: PaginatedResponse<Menu>;
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
const UIcon = resolveComponent('UIcon');

const search = ref(props.filters.search || '');
const deleteModalOpen = ref(false);
const menuToDelete = ref<Menu | null>(null);
const createModalOpen = ref(false);

const isSearching = computed(() => search.value.length > 0);

function onSearch() {
    router.get(cmsPath('/menus'), {
        search: search.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearSearch() {
    search.value = '';
    onSearch();
}

function sortBy(field: string) {
    const newDirection = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(cmsPath('/menus'), {
        search: search.value || undefined,
        sort: field,
        direction: newDirection,
    }, {
        preserveState: true,
        replace: true,
    });
}

function confirmDelete(menu: Menu) {
    menuToDelete.value = menu;
    deleteModalOpen.value = true;
}

function deleteMenu() {
    if (menuToDelete.value) {
        router.delete(cmsPath(`/menus/${menuToDelete.value.uuid}`), {
            onSuccess: () => {
                deleteModalOpen.value = false;
                menuToDelete.value = null;
            },
        });
    }
}

function editMenu(menu: Menu) {
    router.visit(cmsPath(`/menus/${menu.uuid}/edit`));
}

function getRowActions(row: Menu) {
    const actions: any[][] = [];

    if (can('menus.edit')) {
        actions.push([
            {
                label: 'Edit',
                icon: 'i-lucide-pencil',
                onSelect: () => editMenu(row),
            },
        ]);
    }

    if (can('menus.delete')) {
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

function getSortIcon(field: string) {
    if (props.filters.sort !== field) return null;
    return props.filters.direction === 'asc' ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down';
}

const columns: TableColumn<Menu>[] = [
    {
        accessorKey: 'name',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('name'),
        }, [
            'Name',
            getSortIcon('name') && h('span', { class: getSortIcon('name') }),
        ]),
        cell: ({ row }) => {
            return h('div', {}, [
                h('div', { class: 'font-medium text-highlighted' }, row.original.name),
                row.original.description && h('div', { class: 'text-xs text-muted truncate max-w-xs' }, row.original.description),
            ]);
        },
    },
    {
        accessorKey: 'location',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('location'),
        }, [
            'Location',
            getSortIcon('location') && h('span', { class: getSortIcon('location') }),
        ]),
        cell: ({ row }) => {
            return h('span', { class: 'text-muted font-mono text-sm' }, row.original.location);
        },
    },
    {
        id: 'items',
        header: 'Items',
        cell: ({ row }) => {
            return h(
                UBadge,
                { color: 'neutral', variant: 'subtle' },
                () => `${row.original.all_items_count ?? 0}`
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
                },
                () => row.original.is_active ? 'Active' : 'Inactive'
            );
        },
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
    <Head title="Menus" />

    <DashboardLayout>
        <UDashboardPanel id="menus">
            <template #header>
                <UDashboardNavbar title="Menus">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <MenuCreateSlideover
                            v-if="can('menus.create')"
                            v-model:open="createModalOpen"
                            :languages="languages"
                        >
                            <UButton icon="i-lucide-plus">
                                Add Menu
                            </UButton>
                        </MenuCreateSlideover>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <UInput
                            v-model="search"
                            placeholder="Search menus..."
                            icon="i-lucide-search"
                            :ui="{ base: 'w-64' }"
                            @keyup.enter="onSearch"
                        />
                        <UButton
                            v-if="isSearching"
                            color="neutral"
                            variant="ghost"
                            icon="i-lucide-x"
                            @click="clearSearch"
                        />
                    </div>

                    <span class="text-sm text-muted">
                        {{ menus.total }} menus
                    </span>
                </div>

                <UTable
                    :data="menus.data"
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
                <div v-if="menus.last_page > 1" class="flex items-center justify-end gap-3 border-t border-default pt-4 mt-4">
                    <UPagination
                        :page="menus.current_page"
                        :total="menus.total"
                        :items-per-page="menus.per_page"
                        @update:page="(page) => router.get(cmsPath('/menus'), { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="menus.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-menu" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No menus found.</p>
                    <UButton
                        v-if="can('menus.create')"
                        class="mt-4"
                        variant="outline"
                        @click="createModalOpen = true"
                    >
                        Create your first menu
                    </UButton>
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete Menu</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ menuToDelete?.name }}</strong>?
                                This will also delete all menu items.
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
                            @click="deleteMenu"
                        >
                            Delete
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
