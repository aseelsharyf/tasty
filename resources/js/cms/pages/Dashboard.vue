<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../layouts/DashboardLayout.vue';
import type { DropdownMenuItem } from '@nuxt/ui';

interface RecentPost {
    id: number;
    uuid: string;
    title: string;
    status: string;
    post_type: string;
    language_code?: string;
    author: {
        name: string;
        avatar_url?: string | null;
    } | null;
    created_at: string;
}

interface PostType {
    value: string;
    label: string;
    icon?: string;
}

const props = defineProps<{
    stats: {
        users: number;
        posts: number;
        published: number;
        drafts: number;
        pending: number;
        scheduled: number;
        articles: number;
        recipes: number;
        categories: number;
        tags: number;
    };
    recentPosts: RecentPost[];
    postTypes?: PostType[];
    defaultLanguage?: string;
}>();

// Create post functionality
const isCreatingPost = ref(false);

function getCsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find(row => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

async function createPostOfType(postType: string) {
    if (isCreatingPost.value) return;

    isCreatingPost.value = true;

    try {
        const response = await fetch('/cms/posts/quick-draft', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                post_type: postType,
                language_code: props.defaultLanguage || 'en',
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

// Post type menu items for dropdown
const postTypeMenuItems = computed<DropdownMenuItem[][]>(() => {
    const types = props.postTypes || [
        { value: 'article', label: 'Article' },
        { value: 'recipe', label: 'Recipe' },
    ];

    return [
        types.map((type) => ({
            label: type.label,
            icon: type.icon || (type.value === 'recipe' ? 'i-lucide-chef-hat' : 'i-lucide-file-text'),
            disabled: isCreatingPost.value,
            onSelect: () => createPostOfType(type.value),
        })),
    ];
});

const quickActions: DropdownMenuItem[][] = [
    [
        {
            label: 'New User',
            icon: 'i-lucide-user-plus',
            to: '/cms/users/create',
        },
        {
            label: 'Upload Media',
            icon: 'i-lucide-upload',
            to: '/cms/media/upload',
        },
    ],
];

function getStatusColor(status: string): string {
    switch (status) {
        case 'published':
            return 'success';
        case 'draft':
            return 'neutral';
        case 'pending':
            return 'warning';
        case 'scheduled':
            return 'info';
        default:
            return 'neutral';
    }
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head title="Dashboard" />

    <DashboardLayout>
        <UDashboardPanel id="dashboard">
            <template #header>
                <UDashboardNavbar title="Dashboard" :ui="{ right: 'gap-3' }">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <UColorModeButton color="neutral" variant="ghost" />

                        <UDropdownMenu :items="quickActions">
                            <UButton icon="i-lucide-plus" class="rounded-full" />
                        </UDropdownMenu>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <!-- Welcome Section -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-highlighted">Welcome back!</h2>
                    <p class="text-muted text-sm">Here's what's happening with your site today.</p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <Link href="/cms/posts">
                        <UPageCard variant="outline" class="hover:border-primary transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center justify-center size-12 rounded-xl bg-primary/10">
                                    <UIcon name="i-lucide-file-text" class="size-6 text-primary" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted">Total Posts</p>
                                    <p class="text-2xl font-semibold text-highlighted">{{ stats.posts }}</p>
                                </div>
                            </div>
                        </UPageCard>
                    </Link>

                    <Link href="/cms/posts?status=published">
                        <UPageCard variant="outline" class="hover:border-success transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center justify-center size-12 rounded-xl bg-success/10">
                                    <UIcon name="i-lucide-globe" class="size-6 text-success" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted">Published</p>
                                    <p class="text-2xl font-semibold text-highlighted">{{ stats.published }}</p>
                                </div>
                            </div>
                        </UPageCard>
                    </Link>

                    <Link href="/cms/posts?status=pending">
                        <UPageCard variant="outline" class="hover:border-warning transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center justify-center size-12 rounded-xl bg-warning/10">
                                    <UIcon name="i-lucide-clipboard-check" class="size-6 text-warning" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted">Pending Review</p>
                                    <p class="text-2xl font-semibold text-highlighted">{{ stats.pending }}</p>
                                </div>
                            </div>
                        </UPageCard>
                    </Link>

                    <Link href="/cms/users">
                        <UPageCard variant="outline" class="hover:border-info transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center justify-center size-12 rounded-xl bg-info/10">
                                    <UIcon name="i-lucide-users" class="size-6 text-info" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted">Users</p>
                                    <p class="text-2xl font-semibold text-highlighted">{{ stats.users }}</p>
                                </div>
                            </div>
                        </UPageCard>
                    </Link>
                </div>

                <!-- Secondary Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                    <Link href="/cms/posts?status=draft">
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }" class="hover:border-primary/50 transition-colors">
                            <div class="text-center">
                                <p class="text-2xl font-semibold text-highlighted">{{ stats.drafts }}</p>
                                <p class="text-xs font-medium text-muted">Drafts</p>
                            </div>
                        </UPageCard>
                    </Link>

                    <Link href="/cms/posts?status=scheduled">
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }" class="hover:border-primary/50 transition-colors">
                            <div class="text-center">
                                <p class="text-2xl font-semibold text-highlighted">{{ stats.scheduled }}</p>
                                <p class="text-xs font-medium text-muted">Scheduled</p>
                            </div>
                        </UPageCard>
                    </Link>

                    <Link href="/cms/posts?post_type=article">
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }" class="hover:border-primary/50 transition-colors">
                            <div class="text-center">
                                <p class="text-2xl font-semibold text-highlighted">{{ stats.articles }}</p>
                                <p class="text-xs font-medium text-muted">Articles</p>
                            </div>
                        </UPageCard>
                    </Link>

                    <Link href="/cms/posts?post_type=recipe">
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }" class="hover:border-primary/50 transition-colors">
                            <div class="text-center">
                                <p class="text-2xl font-semibold text-highlighted">{{ stats.recipes }}</p>
                                <p class="text-xs font-medium text-muted">Recipes</p>
                            </div>
                        </UPageCard>
                    </Link>
                </div>

                <!-- Quick Actions & Recent Activity -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <UPageCard
                        title="Quick Actions"
                        description="Common tasks you can perform"
                        variant="outline"
                    >
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <UDropdownMenu :items="postTypeMenuItems">
                                <UButton
                                    color="neutral"
                                    variant="soft"
                                    icon="i-lucide-file-plus"
                                    trailing-icon="i-lucide-chevron-down"
                                    block
                                    :loading="isCreatingPost"
                                    class="justify-start"
                                >
                                    New Post
                                </UButton>
                            </UDropdownMenu>
                            <Link href="/cms/users/create">
                                <UButton
                                    color="neutral"
                                    variant="soft"
                                    icon="i-lucide-user-plus"
                                    block
                                    class="justify-start"
                                >
                                    Add User
                                </UButton>
                            </Link>
                            <Link href="/cms/media/upload">
                                <UButton
                                    color="neutral"
                                    variant="soft"
                                    icon="i-lucide-upload"
                                    block
                                    class="justify-start"
                                >
                                    Upload Media
                                </UButton>
                            </Link>
                            <Link href="/cms/settings">
                                <UButton
                                    color="neutral"
                                    variant="soft"
                                    icon="i-lucide-settings"
                                    block
                                    class="justify-start"
                                >
                                    Settings
                                </UButton>
                            </Link>
                        </div>
                    </UPageCard>

                    <UPageCard
                        title="Recent Posts"
                        description="Latest posts on your site"
                        variant="outline"
                    >
                        <div v-if="recentPosts.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
                            <UIcon name="i-lucide-file-text" class="size-12 text-muted mb-3" />
                            <p class="text-muted text-sm">No posts yet</p>
                            <p class="text-dimmed text-xs mt-1">Create your first post to get started</p>
                        </div>
                        <div v-else class="divide-y divide-default -mx-4">
                            <Link
                                v-for="post in recentPosts"
                                :key="post.id"
                                :href="`/cms/posts/${post.uuid}/edit`"
                                class="flex items-center gap-3 px-4 py-3 hover:bg-elevated/50 transition-colors overflow-hidden"
                            >
                                <div class="flex items-center justify-center size-8 rounded bg-muted/50 shrink-0">
                                    <UIcon
                                        :name="post.post_type === 'recipe' ? 'i-lucide-chef-hat' : 'i-lucide-file-text'"
                                        class="size-4 text-muted"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-highlighted truncate">{{ post.title }}</p>
                                    <p class="text-xs text-muted">
                                        {{ post.author?.name || 'Unknown' }} · {{ formatDate(post.created_at) }}
                                    </p>
                                </div>
                                <UBadge
                                    :color="getStatusColor(post.status) as any"
                                    variant="subtle"
                                    size="sm"
                                >
                                    {{ post.status }}
                                </UBadge>
                            </Link>
                        </div>
                        <div v-if="recentPosts.length > 0" class="pt-4 mt-2 border-t border-default">
                            <Link href="/cms/posts">
                                <UButton
                                    color="neutral"
                                    variant="ghost"
                                    size="sm"
                                    trailing-icon="i-lucide-arrow-right"
                                    block
                                >
                                    View all posts
                                </UButton>
                            </Link>
                        </div>
                    </UPageCard>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
