<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import { useCmsPath } from '../../composables/useCmsPath';
import type { PaginatedResponse } from '../../types';
import type { TableColumn } from '@nuxt/ui';

interface Submission {
    id: number;
    uuid: string;
    submission_type: 'single' | 'composite';
    recipe_name: string;
    slug: string;
    submitter_name: string;
    submitter_email: string;
    is_chef: boolean;
    chef_name?: string;
    status: 'pending' | 'approved' | 'rejected' | 'converted';
    image_url?: string | null;
    child_count: number;
    reviewer?: { id: number; name: string } | null;
    reviewed_at?: string;
    converted_post?: { id: number; uuid: string; title: string } | null;
    created_at: string;
}

interface Props {
    submissions: PaginatedResponse<Submission>;
    filters: {
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
        status?: string;
        type?: string;
    };
    counts: {
        all: number;
        pending: number;
        approved: number;
        rejected: number;
        converted: number;
    };
}

const props = defineProps<Props>();
const { can } = usePermission();
const { cmsPath } = useCmsPath();

const UButton = resolveComponent('UButton');
const UBadge = resolveComponent('UBadge');
const UDropdownMenu = resolveComponent('UDropdownMenu');
const UCheckbox = resolveComponent('UCheckbox');
const UAvatar = resolveComponent('UAvatar');

const search = ref(props.filters.search || '');
const currentStatus = ref(props.filters.status || 'pending');
const deleteModalOpen = ref(false);
const submissionToDelete = ref<Submission | null>(null);
const rowSelection = ref<Record<string, boolean>>({});
const bulkActionModalOpen = ref(false);
const bulkActionType = ref<'approve' | 'reject' | 'delete'>('approve');
const bulkActionNotes = ref('');
const processing = ref(false);

const selectedCount = computed(() => {
    return Object.values(rowSelection.value).filter(Boolean).length;
});

const selectedIds = computed(() => {
    return Object.entries(rowSelection.value)
        .filter(([_, selected]) => selected)
        .map(([index]) => props.submissions.data[parseInt(index)]?.id)
        .filter(Boolean);
});

function clearSelection() {
    rowSelection.value = {};
}

function onSearch() {
    router.get(cmsPath('/recipe-submissions'), {
        search: search.value || undefined,
        status: currentStatus.value,
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

function filterByStatus(status: string) {
    currentStatus.value = status;
    router.get(cmsPath('/recipe-submissions'), {
        search: search.value || undefined,
        status,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
}

function sortBy(field: string) {
    const newDirection = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(cmsPath('/recipe-submissions'), {
        search: search.value || undefined,
        status: currentStatus.value,
        sort: field,
        direction: newDirection,
    }, {
        preserveState: true,
        replace: true,
    });
}

function viewSubmission(submission: Submission) {
    router.get(cmsPath(`/recipe-submissions/${submission.uuid}`));
}

function approveSubmission(submission: Submission) {
    if (confirm('Approve this submission?')) {
        router.post(cmsPath(`/recipe-submissions/${submission.uuid}/approve`));
    }
}

function rejectSubmission(submission: Submission) {
    const notes = prompt('Enter rejection notes (optional):');
    router.post(cmsPath(`/recipe-submissions/${submission.uuid}/reject`), { notes });
}

function confirmDelete(submission: Submission) {
    submissionToDelete.value = submission;
    deleteModalOpen.value = true;
}

function deleteSubmission() {
    if (submissionToDelete.value) {
        router.delete(cmsPath(`/recipe-submissions/${submissionToDelete.value.uuid}`), {
            onSuccess: () => {
                deleteModalOpen.value = false;
                submissionToDelete.value = null;
            },
        });
    }
}

function openBulkActionModal(action: 'approve' | 'reject' | 'delete') {
    bulkActionType.value = action;
    bulkActionNotes.value = '';
    bulkActionModalOpen.value = true;
}

function executeBulkAction() {
    processing.value = true;
    router.post(cmsPath('/recipe-submissions/bulk'), {
        ids: selectedIds.value,
        action: bulkActionType.value,
        notes: bulkActionNotes.value,
    }, {
        onSuccess: () => {
            bulkActionModalOpen.value = false;
            rowSelection.value = {};
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}

function getStatusColor(status: string): 'warning' | 'success' | 'error' | 'info' | 'neutral' {
    switch (status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'error';
        case 'converted': return 'info';
        default: return 'neutral';
    }
}

function getRowActions(row: Submission) {
    const actions: any[][] = [];

    // View action
    actions.push([{
        label: 'View Details',
        icon: 'i-lucide-eye',
        onSelect: () => viewSubmission(row),
    }]);

    // Status actions for pending submissions
    if (row.status === 'pending' && can('posts.edit')) {
        actions.push([
            {
                label: 'Approve',
                icon: 'i-lucide-check',
                onSelect: () => approveSubmission(row),
            },
            {
                label: 'Reject',
                icon: 'i-lucide-x',
                onSelect: () => rejectSubmission(row),
            },
        ]);
    }

    // Convert for approved submissions
    if (row.status === 'approved' && !row.converted_post && can('posts.create')) {
        actions.push([{
            label: 'Convert to Post',
            icon: 'i-lucide-file-plus',
            onSelect: () => viewSubmission(row),
        }]);
    }

    // Delete action
    if (can('posts.delete')) {
        actions.push([{
            label: 'Delete',
            icon: 'i-lucide-trash',
            color: 'error' as const,
            onSelect: () => confirmDelete(row),
        }]);
    }

    return actions;
}

const columns: TableColumn<Submission>[] = [
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
        accessorKey: 'recipe_name',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('recipe_name'),
        }, [
            'Recipe',
            props.filters.sort === 'recipe_name' && h('span', {
                class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down'),
            }),
        ]),
        cell: ({ row }) => h('div', { class: 'flex items-center gap-3' }, [
            h(UAvatar, {
                src: row.original.image_url || undefined,
                alt: row.original.recipe_name,
                size: 'sm',
                icon: 'i-lucide-chef-hat',
                class: 'bg-elevated',
            }),
            h('div', {}, [
                h('div', { class: 'font-medium text-highlighted' }, row.original.recipe_name),
                row.original.submission_type === 'composite' && h(UBadge, {
                    color: 'info',
                    variant: 'subtle',
                    size: 'xs',
                }, () => `Composite (${row.original.child_count} recipes)`),
            ]),
        ]),
    },
    {
        accessorKey: 'submitter_name',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('submitter_name'),
        }, [
            'Submitter',
            props.filters.sort === 'submitter_name' && h('span', {
                class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down'),
            }),
        ]),
        cell: ({ row }) => h('div', {}, [
            h('div', { class: 'font-medium' }, row.original.submitter_name),
            h('div', { class: 'text-xs text-muted' }, row.original.submitter_email),
            !row.original.is_chef && row.original.chef_name && h('div', { class: 'text-xs text-warning' }, `Chef: ${row.original.chef_name}`),
        ]),
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => h(UBadge, {
            color: getStatusColor(row.original.status),
            variant: 'subtle',
        }, () => row.original.status.charAt(0).toUpperCase() + row.original.status.slice(1)),
    },
    {
        accessorKey: 'created_at',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('created_at'),
        }, [
            'Submitted',
            props.filters.sort === 'created_at' && h('span', {
                class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down'),
            }),
        ]),
        cell: ({ row }) => h('span', { class: 'text-muted text-sm' }, row.original.created_at),
    },
    {
        id: 'reviewer',
        header: 'Reviewed By',
        cell: ({ row }) => {
            if (!row.original.reviewer) {
                return h('span', { class: 'text-muted text-sm' }, '—');
            }
            return h('div', {}, [
                h('div', { class: 'text-sm' }, row.original.reviewer.name),
                h('div', { class: 'text-xs text-muted' }, row.original.reviewed_at),
            ]);
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const actions = getRowActions(row.original);
            if (actions.length === 0) return null;

            return h('div', { class: 'text-right' },
                h(UDropdownMenu, {
                    content: { align: 'end' },
                    items: actions,
                }, () => h(UButton, {
                    icon: 'i-lucide-ellipsis-vertical',
                    color: 'neutral',
                    variant: 'ghost',
                    class: 'ml-auto',
                }))
            );
        },
    },
];

const statusTabs = [
    { key: 'pending', label: 'Pending', count: props.counts.pending },
    { key: 'approved', label: 'Approved', count: props.counts.approved },
    { key: 'converted', label: 'Converted', count: props.counts.converted },
    { key: 'rejected', label: 'Rejected', count: props.counts.rejected },
    { key: 'all', label: 'All', count: props.counts.all },
];
</script>

<template>
    <Head title="Recipe Submissions" />

    <DashboardLayout>
        <UDashboardPanel id="recipe-submissions">
            <template #header>
                <UDashboardNavbar title="Recipe Submissions">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <!-- Status Tabs -->
                <div class="flex items-center gap-2 mb-4 border-b border-default">
                    <button
                        v-for="tab in statusTabs"
                        :key="tab.key"
                        @click="filterByStatus(tab.key)"
                        :class="[
                            'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
                            currentStatus === tab.key
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted hover:text-highlighted'
                        ]"
                    >
                        {{ tab.label }}
                        <UBadge
                            :color="currentStatus === tab.key ? 'primary' : 'neutral'"
                            variant="subtle"
                            size="xs"
                            class="ml-1"
                        >
                            {{ tab.count }}
                        </UBadge>
                    </button>
                </div>

                <!-- Search and Filters -->
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <UInput
                            v-model="search"
                            placeholder="Search recipes or submitters..."
                            icon="i-lucide-search"
                            :ui="{ base: 'w-72' }"
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
                        {{ submissions.total }} submission{{ submissions.total !== 1 ? 's' : '' }}
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
                            {{ selectedCount }} {{ selectedCount === 1 ? 'submission' : 'submissions' }} selected
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
                            v-if="can('posts.edit')"
                            color="success"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-check"
                            @click="openBulkActionModal('approve')"
                        >
                            Approve
                        </UButton>
                        <UButton
                            v-if="can('posts.edit')"
                            color="warning"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-x"
                            @click="openBulkActionModal('reject')"
                        >
                            Reject
                        </UButton>
                        <UButton
                            v-if="can('posts.delete')"
                            color="error"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-trash"
                            @click="openBulkActionModal('delete')"
                        >
                            Delete
                        </UButton>
                    </div>
                </div>

                <!-- Table -->
                <UTable
                    v-model:row-selection="rowSelection"
                    :data="submissions.data"
                    :columns="columns"
                    :ui="{
                        base: 'table-fixed border-separate border-spacing-0',
                        thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                        tbody: '[&>tr]:last:[&>td]:border-b-0 [&>tr]:cursor-pointer [&>tr]:hover:bg-elevated/30',
                        th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
                        td: 'border-b border-default',
                        separator: 'h-0',
                    }"
                    @select="(e, row) => viewSubmission(row.original)"
                />

                <!-- Pagination -->
                <div v-if="submissions.last_page > 1" class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-4">
                    <div class="text-sm text-muted">
                        Showing {{ submissions.from }} to {{ submissions.to }} of {{ submissions.total }}
                    </div>
                    <UPagination
                        :page="submissions.current_page"
                        :total="submissions.total"
                        :items-per-page="submissions.per_page"
                        @update:page="(page) => router.get(cmsPath('/recipe-submissions'), { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="submissions.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-inbox" class="size-12 mx-auto mb-4 opacity-50" />
                    <p>No submissions found.</p>
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
                            <h3 class="text-lg font-semibold text-highlighted">Delete Submission</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ submissionToDelete?.recipe_name }}</strong>?
                                This action cannot be undone.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <UButton color="neutral" variant="outline" @click="deleteModalOpen = false">
                            Cancel
                        </UButton>
                        <UButton color="error" @click="deleteSubmission">
                            Delete
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>

        <!-- Bulk Action Modal -->
        <UModal v-model:open="bulkActionModalOpen">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <div class="flex items-start gap-4">
                        <div
                            :class="[
                                'flex items-center justify-center size-12 rounded-full shrink-0',
                                bulkActionType === 'approve' ? 'bg-success/10' :
                                bulkActionType === 'reject' ? 'bg-warning/10' : 'bg-error/10'
                            ]"
                        >
                            <UIcon
                                :name="bulkActionType === 'approve' ? 'i-lucide-check' :
                                       bulkActionType === 'reject' ? 'i-lucide-x' : 'i-lucide-trash'"
                                :class="[
                                    'size-6',
                                    bulkActionType === 'approve' ? 'text-success' :
                                    bulkActionType === 'reject' ? 'text-warning' : 'text-error'
                                ]"
                            />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-highlighted">
                                {{ bulkActionType === 'approve' ? 'Approve' :
                                   bulkActionType === 'reject' ? 'Reject' : 'Delete' }}
                                {{ selectedCount }} Submissions
                            </h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to {{ bulkActionType }}
                                <strong class="text-highlighted">{{ selectedCount }}</strong> selected submissions?
                            </p>

                            <div v-if="bulkActionType !== 'delete'" class="mt-4">
                                <label class="block text-sm font-medium mb-2">Notes (optional)</label>
                                <UTextarea
                                    v-model="bulkActionNotes"
                                    placeholder="Add notes for this action..."
                                    :rows="3"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <UButton
                            color="neutral"
                            variant="outline"
                            :disabled="processing"
                            @click="bulkActionModalOpen = false"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            :color="bulkActionType === 'approve' ? 'success' :
                                    bulkActionType === 'reject' ? 'warning' : 'error'"
                            :loading="processing"
                            @click="executeBulkAction"
                        >
                            {{ bulkActionType === 'approve' ? 'Approve' :
                               bulkActionType === 'reject' ? 'Reject' : 'Delete' }}
                            {{ selectedCount }} Submissions
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
