<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, h, resolveComponent, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import CommentSlideover from '../../components/CommentSlideover.vue';
import { usePermission } from '../../composables/usePermission';
import type { TableColumn } from '@nuxt/ui';
import type { NavigationMenuItem } from '@nuxt/ui';
import { formatDistanceToNow } from 'date-fns';

interface Comment {
    id: number;
    uuid: string;
    content: string;
    content_excerpt: string;
    status: 'pending' | 'approved' | 'spam' | 'trashed';
    author_display_name: string;
    author_display_email: string;
    author_ip: string | null;
    gravatar_url: string;
    is_registered_user: boolean;
    is_edited: boolean;
    replies_count: number;
    post: {
        id: number;
        title: string;
        slug: string;
    } | null;
    created_at: string;
    edited_at: string | null;
}

interface PostOption {
    value: string;
    label: string;
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
    counts: {
        all: number;
        pending: number;
        approved: number;
        spam: number;
        trashed: number;
    };
    selectedPost: { id: number; title: string } | null;
    filters: {
        status?: string;
        post_id?: string;
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
}>();

const { can } = usePermission();

const UButton = resolveComponent('UButton');
const UBadge = resolveComponent('UBadge');
const UDropdownMenu = resolveComponent('UDropdownMenu');
const UCheckbox = resolveComponent('UCheckbox');
const UAvatar = resolveComponent('UAvatar');

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedPostId = ref(props.filters.post_id || '');
const rowSelection = ref<Record<string, boolean>>({});
const slideoverOpen = ref(false);
const selectedComment = ref<Comment | null>(null);

// Post search functionality
const postSearchQuery = ref('');
const postOptions = ref<PostOption[]>([]);
const isLoadingPosts = ref(false);

// Initialize with selected post if present
if (props.selectedPost) {
    postOptions.value = [{
        value: String(props.selectedPost.id),
        label: props.selectedPost.title,
    }];
}

const searchPosts = useDebounceFn(async (query: string) => {
    if (!query && !props.selectedPost) {
        postOptions.value = [];
        return;
    }

    isLoadingPosts.value = true;
    try {
        const response = await fetch(`/cms/comments/search-posts?q=${encodeURIComponent(query)}`);
        const results = await response.json();
        postOptions.value = results;
    } catch (error) {
        console.error('Failed to search posts:', error);
        postOptions.value = [];
    } finally {
        isLoadingPosts.value = false;
    }
}, 300);

watch(postSearchQuery, (query) => {
    searchPosts(query);
});

const selectedCount = computed(() => {
    return Object.values(rowSelection.value).filter(Boolean).length;
});

const selectedIds = computed(() => {
    return Object.entries(rowSelection.value)
        .filter(([_, selected]) => selected)
        .map(([index]) => props.comments.data[parseInt(index)]?.uuid)
        .filter(Boolean);
});

// Navigation menu items for status tabs
const statusLinks = computed<NavigationMenuItem[][]>(() => [[
    {
        label: 'All',
        icon: 'i-lucide-messages-square',
        badge: props.counts.all,
        to: '/cms/comments',
        active: !selectedStatus.value,
        onSelect: () => changeStatus(''),
    },
    {
        label: 'Pending',
        icon: 'i-lucide-clock',
        badge: props.counts.pending,
        to: '/cms/comments?status=pending',
        active: selectedStatus.value === 'pending',
        onSelect: () => changeStatus('pending'),
    },
    {
        label: 'Approved',
        icon: 'i-lucide-check-circle',
        badge: props.counts.approved,
        to: '/cms/comments?status=approved',
        active: selectedStatus.value === 'approved',
        onSelect: () => changeStatus('approved'),
    },
    {
        label: 'Spam',
        icon: 'i-lucide-shield-alert',
        badge: props.counts.spam,
        to: '/cms/comments?status=spam',
        active: selectedStatus.value === 'spam',
        onSelect: () => changeStatus('spam'),
    },
    {
        label: 'Trashed',
        icon: 'i-lucide-trash',
        badge: props.counts.trashed,
        to: '/cms/comments?status=trashed',
        active: selectedStatus.value === 'trashed',
        onSelect: () => changeStatus('trashed'),
    },
]]);

function clearSelection() {
    rowSelection.value = {};
}

function changeStatus(status: string) {
    selectedStatus.value = status;
    applyFilters();
}

function applyFilters() {
    router.get('/cms/comments', {
        status: selectedStatus.value || undefined,
        post_id: selectedPostId.value || undefined,
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
    applyFilters();
}

function clearFilters() {
    selectedStatus.value = '';
    selectedPostId.value = '';
    search.value = '';
    router.get('/cms/comments', {}, { preserveState: true, replace: true });
}

function sortBy(field: string) {
    const newDirection = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get('/cms/comments', {
        ...props.filters,
        sort: field,
        direction: newDirection,
    }, {
        preserveState: true,
        replace: true,
    });
}

function openComment(comment: Comment) {
    selectedComment.value = comment;
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

function restoreComment(comment: Comment) {
    router.post(`/cms/comments/${comment.uuid}/restore`, {}, {
        preserveScroll: true,
    });
}

function deleteComment(comment: Comment) {
    if (confirm('Are you sure you want to permanently delete this comment?')) {
        router.delete(`/cms/comments/${comment.uuid}`, {
            preserveScroll: true,
        });
    }
}

function bulkAction(action: 'approve' | 'spam' | 'trash' | 'delete') {
    if (selectedIds.value.length === 0) return;

    if (action === 'delete' && !confirm(`Permanently delete ${selectedIds.value.length} comments?`)) {
        return;
    }

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

function getStatusColor(status: string): 'warning' | 'success' | 'error' | 'neutral' {
    switch (status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'spam': return 'error';
        case 'trashed': return 'neutral';
        default: return 'neutral';
    }
}

function getRowActions(row: Comment) {
    const actions: any[][] = [];

    // View action
    actions.push([
        {
            label: 'View',
            icon: 'i-lucide-eye',
            onSelect: () => openComment(row),
        },
    ]);

    // Status-based actions
    if (can('comments.moderate')) {
        const statusActions: any[] = [];

        if (row.status !== 'approved') {
            statusActions.push({
                label: 'Approve',
                icon: 'i-lucide-check',
                onSelect: () => approveComment(row),
            });
        }
        if (row.status !== 'spam') {
            statusActions.push({
                label: 'Mark as Spam',
                icon: 'i-lucide-shield-alert',
                onSelect: () => spamComment(row),
            });
        }
        if (row.status !== 'trashed') {
            statusActions.push({
                label: 'Trash',
                icon: 'i-lucide-trash',
                onSelect: () => trashComment(row),
            });
        }
        if (row.status === 'trashed') {
            statusActions.push({
                label: 'Restore',
                icon: 'i-lucide-undo',
                onSelect: () => restoreComment(row),
            });
        }

        if (statusActions.length > 0) {
            actions.push(statusActions);
        }
    }

    // Delete action
    if (can('comments.delete')) {
        actions.push([
            {
                label: 'Delete Permanently',
                icon: 'i-lucide-trash-2',
                color: 'error' as const,
                onSelect: () => deleteComment(row),
            },
        ]);
    }

    return actions;
}

function onPostSelect(postId: string | null) {
    selectedPostId.value = postId || '';
    applyFilters();
}

function clearPostFilter() {
    selectedPostId.value = '';
    postSearchQuery.value = '';
    applyFilters();
}

const bulkActions = [
    [
        { label: 'Approve', icon: 'i-lucide-check', onSelect: () => bulkAction('approve') },
        { label: 'Mark as Spam', icon: 'i-lucide-shield-alert', onSelect: () => bulkAction('spam') },
        { label: 'Trash', icon: 'i-lucide-trash', onSelect: () => bulkAction('trash') },
    ],
    [
        { label: 'Delete Permanently', icon: 'i-lucide-trash-2', color: 'error' as const, onSelect: () => bulkAction('delete') },
    ],
];

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
        id: 'author',
        header: 'Author',
        cell: ({ row }) => {
            const comment = row.original;
            return h('div', { class: 'flex items-center gap-3' }, [
                h(UAvatar, {
                    src: comment.gravatar_url,
                    alt: comment.author_display_name,
                    size: 'sm',
                }),
                h('div', {}, [
                    h('div', { class: 'font-medium text-highlighted flex items-center gap-1.5' }, [
                        comment.author_display_name,
                        comment.is_registered_user && h('span', {
                            class: 'i-lucide-badge-check size-4 text-primary',
                            title: 'Registered user'
                        }),
                    ]),
                    h('div', { class: 'text-xs text-muted' }, comment.author_display_email),
                ]),
            ]);
        },
    },
    {
        accessorKey: 'content_excerpt',
        header: 'Comment',
        cell: ({ row }) => {
            const comment = row.original;
            return h('div', { class: 'max-w-md' }, [
                h('p', {
                    class: 'text-sm text-default line-clamp-2 cursor-pointer hover:text-highlighted',
                    onClick: () => openComment(comment),
                }, comment.content_excerpt),
                comment.replies_count > 0 && h('span', { class: 'text-xs text-muted mt-1 flex items-center gap-1' }, [
                    h('span', { class: 'i-lucide-message-square size-3' }),
                    `${comment.replies_count} ${comment.replies_count === 1 ? 'reply' : 'replies'}`,
                ]),
            ]);
        },
    },
    {
        id: 'post',
        header: 'Post',
        cell: ({ row }) => {
            const post = row.original.post;
            if (!post) return h('span', { class: 'text-muted' }, '-');
            return h('a', {
                href: `/cms/posts/${post.slug}`,
                class: 'text-sm text-primary hover:underline line-clamp-1 max-w-48',
            }, post.title);
        },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const comment = row.original;
            return h('div', { class: 'flex items-center gap-2' }, [
                h(UBadge, {
                    color: getStatusColor(comment.status),
                    variant: 'subtle',
                }, () => comment.status.charAt(0).toUpperCase() + comment.status.slice(1)),
                comment.is_edited && h(UBadge, {
                    color: 'neutral',
                    variant: 'subtle',
                }, () => 'Edited'),
            ]);
        },
    },
    {
        id: 'date',
        header: () => h('button', {
            class: 'flex items-center gap-1 hover:text-highlighted',
            onClick: () => sortBy('created_at'),
        }, [
            'Date',
            props.filters.sort === 'created_at' && h(
                'span',
                { class: 'i-lucide-' + (props.filters.direction === 'asc' ? 'chevron-up' : 'chevron-down') }
            ),
        ]),
        cell: ({ row }) => {
            return h('span', { class: 'text-sm text-muted whitespace-nowrap' },
                formatDistanceToNow(new Date(row.original.created_at), { addSuffix: true })
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
    <Head title="Comments" />

    <DashboardLayout>
        <UDashboardPanel id="comments">
            <template #header>
                <UDashboardNavbar title="Comments">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <UButton
                            v-if="counts.pending > 0"
                            color="primary"
                            variant="soft"
                            icon="i-lucide-inbox"
                            :to="'/cms/comments/queue'"
                        >
                            Moderation Queue
                            <UBadge color="primary" size="sm" class="ml-1">
                                {{ counts.pending }}
                            </UBadge>
                        </UButton>
                    </template>
                </UDashboardNavbar>

                <UDashboardToolbar>
                    <UNavigationMenu :items="statusLinks" highlight class="-mx-1 flex-1" />
                </UDashboardToolbar>
            </template>

            <template #body>
                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-3 mb-4">
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

                    <div class="relative">
                        <USelectMenu
                            v-model="selectedPostId"
                            v-model:search-term="postSearchQuery"
                            :items="postOptions"
                            value-key="value"
                            :searchable="true"
                            :loading="isLoadingPosts"
                            placeholder="Search posts..."
                            class="w-64"
                            @update:model-value="onPostSelect"
                        >
                            <template #empty>
                                <div class="text-center py-2 text-sm text-muted">
                                    {{ postSearchQuery ? 'No posts found' : 'Type to search posts...' }}
                                </div>
                            </template>
                        </USelectMenu>
                        <UButton
                            v-if="selectedPostId"
                            icon="i-lucide-x"
                            color="neutral"
                            variant="ghost"
                            size="xs"
                            class="absolute right-8 top-1/2 -translate-y-1/2"
                            @click="clearPostFilter"
                        />
                    </div>

                    <UButton
                        v-if="search"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        @click="clearFilters"
                    >
                        Clear Filters
                    </UButton>

                    <span class="ml-auto text-sm text-muted">
                        {{ comments.total }} comment{{ comments.total !== 1 ? 's' : '' }}
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
                        <UDropdownMenu
                            :items="bulkActions"
                            :content="{ align: 'end' }"
                        >
                            <UButton
                                color="primary"
                                variant="soft"
                                size="sm"
                                trailing-icon="i-lucide-chevron-down"
                            >
                                Actions
                            </UButton>
                        </UDropdownMenu>
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
                        td: 'border-b border-default',
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
                        @update:page="(page) => router.get('/cms/comments', { ...filters, page }, { preserveState: true })"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="comments.data.length === 0" class="text-center py-12 text-muted">
                    <UIcon name="i-lucide-message-square-off" class="size-12 mx-auto mb-4 opacity-50" />
                    <p v-if="selectedStatus === 'pending'">No pending comments to moderate.</p>
                    <p v-else-if="selectedStatus">No {{ selectedStatus }} comments found.</p>
                    <p v-else>No comments yet.</p>
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
            @restore="restoreComment"
            @delete="deleteComment"
        />
    </DashboardLayout>
</template>
