<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import CommentSlideover from '../../components/CommentSlideover.vue';
import { usePermission } from '../../composables/usePermission';
import { formatDistanceToNow } from 'date-fns';

interface Comment {
    id: number;
    uuid: string;
    content: string;
    content_excerpt?: string;
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
    parent: {
        id: number;
        uuid: string;
        author_name: string;
        content_excerpt: string;
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

const search = ref(props.filters.search || '');
const slideoverOpen = ref(false);
const selectedComment = ref<Comment | null>(null);

// Selection management
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
const selectedCount = computed(() => selectedComments.value.size);

function clearSelection() {
    selectedComments.value.clear();
    selectedComments.value = new Set();
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

function bulkAction(action: 'approve' | 'spam' | 'trash') {
    if (selectedUuids.value.length === 0) return;

    router.post('/cms/comments/bulk', {
        action,
        ids: selectedUuids.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            clearSelection();
        },
    });
}
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
                <!-- Header with count and search -->
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
                            Approve
                        </UButton>
                        <UButton
                            color="warning"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-shield-alert"
                            @click="bulkAction('spam')"
                        >
                            Spam
                        </UButton>
                        <UButton
                            color="neutral"
                            variant="soft"
                            size="sm"
                            icon="i-lucide-trash"
                            @click="bulkAction('trash')"
                        >
                            Trash
                        </UButton>
                    </div>
                </div>

                <!-- Select All -->
                <div v-if="comments.data.length > 0" class="flex items-center gap-2 mb-4">
                    <UCheckbox
                        :model-value="selectedCount === comments.data.length && comments.data.length > 0"
                        :indeterminate="selectedCount > 0 && selectedCount < comments.data.length"
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
                                                :href="`/cms/posts/${comment.post.slug}`"
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
                                            {{ comment.content_excerpt || comment.content }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div v-if="can('comments.moderate')" class="flex items-center gap-1 shrink-0">
                                    <UButton
                                        icon="i-lucide-check"
                                        color="success"
                                        variant="ghost"
                                        size="xs"
                                        title="Approve"
                                        @click="approveComment(comment)"
                                    />
                                    <UButton
                                        icon="i-lucide-shield-alert"
                                        color="warning"
                                        variant="ghost"
                                        size="xs"
                                        title="Mark as Spam"
                                        @click="spamComment(comment)"
                                    />
                                    <UButton
                                        icon="i-lucide-trash"
                                        color="neutral"
                                        variant="ghost"
                                        size="xs"
                                        title="Trash"
                                        @click="trashComment(comment)"
                                    />
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
