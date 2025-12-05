<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import CommentSlideover from '../../components/CommentSlideover.vue';
import { usePermission } from '../../composables/usePermission';
import type { TableColumn } from '@nuxt/ui';
import { formatDistanceToNow } from 'date-fns';

interface Comment {
    id: number;
    uuid: string;
    content: string;
    status?: string;
    author_display_name: string;
    author_display_email: string;
    author_ip: string | null;
    gravatar_url: string;
    is_registered_user: boolean;
    is_edited?: boolean;
    post: {
        id: number;
        title: string;
        slug: string;
    } | null;
    created_at: string;
    edited_at?: string | null;
}

interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    comments: PaginatedResponse<Comment>;
    pendingCount: number;
    filters: {
        search?: string;
    };
}>();

const { can } = usePermission();

const UButton = resolveComponent('UButton');
const UAvatar = resolveComponent('UAvatar');
const UCheckbox = resolveComponent('UCheckbox');

const search = ref(props.filters.search || '');
const rowSelection = ref<Record<string, boolean>>({});
const slideoverOpen = ref(false);
const selectedComment = ref<Comment | null>(null);

const selectedCount = computed(() => {
    return Object.values(rowSelection.value).filter(Boolean).length;
});

const selectedIds = computed(() => {
    return Object.entries(rowSelection.value)
        .filter(([_, selected]) => selected)
        .map(([index]) => props.comments.data[parseInt(index)]?.uuid)
        .filter(Boolean);
});

function clearSelection() {
    rowSelection.value = {};
}

function applyFilters() {
    router.get('/cms/comments/queue', {
        search: search.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearSearch() {
    search.value = '';
    applyFilters();
}

function openComment(comment: Comment) {
    selectedComment.value = comment as any;
    slideoverOpen.value = true;
}

function approveComment(comment: Comment) {
    router.post(`/cms/comments/${comment.uuid}/approve`, {}, {
        preserveScroll: true,
    });
}

function spamComment(comment: Comment) {
    router.post(`/cms/comments/${comment.uuid}/spam`, {}, {
        preserveScroll: true,
    });
}

function trashComment(comment: Comment) {
    router.post(`/cms/comments/${comment.uuid}/trash`, {}, {
        preserveScroll: true,
    });
}

function bulkAction(action: 'approve' | 'spam' | 'trash') {
    if (selectedIds.value.length === 0) return;

    router.post('/cms/comments/bulk', {
        action,
        ids: selectedIds.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            rowSelection.value = {};
        },
    });
}

const columns: TableColumn<Comment>[] = [
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
        id: 'comment',
        header: 'Comment',
        cell: ({ row }) => {
            const comment = row.original;
            return h('div', { class: 'flex items-start gap-3' }, [
                h(UAvatar, {
                    src: comment.gravatar_url,
                    alt: comment.author_display_name,
                    size: 'sm',
                    class: 'mt-0.5',
                }),
                h('div', { class: 'flex-1 min-w-0' }, [
                    h('div', { class: 'flex items-center gap-1.5 mb-1' }, [
                        h('span', { class: 'font-medium text-highlighted' }, comment.author_display_name),
                        comment.is_registered_user && h('span', {
                            class: 'i-lucide-badge-check size-4 text-primary',
                            title: 'Registered user'
                        }),
                        h('span', { class: 'text-muted mx-1' }, 'on'),
                        comment.post && h('a', {
                            href: `/cms/posts/${comment.post.slug}`,
                            class: 'text-primary hover:underline truncate max-w-xs',
                        }, comment.post.title),
                    ]),
                    h('p', {
                        class: 'text-sm text-default line-clamp-3 cursor-pointer hover:text-highlighted',
                        onClick: () => openComment(comment),
                    }, comment.content),
                    h('span', { class: 'text-xs text-muted mt-1 block' },
                        formatDistanceToNow(new Date(comment.created_at), { addSuffix: true })
                    ),
                ]),
            ]);
        },
    },
    {
        id: 'actions',
        header: '',
        cell: ({ row }) => {
            if (!can('comments.moderate')) return null;

            return h('div', { class: 'flex items-center gap-1' }, [
                h(UButton, {
                    icon: 'i-lucide-check',
                    color: 'success',
                    variant: 'soft',
                    size: 'xs',
                    title: 'Approve',
                    onClick: () => approveComment(row.original),
                }),
                h(UButton, {
                    icon: 'i-lucide-shield-alert',
                    color: 'warning',
                    variant: 'soft',
                    size: 'xs',
                    title: 'Mark as Spam',
                    onClick: () => spamComment(row.original),
                }),
                h(UButton, {
                    icon: 'i-lucide-trash',
                    color: 'neutral',
                    variant: 'soft',
                    size: 'xs',
                    title: 'Trash',
                    onClick: () => trashComment(row.original),
                }),
            ]);
        },
    },
];
</script>

<template>
    <Head title="Moderation Queue" />

    <DashboardLayout>
        <UDashboardPanel id="comments-queue">
            <template #header>
                <UDashboardNavbar title="Moderation Queue">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <UButton
                            color="neutral"
                            variant="ghost"
                            icon="i-lucide-arrow-left"
                            to="/cms/comments"
                        >
                            All Comments
                        </UButton>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <!-- Header with count -->
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <UBadge color="warning" variant="subtle" size="lg">
                            {{ pendingCount }} pending
                        </UBadge>
                        <p class="text-sm text-muted">
                            Comments awaiting moderation
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <UInput
                            v-model="search"
                            placeholder="Search comments..."
                            icon="i-lucide-search"
                            :ui="{ base: 'w-64' }"
                            @keyup.enter="applyFilters"
                        />
                        <UButton
                            v-if="search"
                            color="neutral"
                            variant="ghost"
                            icon="i-lucide-x"
                            size="sm"
                            @click="clearSearch"
                        />
                    </div>
                </div>

                <!-- Bulk Actions Bar -->
                <div
                    v-if="selectedCount > 0"
                    class="flex items-center justify-between gap-3 p-3 mb-4 rounded-lg bg-elevated border border-default"
                >
                    <div class="flex items-center gap-2">
                        <UIcon name="i-lucide-check-square" class="size-5 text-primary" />
                        <span class="text-sm font-medium">
                            {{ selectedCount }} {{ selectedCount === 1 ? 'comment' : 'comments' }} selected
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
                            color="success"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-check"
                            @click="bulkAction('approve')"
                        >
                            Approve All
                        </UButton>
                        <UButton
                            color="warning"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-shield-alert"
                            @click="bulkAction('spam')"
                        >
                            Spam All
                        </UButton>
                        <UButton
                            color="neutral"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-trash"
                            @click="bulkAction('trash')"
                        >
                            Trash All
                        </UButton>
                    </div>
                </div>

                <UTable
                    v-model:row-selection="rowSelection"
                    :data="comments.data"
                    :columns="columns"
                    :ui="{
                        base: 'table-fixed border-separate border-spacing-0',
                        thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                        tbody: '[&>tr]:last:[&>td]:border-b-0',
                        th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
                        td: 'border-b border-default py-4',
                        separator: 'h-0',
                    }"
                />

                <!-- Pagination -->
                <div v-if="comments.last_page > 1" class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-4">
                    <div class="text-sm text-muted">
                        Showing {{ (comments.current_page - 1) * comments.per_page + 1 }} to
                        {{ Math.min(comments.current_page * comments.per_page, comments.total) }} of
                        {{ comments.total }}
                    </div>
                    <UPagination
                        :page="comments.current_page"
                        :total="comments.total"
                        :items-per-page="comments.per_page"
                        @update:page="(page) => router.get('/cms/comments/queue', { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="comments.data.length === 0" class="text-center py-12 text-muted">
                    <div class="size-16 rounded-full bg-success/10 flex items-center justify-center mx-auto mb-4">
                        <UIcon name="i-lucide-check-circle" class="size-8 text-success" />
                    </div>
                    <h3 class="text-lg font-medium text-highlighted mb-2">All caught up!</h3>
                    <p>No pending comments to moderate.</p>
                    <UButton
                        color="neutral"
                        variant="soft"
                        class="mt-4"
                        to="/cms/comments"
                    >
                        View All Comments
                    </UButton>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Comment Slideover -->
        <CommentSlideover
            v-model:open="slideoverOpen"
            :comment="selectedComment"
            @approve="approveComment"
            @spam="spamComment"
            @trash="trashComment"
        />
    </DashboardLayout>
</template>
