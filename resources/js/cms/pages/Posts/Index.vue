<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { formatDistanceToNow } from 'date-fns';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import { useCmsPath } from '../../composables/useCmsPath';
import type { Post, PostCounts, PostFilters, Author, Category, PaginatedResponse } from '../../types';
import type { NavigationMenuItem, DropdownMenuItem } from '@nuxt/ui';

interface LanguageInfo {
    code: string;
    name: string;
    native_name: string;
    direction: 'ltr' | 'rtl';
    is_rtl: boolean;
}

interface Language {
    code: string;
    name: string;
    native_name: string;
    direction: 'ltr' | 'rtl';
    is_default: boolean;
}

interface PostType {
    value: string;
    label: string;
    icon?: string;
}

interface UserCapabilities {
    isEditorOrAdmin: boolean;
    userId: number;
}

const props = defineProps<{
    posts: PaginatedResponse<Post>;
    counts: PostCounts;
    authors: Author[];
    categories: Category[];
    filters: PostFilters;
    language: LanguageInfo;
    languages: Language[];
    postTypes: PostType[];
    userCapabilities: UserCapabilities;
}>();

const { can } = usePermission();
const { cmsPath } = useCmsPath();

// Current language from props
const currentLanguageCode = computed(() => props.language.code);
const isRtlLanguage = computed(() => props.language.is_rtl);

// Create post functionality
const isCreatingPost = ref(false);

// Dropdown items for post type selection
const postTypeMenuItems = computed<DropdownMenuItem[][]>(() => [
    props.postTypes.map((type) => ({
        label: type.label,
        icon: type.icon || (type.value === 'recipe' ? 'i-lucide-chef-hat' : 'i-lucide-file-text'),
        disabled: isCreatingPost.value,
        onSelect: () => createPostOfType(type.value),
    })),
]);

async function createPostOfType(postType: string) {
    if (isCreatingPost.value) return;

    isCreatingPost.value = true;

    try {
        const response = await fetch(cmsPath('/posts/quick-draft'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                post_type: postType,
                language_code: currentLanguageCode.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.redirect) {
            router.visit(data.redirect);
        }
    } catch (e) {
        console.error('Create post error:', e);
    } finally {
        isCreatingPost.value = false;
    }
}

function getCsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find(row => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

const search = ref(props.filters.search || '');
const currentStatus = ref(props.filters.status || 'all');
const selectedPostType = ref<string | null>(props.filters.post_type || null);
const selectedAuthor = ref<string | null>(props.filters.author?.toString() || null);
const selectedCategory = ref<string | null>(props.filters.category?.toString() || null);
const showAllDrafts = ref(props.filters.show_all === '1' || props.filters.show_all === true);
const deleteModalOpen = ref(false);
const postToDelete = ref<Post | null>(null);

// Navigation menu items for status tabs
const statusLinks = computed<NavigationMenuItem[][]>(() => {
    const items: NavigationMenuItem[] = [
        {
            label: 'All',
            icon: 'i-lucide-layout-list',
            badge: props.counts.all,
            to: cmsPath(`/posts/${currentLanguageCode.value}`),
            active: currentStatus.value === 'all',
            onSelect: () => changeStatus('all'),
        },
        {
            label: 'Drafts',
            icon: 'i-lucide-file-edit',
            badge: props.counts.draft,
            to: cmsPath(`/posts/${currentLanguageCode.value}?status=draft`),
            active: currentStatus.value === 'draft',
            onSelect: () => changeStatus('draft'),
        },
        {
            label: 'Unpublished',
            icon: 'i-lucide-eye-off',
            badge: props.counts.unpublished,
            to: cmsPath(`/posts/${currentLanguageCode.value}?status=unpublished`),
            active: currentStatus.value === 'unpublished',
            onSelect: () => changeStatus('unpublished'),
        },
    ];

    // Only show Copydesk tab for editors/admins
    if (props.userCapabilities.isEditorOrAdmin) {
        items.push({
            label: 'Copydesk',
            icon: 'i-lucide-spell-check-2',
            badge: props.counts.copydesk,
            to: cmsPath(`/posts/${currentLanguageCode.value}?status=copydesk`),
            active: currentStatus.value === 'copydesk',
            onSelect: () => changeStatus('copydesk'),
        });
    }

    items.push(
        {
            label: 'Published',
            icon: 'i-lucide-globe',
            badge: props.counts.published,
            to: cmsPath(`/posts/${currentLanguageCode.value}?status=published`),
            active: currentStatus.value === 'published',
            onSelect: () => changeStatus('published'),
        },
        {
            label: 'Scheduled',
            icon: 'i-lucide-calendar-clock',
            badge: props.counts.scheduled,
            to: cmsPath(`/posts/${currentLanguageCode.value}?status=scheduled`),
            active: currentStatus.value === 'scheduled',
            onSelect: () => changeStatus('scheduled'),
        },
        {
            label: 'Trash',
            icon: 'i-lucide-trash',
            badge: props.counts.trashed,
            to: cmsPath(`/posts/${currentLanguageCode.value}?status=trashed`),
            active: currentStatus.value === 'trashed',
            onSelect: () => changeStatus('trashed'),
        },
    );

    return [items];
});

const postTypeOptions = computed(() => [
    { label: 'All Types', value: null },
    ...props.postTypes.map((t) => ({ label: t.label, value: t.value })),
]);

const authorOptions = computed(() => [
    { label: 'All Authors', value: null },
    ...props.authors.map((a) => ({ label: a.name, value: a.id.toString() })),
]);

const categoryOptions = computed(() => [
    { label: 'All Categories', value: null },
    ...props.categories.map((c) => ({ label: c.name, value: c.id.toString() })),
]);

function applyFilters(overrides: Record<string, any> = {}) {
    router.get(cmsPath(`/posts/${currentLanguageCode.value}`), {
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
        search: search.value || undefined,
        post_type: selectedPostType.value ?? undefined,
        author: selectedAuthor.value ?? undefined,
        category: selectedCategory.value ?? undefined,
        show_all: showAllDrafts.value && currentStatus.value === 'draft' ? '1' : undefined,
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

watch([selectedPostType, selectedAuthor, selectedCategory], () => {
    applyFilters();
});

function changeStatus(status: string) {
    currentStatus.value = status;
    if (status !== 'draft') {
        showAllDrafts.value = false;
    }
    applyFilters({ status: status !== 'all' ? status : undefined });
}

function confirmDelete(post: Post) {
    postToDelete.value = post;
    deleteModalOpen.value = true;
}

function deletePost() {
    if (postToDelete.value) {
        const langCode = postToDelete.value.language_code || currentLanguageCode.value;
        router.delete(cmsPath(`/posts/${langCode}/${postToDelete.value.uuid}`), {
            onSuccess: () => {
                deleteModalOpen.value = false;
                postToDelete.value = null;
            },
        });
    }
}

function restorePost(post: Post) {
    const langCode = post.language_code || currentLanguageCode.value;
    router.post(cmsPath(`/posts/${langCode}/${post.uuid}/restore`));
}

function forceDeletePost(post: Post) {
    const langCode = post.language_code || currentLanguageCode.value;
    router.delete(cmsPath(`/posts/${langCode}/${post.uuid}/force`));
}

function publishPost(post: Post) {
    const langCode = post.language_code || currentLanguageCode.value;
    router.post(cmsPath(`/posts/${langCode}/${post.uuid}/publish`));
}

function unpublishPost(post: Post) {
    const langCode = post.language_code || currentLanguageCode.value;
    router.post(cmsPath(`/posts/${langCode}/${post.uuid}/unpublish`));
}

function editPost(post: Post) {
    const langCode = post.language_code || currentLanguageCode.value;
    router.visit(cmsPath(`/posts/${langCode}/${post.uuid}/edit`));
}

function getStatusColor(status: string): 'success' | 'warning' | 'primary' | 'info' | 'neutral' {
    switch (status) {
        case 'published':
            return 'success';
        case 'draft':
            return 'neutral';
        case 'unpublished':
            return 'warning';
        case 'pending':
            return 'warning';
        case 'scheduled':
            return 'info';
        default:
            return 'neutral';
    }
}

function getWorkflowStatusColor(status: string): 'success' | 'warning' | 'primary' | 'info' | 'neutral' | 'error' {
    switch (status) {
        case 'draft':
            return 'neutral';
        case 'review':
            return 'warning';
        case 'copydesk':
            return 'info';
        case 'approved':
            return 'primary';
        case 'rejected':
            return 'error';
        case 'published':
            return 'success';
        default:
            return 'neutral';
    }
}

function getWorkflowStatusLabel(status: string): string {
    const labels: Record<string, string> = {
        draft: 'Draft',
        review: 'In Review',
        copydesk: 'Copydesk',
        approved: 'Approved',
        rejected: 'Needs Revision',
        published: 'Published',
    };
    return labels[status] || status;
}

function getWorkflowStatusIcon(status: string): string {
    const icons: Record<string, string> = {
        draft: 'i-lucide-file-edit',
        review: 'i-lucide-eye',
        copydesk: 'i-lucide-spell-check-2',
        approved: 'i-lucide-check-circle',
        rejected: 'i-lucide-alert-circle',
        published: 'i-lucide-globe',
    };
    return icons[status] || 'i-lucide-circle';
}

function getPostTypeIcon(type: string) {
    return type === 'recipe' ? 'i-lucide-chef-hat' : 'i-lucide-file-text';
}

function canEditPost(post: Post): boolean {
    const isAuthor = post.author?.id === props.userCapabilities.userId;
    const isEditorOrAdmin = props.userCapabilities.isEditorOrAdmin;

    // For published posts, only editors or the author can edit
    if (post.status === 'published') {
        return isEditorOrAdmin || isAuthor;
    }

    // For draft posts, user can edit if they have posts.edit or (posts.edit-own AND are the author)
    return can('posts.edit') || (can('posts.edit-own') && isAuthor);
}

function getRowActions(post: Post) {
    const actions: any[][] = [];
    const isAuthor = post.author?.id === props.userCapabilities.userId;
    const canEditThisPost = props.userCapabilities.isEditorOrAdmin || isAuthor;

    if (post.deleted_at) {
        // Trashed post actions
        if (canEditThisPost && (can('posts.edit') || can('posts.edit-own'))) {
            actions.push([
                {
                    label: 'Restore',
                    icon: 'i-lucide-undo',
                    onSelect: () => restorePost(post),
                },
            ]);
        }
        if (can('posts.delete')) {
            actions.push([
                {
                    label: 'Delete Permanently',
                    icon: 'i-lucide-trash',
                    color: 'error' as const,
                    onSelect: () => forceDeletePost(post),
                },
            ]);
        }
    } else {
        // Normal post actions
        // For published posts, only editors or the author can edit/unpublish
        const canEditPublished = post.status === 'published' ? canEditThisPost : true;

        if (canEditPublished && (can('posts.edit') || (can('posts.edit-own') && isAuthor))) {
            const langCode = post.language_code || currentLanguageCode.value;
            actions.push([
                {
                    label: 'Edit',
                    icon: 'i-lucide-pencil',
                    to: cmsPath(`/posts/${langCode}/${post.uuid}/edit`),
                },
            ]);
        }

        // Only editors or author can publish/unpublish
        if (can('posts.publish') && canEditThisPost) {
            if (post.status === 'published') {
                actions.push([
                    {
                        label: 'Unpublish',
                        icon: 'i-lucide-eye-off',
                        onSelect: () => unpublishPost(post),
                    },
                ]);
            } else if (post.status !== 'scheduled') {
                actions.push([
                    {
                        label: 'Publish',
                        icon: 'i-lucide-globe',
                        onSelect: () => publishPost(post),
                    },
                ]);
            }
        }

        if (can('posts.delete') && canEditThisPost) {
            actions.push([
                {
                    label: 'Move to Trash',
                    icon: 'i-lucide-trash',
                    color: 'error' as const,
                    onSelect: () => confirmDelete(post),
                },
            ]);
        }
    }

    return actions;
}

function formatDate(dateStr: string) {
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head title="Posts" />

    <DashboardLayout>
        <UDashboardPanel id="posts">
            <template #header>
                <UDashboardNavbar title="Posts">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <UDropdownMenu
                            v-if="can('posts.create')"
                            :items="postTypeMenuItems"
                            :content="{ align: 'end' }"
                        >
                            <UButton
                                icon="i-lucide-plus"
                                :loading="isCreatingPost"
                                trailing-icon="i-lucide-chevron-down"
                            >
                                New Post
                            </UButton>
                        </UDropdownMenu>
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
                        class="w-full sm:max-w-xs"
                        icon="i-lucide-search"
                        placeholder="Search posts..."
                    />

                    <USelectMenu
                        v-model="selectedPostType"
                        :items="postTypeOptions"
                        value-key="value"
                        class="w-full sm:w-40"
                        placeholder="Post Type"
                    />

                    <USelectMenu
                        v-model="selectedAuthor"
                        :items="authorOptions"
                        value-key="value"
                        class="w-full sm:w-44"
                        placeholder="Author"
                    />

                    <USelectMenu
                        v-model="selectedCategory"
                        :items="categoryOptions"
                        value-key="value"
                        class="w-full sm:w-44"
                        placeholder="Category"
                    />

                    <!-- Language indicator -->
                    <UBadge
                        :color="isRtlLanguage ? 'warning' : 'primary'"
                        variant="subtle"
                        size="lg"
                        class="gap-1.5"
                    >
                        <UIcon :name="isRtlLanguage ? 'i-lucide-align-right' : 'i-lucide-align-left'" class="size-3.5" />
                        {{ language.name }}
                    </UBadge>

                    <UButton
                        v-if="currentStatus === 'draft' && userCapabilities.isEditorOrAdmin"
                        :icon="showAllDrafts ? 'i-lucide-users' : 'i-lucide-user'"
                        :variant="showAllDrafts ? 'soft' : 'ghost'"
                        size="sm"
                        @click="showAllDrafts = !showAllDrafts; applyFilters()"
                    >
                        {{ showAllDrafts ? 'All Authors' : 'My Drafts' }}
                    </UButton>

                    <div class="text-sm text-muted ml-auto">
                        {{ posts.total }} post{{ posts.total !== 1 ? 's' : '' }}
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="posts.data.length === 0"
                    class="flex flex-col items-center justify-center py-12 text-center"
                >
                    <div class="size-12 rounded-full bg-muted/50 flex items-center justify-center mb-4">
                        <UIcon name="i-lucide-file-text" class="size-6 text-muted" />
                    </div>
                    <h3 class="text-lg font-medium text-highlighted">No posts found</h3>
                    <p class="text-muted mt-1">
                        {{ search || selectedPostType || selectedAuthor || selectedCategory
                            ? 'Try adjusting your filters'
                            : 'Get started by creating your first post' }}
                    </p>
                    <UDropdownMenu
                        v-if="can('posts.create') && !search"
                        :items="postTypeMenuItems"
                    >
                        <UButton
                            icon="i-lucide-plus"
                            class="mt-4"
                            :loading="isCreatingPost"
                            trailing-icon="i-lucide-chevron-down"
                        >
                            Create Post
                        </UButton>
                    </UDropdownMenu>
                </div>

                <!-- Post Cards -->
                <div v-else class="space-y-2">
                    <div
                        v-for="post in posts.data"
                        :key="post.uuid"
                        class="rounded-lg border border-default bg-default/50 hover:bg-elevated/50 transition-colors cursor-pointer"
                        :class="{ 'opacity-60': post.deleted_at }"
                        @click="editPost(post)"
                    >
                        <div class="p-3">
                            <div class="flex items-start gap-3">
                                <!-- Featured Image or Placeholder -->
                                <div class="shrink-0">
                                    <img
                                        v-if="post.featured_image_thumb"
                                        :src="post.featured_image_thumb"
                                        :alt="post.title"
                                        class="size-16 rounded-lg object-cover"
                                    />
                                    <div
                                        v-else
                                        class="size-16 rounded-lg bg-muted/50 flex items-center justify-center"
                                    >
                                        <UIcon :name="getPostTypeIcon(post.post_type)" class="size-6 text-muted" />
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <!-- Title -->
                                    <h3
                                        class="font-medium text-highlighted line-clamp-1"
                                        :class="{ 'font-dhivehi text-right': isRtlLanguage }"
                                        :dir="isRtlLanguage ? 'rtl' : 'ltr'"
                                    >
                                        {{ post.title }}
                                    </h3>

                                    <!-- Excerpt -->
                                    <p
                                        v-if="post.excerpt"
                                        class="text-sm text-muted line-clamp-1 mt-0.5"
                                        :class="{ 'font-dhivehi text-right': isRtlLanguage }"
                                        :dir="isRtlLanguage ? 'rtl' : 'ltr'"
                                    >
                                        {{ post.excerpt }}
                                    </p>

                                    <!-- Meta Row -->
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-xs text-muted">
                                        <!-- Author -->
                                        <div v-if="post.author" class="flex items-center gap-1.5">
                                            <UAvatar
                                                :src="post.author.avatar_url"
                                                :alt="post.author.name"
                                                size="2xs"
                                            />
                                            <span>{{ post.author.name }}</span>
                                        </div>

                                        <span class="text-default/30">·</span>

                                        <!-- Date -->
                                        <span>{{ formatDate(post.published_at || post.created_at) }}</span>

                                        <span class="text-default/30">·</span>

                                        <!-- Categories -->
                                        <div v-if="post.categories && post.categories.length > 0" class="flex items-center gap-1">
                                            <span
                                                v-for="(cat, idx) in post.categories.slice(0, 2)"
                                                :key="cat.id"
                                            >
                                                {{ cat.name }}{{ idx < Math.min(post.categories.length, 2) - 1 ? ',' : '' }}
                                            </span>
                                            <span v-if="post.categories.length > 2">+{{ post.categories.length - 2 }}</span>
                                        </div>
                                        <span v-else class="italic">Uncategorized</span>
                                    </div>

                                    <!-- Status & Type Badges -->
                                    <div class="flex flex-wrap items-center gap-2 mt-2">
                                        <!-- Workflow Status - only show editorial statuses (review, copydesk, approved, rejected) -->
                                        <UBadge
                                            v-if="post.workflow_status && !['draft', 'published'].includes(post.workflow_status) && post.workflow_status !== post.status"
                                            :color="getWorkflowStatusColor(post.workflow_status)"
                                            variant="soft"
                                            size="xs"
                                            class="gap-1"
                                        >
                                            <UIcon :name="getWorkflowStatusIcon(post.workflow_status)" class="size-3" />
                                            {{ getWorkflowStatusLabel(post.workflow_status) }}
                                        </UBadge>
                                        <UBadge
                                            :color="getStatusColor(post.status)"
                                            variant="subtle"
                                            size="xs"
                                        >
                                            {{ post.status.charAt(0).toUpperCase() + post.status.slice(1) }}
                                        </UBadge>
                                        <UBadge
                                            :color="post.post_type === 'recipe' ? 'warning' : 'primary'"
                                            variant="subtle"
                                            size="xs"
                                        >
                                            {{ post.post_type === 'recipe' ? 'Recipe' : 'Article' }}
                                        </UBadge>
                                        <UBadge
                                            v-if="post.deleted_at"
                                            color="error"
                                            variant="subtle"
                                            size="xs"
                                        >
                                            Trashed
                                        </UBadge>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-1 shrink-0" @click.stop>
                                    <template v-if="post.deleted_at">
                                        <UButton
                                            v-if="canEditPost(post)"
                                            icon="i-lucide-undo"
                                            color="neutral"
                                            variant="ghost"
                                            size="xs"
                                            title="Restore"
                                            @click="restorePost(post)"
                                        />
                                        <UButton
                                            v-if="can('posts.delete')"
                                            icon="i-lucide-trash-2"
                                            color="error"
                                            variant="ghost"
                                            size="xs"
                                            title="Delete Permanently"
                                            @click="forceDeletePost(post)"
                                        />
                                    </template>
                                    <template v-else>
                                        <UButton
                                            v-if="canEditPost(post)"
                                            icon="i-lucide-pencil"
                                            color="neutral"
                                            variant="ghost"
                                            size="xs"
                                            title="Edit"
                                            @click="editPost(post)"
                                        />
                                        <UButton
                                            v-if="can('posts.publish') && canEditPost(post) && post.status !== 'published'"
                                            icon="i-lucide-globe"
                                            color="success"
                                            variant="ghost"
                                            size="xs"
                                            title="Publish"
                                            @click="publishPost(post)"
                                        />
                                        <UButton
                                            v-if="can('posts.publish') && canEditPost(post) && post.status === 'published'"
                                            icon="i-lucide-eye-off"
                                            color="warning"
                                            variant="ghost"
                                            size="xs"
                                            title="Unpublish"
                                            @click="unpublishPost(post)"
                                        />
                                    </template>
                                    <UDropdownMenu
                                        :items="getRowActions(post)"
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
                <div
                    v-if="posts.last_page > 1"
                    class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-4"
                >
                    <div class="text-sm text-muted">
                        Showing {{ posts.from }} to {{ posts.to }} of {{ posts.total }}
                    </div>

                    <UPagination
                        :default-page="posts.current_page"
                        :items-per-page="posts.per_page"
                        :total="posts.total"
                        @update:page="(p: number) => router.get(cmsPath(`/posts/${currentLanguageCode}`), { ...filters, page: p })"
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
                            <h3 class="text-lg font-semibold text-highlighted">Move to Trash</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to trash <strong class="text-highlighted">{{ postToDelete?.title }}</strong>? You can restore it later from the trash.
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
                            @click="deletePost"
                        >
                            Move to Trash
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>

    </DashboardLayout>
</template>
