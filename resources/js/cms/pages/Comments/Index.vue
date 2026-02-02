<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import CommentSlideover from '../../components/CommentSlideover.vue';
import { usePermission } from '../../composables/usePermission';
import { useCmsPath } from '../../composables/useCmsPath';
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
    parent: {
        id: number;
        uuid: string;
        author_name: string;
        content_excerpt: string;
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
const { cmsPath } = useCmsPath();

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedPostId = ref(props.filters.post_id || '');
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
        const response = await fetch(cmsPath(`/comments/search-posts?q=${encodeURIComponent(query)}`));
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

// Navigation menu items for status tabs
const statusLinks = computed<NavigationMenuItem[][]>(() => [[
    {
        label: 'All',
        icon: 'i-lucide-messages-square',
        badge: props.counts.all,
        to: cmsPath('/comments'),
        active: !selectedStatus.value,
        onSelect: () => changeStatus(''),
    },
    {
        label: 'Pending',
        icon: 'i-lucide-clock',
        badge: props.counts.pending,
        to: cmsPath('/comments?status=pending'),
        active: selectedStatus.value === 'pending',
        onSelect: () => changeStatus('pending'),
    },
    {
        label: 'Approved',
        icon: 'i-lucide-check-circle',
        badge: props.counts.approved,
        to: cmsPath('/comments?status=approved'),
        active: selectedStatus.value === 'approved',
        onSelect: () => changeStatus('approved'),
    },
    {
        label: 'Spam',
        icon: 'i-lucide-shield-alert',
        badge: props.counts.spam,
        to: cmsPath('/comments?status=spam'),
        active: selectedStatus.value === 'spam',
        onSelect: () => changeStatus('spam'),
    },
    {
        label: 'Trashed',
        icon: 'i-lucide-trash',
        badge: props.counts.trashed,
        to: cmsPath('/comments?status=trashed'),
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
    router.get(cmsPath('/comments'), {
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
    router.get(cmsPath('/comments'), {}, { preserveState: true, replace: true });
}

function sortBy(field: string) {
    const newDirection = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(cmsPath('/comments'), {
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
    router.post(cmsPath(`/comments/${comment.uuid}/approve`), {}, {
        preserveScroll: true,
    });
}

function spamComment(comment: Comment) {
    router.post(cmsPath(`/comments/${comment.uuid}/spam`), {}, {
        preserveScroll: true,
    });
}

function trashComment(comment: Comment) {
    router.post(cmsPath(`/comments/${comment.uuid}/trash`), {}, {
        preserveScroll: true,
    });
}

function restoreComment(comment: Comment) {
    router.post(cmsPath(`/comments/${comment.uuid}/restore`), {}, {
        preserveScroll: true,
    });
}

function deleteComment(comment: Comment) {
    if (confirm('Are you sure you want to permanently delete this comment?')) {
        router.delete(cmsPath(`/comments/${comment.uuid}`), {
            preserveScroll: true,
        });
    }
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

// Selection management for cards
const selectedComments = ref<Set<string>>(new Set());

function toggleSelect(uuid: string) {
    if (selectedComments.value.has(uuid)) {
        selectedComments.value.delete(uuid);
    } else {
        selectedComments.value.add(uuid);
    }
    selectedComments.value = new Set(selectedComments.value);
}

function selectAll() {
    if (selectedComments.value.size === props.comments.data.length) {
        selectedComments.value.clear();
    } else {
        selectedComments.value = new Set(props.comments.data.map(c => c.uuid));
    }
    selectedComments.value = new Set(selectedComments.value);
}

const selectedUuids = computed(() => Array.from(selectedComments.value));

const cardSelectedCount = computed(() => selectedComments.value.size);

function clearCardSelection() {
    selectedComments.value.clear();
    selectedComments.value = new Set();
}

function cardBulkAction(action: 'approve' | 'spam' | 'trash' | 'delete') {
    if (selectedUuids.value.length === 0) return;

    if (action === 'delete' && !confirm(`Permanently delete ${selectedUuids.value.length} comments?`)) {
        return;
    }

    router.post(cmsPath('/comments/bulk'), {
        action,
        ids: selectedUuids.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            clearCardSelection();
        },
    });
}
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
                            :to="cmsPath('/comments/queue')"
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
                    v-if="cardSelectedCount > 0"
                    class="flex items-center justify-between gap-3 p-3 mb-4 rounded-lg bg-elevated border border-default"
                >
                    <div class="flex items-center gap-2">
                        <UIcon name="i-lucide-check-square" class="size-5 text-primary" />
                        <span class="text-sm font-medium">
                            {{ cardSelectedCount }} {{ cardSelectedCount === 1 ? 'comment' : 'comments' }} selected
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <UButton
                            color="neutral"
                            variant="ghost"
                            size="sm"
                            @click="clearCardSelection"
                        >
                            Clear
                        </UButton>
                        <UButton
                            color="success"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-check"
                            @click="cardBulkAction('approve')"
                        >
                            Approve
                        </UButton>
                        <UButton
                            color="warning"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-shield-alert"
                            @click="cardBulkAction('spam')"
                        >
                            Spam
                        </UButton>
                        <UButton
                            color="neutral"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-trash"
                            @click="cardBulkAction('trash')"
                        >
                            Trash
                        </UButton>
                    </div>
                </div>

                <!-- Select All -->
                <div v-if="comments.data.length > 0" class="flex items-center gap-2 mb-4">
                    <UCheckbox
                        :model-value="cardSelectedCount === comments.data.length && comments.data.length > 0"
                        :indeterminate="cardSelectedCount > 0 && cardSelectedCount < comments.data.length"
                        @update:model-value="selectAll"
                    />
                    <span class="text-sm text-muted">Select all on this page</span>
                </div>

                <!-- Comment Cards -->
                <div class="space-y-2">
                    <div
                        v-for="comment in comments.data"
                        :key="comment.uuid"
                        class="rounded-lg border border-default bg-default/50 hover:bg-elevated/50 transition-colors"
                        :class="{ 'ring-2 ring-primary': selectedComments.has(comment.uuid) }"
                    >
                        <div class="p-3">
                            <!-- Header: Author, Post, Date, Status -->
                            <div class="flex items-start gap-2.5">
                                <!-- Checkbox -->
                                <UCheckbox
                                    :model-value="selectedComments.has(comment.uuid)"
                                    class="mt-0.5"
                                    @update:model-value="toggleSelect(comment.uuid)"
                                />

                                <!-- Clickable content area -->
                                <div
                                    class="flex items-start gap-2.5 flex-1 min-w-0 cursor-pointer"
                                    @click="openComment(comment)"
                                >
                                    <!-- Avatar -->
                                    <UAvatar
                                        :src="comment.gravatar_url"
                                        :alt="comment.author_display_name"
                                        size="sm"
                                        class="shrink-0 mt-0.5"
                                    />

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <!-- Author & Meta -->
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                                            <span class="font-medium text-highlighted">
                                                {{ comment.author_display_name }}
                                            </span>
                                            <UIcon
                                                v-if="comment.is_registered_user"
                                                name="i-lucide-badge-check"
                                                class="size-3.5 text-primary"
                                            />
                                            <span class="text-muted">on</span>
                                            <a
                                                v-if="comment.post"
                                                :href="cmsPath(`/posts/${comment.post.language_code}/${comment.post.uuid}/edit`)"
                                                class="text-primary hover:underline font-medium truncate max-w-xs"
                                                @click.stop
                                            >
                                                {{ comment.post.title }}
                                            </a>
                                            <span v-else class="text-muted italic">deleted post</span>
                                            <span class="text-muted">·</span>
                                            <span class="text-muted">{{ formatDistanceToNow(new Date(comment.created_at), { addSuffix: true }) }}</span>
                                        </div>

                                        <!-- Parent Comment Indicator -->
                                        <div
                                            v-if="comment.parent"
                                            class="flex items-start gap-1.5 mt-1.5 text-xs text-muted bg-muted/30 rounded px-2 py-1.5"
                                        >
                                            <UIcon name="i-lucide-corner-down-right" class="size-3 shrink-0 mt-0.5" />
                                            <span>
                                                Reply to <span class="font-medium text-highlighted">{{ comment.parent.author_name }}</span>:
                                                "{{ comment.parent.content_excerpt }}"
                                            </span>
                                        </div>

                                        <!-- Comment Content -->
                                        <p class="text-sm text-default mt-1.5 line-clamp-2">
                                            {{ comment.content_excerpt }}
                                        </p>

                                        <!-- Status & Replies Row -->
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-muted mt-2">
                                            <UBadge
                                                :color="getStatusColor(comment.status)"
                                                variant="subtle"
                                                size="xs"
                                            >
                                                {{ comment.status.charAt(0).toUpperCase() + comment.status.slice(1) }}
                                            </UBadge>
                                            <UBadge
                                                v-if="comment.is_edited"
                                                color="neutral"
                                                variant="subtle"
                                                size="xs"
                                            >
                                                Edited
                                            </UBadge>
                                            <template v-if="comment.replies_count > 0">
                                                <span class="flex items-center gap-1">
                                                    <UIcon name="i-lucide-message-square" class="size-3" />
                                                    {{ comment.replies_count }} {{ comment.replies_count === 1 ? 'reply' : 'replies' }}
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-1 shrink-0">
                                    <template v-if="can('comments.moderate')">
                                        <UButton
                                            v-if="comment.status !== 'approved'"
                                            icon="i-lucide-check"
                                            color="success"
                                            variant="ghost"
                                            size="xs"
                                            title="Approve"
                                            @click="approveComment(comment)"
                                        />
                                        <UButton
                                            v-if="comment.status !== 'spam'"
                                            icon="i-lucide-shield-alert"
                                            color="warning"
                                            variant="ghost"
                                            size="xs"
                                            title="Mark as Spam"
                                            @click="spamComment(comment)"
                                        />
                                        <UButton
                                            v-if="comment.status !== 'trashed'"
                                            icon="i-lucide-trash"
                                            color="neutral"
                                            variant="ghost"
                                            size="xs"
                                            title="Trash"
                                            @click="trashComment(comment)"
                                        />
                                        <UButton
                                            v-if="comment.status === 'trashed'"
                                            icon="i-lucide-undo"
                                            color="neutral"
                                            variant="ghost"
                                            size="xs"
                                            title="Restore"
                                            @click="restoreComment(comment)"
                                        />
                                    </template>
                                    <UDropdownMenu
                                        :items="getRowActions(comment)"
                                        :content="{ align: 'end' }"
                                    >
                                        <UButton
                                            icon="i-lucide-ellipsis-vertical"
                                            color="neutral"
                                            variant="ghost"
                                            size="xs"
                                        />
                                    </UDropdownMenu>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
                        @update:page="(page) => router.get(cmsPath('/comments'), { ...filters, page }, { preserveState: true })"
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
