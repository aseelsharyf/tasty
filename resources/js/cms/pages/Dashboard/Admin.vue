<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import type { PageProps } from '../../types';
import type { DropdownMenuItem } from '@nuxt/ui';

const page = usePage<PageProps>();
const user = computed(() => page.props.auth?.user);

interface PostsByType {
    type: string;
    count: number;
}

interface PostsByStatus {
    status: string;
    count: number;
}

interface TopWriter {
    user: {
        id: number;
        uuid: string;
        name: string;
        avatar_url: string | null;
    };
    posts_count: number;
    target_progress: number | null;
}

interface PostsPerDay {
    date: string;
    count: number;
}

interface Stats {
    total_posts: number;
    published_today: number;
    published_this_week: number;
    published_this_month: number;
    pending_review: number;
    total_writers: number;
    active_writers: number;
    top_writers: TopWriter[];
    posts_per_day: PostsPerDay[];
    posts_by_type: PostsByType[];
    posts_by_status: PostsByStatus[];
}

interface Post {
    id: number;
    uuid: string;
    title: string;
    status?: string;
    workflow_status: string | null;
    language_code: string;
    author: {
        id: number;
        name: string;
    } | null;
    updated_at: string;
}

interface PostType {
    value: string;
    label: string;
    icon?: string;
}

const props = defineProps<{
    greeting: string;
    stats: Stats;
    pendingReview: Post[];
    recentActivity: Post[];
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

function getStatusColor(status: string): string {
    const colors: Record<string, string> = {
        published: 'success',
        draft: 'neutral',
        pending: 'warning',
        scheduled: 'info',
        review: 'warning',
        copydesk: 'info',
        approved: 'primary',
        rejected: 'error',
    };
    return colors[status] || 'neutral';
}

function formatRelativeDate(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor(diff / (1000 * 60));

    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days === 1) return 'Yesterday';
    if (days < 7) return `${days} days ago`;
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function editPost(post: Post) {
    router.visit(`/cms/posts/${post.language_code}/${post.uuid}/edit`);
}

// Chart data for posts per day
const postsPerDay = computed(() => props.stats?.posts_per_day || []);

const chartMax = computed(() => {
    if (!postsPerDay.value.length) return 1;
    return Math.max(...postsPerDay.value.map(d => d.count), 1);
});

function getBarHeight(count: number): string {
    const percentage = (count / chartMax.value) * 100;
    // Minimum 4% height so bars are visible even when 0
    return `${Math.max(percentage, 4)}%`;
}
</script>

<template>
    <Head title="Dashboard" />

    <DashboardLayout>
        <UDashboardPanel id="admin-dashboard">
            <template #header>
                <UDashboardNavbar title="Dashboard">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                    <template #right>
                        <UColorModeButton color="neutral" variant="ghost" />
                        <UDropdownMenu :items="postTypeMenuItems" :content="{ align: 'end' }">
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
            </template>

            <template #body>
                <div class="max-w-7xl mx-auto space-y-6">
                    <!-- Greeting with Avatar -->
                    <div class="flex items-center gap-4">
                        <UAvatar
                            v-if="user?.avatar_url"
                            :src="user.avatar_url"
                            :alt="user?.name"
                            size="xl"
                        />
                        <div v-else class="flex items-center justify-center size-16 rounded-full bg-primary/10 text-primary text-2xl font-semibold">
                            {{ user?.name?.charAt(0)?.toUpperCase() }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-highlighted">{{ greeting }}</h1>
                            <p class="text-muted">Here's what's happening with your content</p>
                        </div>
                    </div>

                    <!-- Overview Stats -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4">
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-highlighted">{{ stats.published_today }}</p>
                                <p class="text-xs text-muted">Published Today</p>
                            </div>
                        </UPageCard>

                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-primary">{{ stats.published_this_week }}</p>
                                <p class="text-xs text-muted">This Week</p>
                            </div>
                        </UPageCard>

                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-success">{{ stats.published_this_month }}</p>
                                <p class="text-xs text-muted">This Month</p>
                            </div>
                        </UPageCard>

                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-warning">{{ stats.pending_review }}</p>
                                <p class="text-xs text-muted">Pending Review</p>
                            </div>
                        </UPageCard>

                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-info">{{ stats.active_writers }}</p>
                                <p class="text-xs text-muted">Active Writers</p>
                            </div>
                        </UPageCard>

                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-muted">{{ stats.total_posts }}</p>
                                <p class="text-xs text-muted">Total Posts</p>
                            </div>
                        </UPageCard>
                    </div>

                    <!-- Charts Row -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Posts Per Day Chart -->
                        <UPageCard
                            title="Publishing Trend"
                            description="Posts published in the last 14 days"
                            variant="outline"
                        >
                            <div v-if="postsPerDay.length === 0" class="h-40 flex items-center justify-center">
                                <p class="text-muted text-sm">No data available</p>
                            </div>
                            <template v-else>
                                <div class="h-40 flex items-end gap-1">
                                    <div
                                        v-for="day in postsPerDay"
                                        :key="day.date"
                                        class="flex-1 flex flex-col items-center gap-1 group cursor-pointer"
                                    >
                                        <span class="text-xs text-muted opacity-0 group-hover:opacity-100 transition-opacity">{{ day.count }}</span>
                                        <div
                                            class="w-full bg-primary/30 group-hover:bg-primary rounded-t transition-all"
                                            :class="{ 'bg-primary': day.count > 0 }"
                                            :style="{ height: getBarHeight(day.count) }"
                                            :title="`${day.date}: ${day.count} posts`"
                                        />
                                    </div>
                                </div>
                                <div class="flex justify-between mt-2 text-xs text-muted">
                                    <span>{{ postsPerDay[0]?.date.slice(5) }}</span>
                                    <span>{{ postsPerDay[postsPerDay.length - 1]?.date.slice(5) }}</span>
                                </div>
                            </template>
                        </UPageCard>

                        <!-- Posts by Type -->
                        <UPageCard
                            title="Content Breakdown"
                            description="Posts by type"
                            variant="outline"
                        >
                            <div class="space-y-3">
                                <div
                                    v-for="item in stats.posts_by_type"
                                    :key="item.type"
                                    class="flex items-center gap-3"
                                >
                                    <div class="flex items-center justify-center size-8 rounded bg-muted/20">
                                        <UIcon
                                            :name="item.type === 'recipe' ? 'i-lucide-chef-hat' : item.type === 'video' ? 'i-lucide-video' : 'i-lucide-file-text'"
                                            class="size-4 text-muted"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm font-medium text-highlighted capitalize">{{ item.type || 'Article' }}</span>
                                            <span class="text-sm text-muted">{{ item.count }}</span>
                                        </div>
                                        <div class="h-1.5 bg-muted/20 rounded-full overflow-hidden">
                                            <div
                                                class="h-full bg-primary rounded-full"
                                                :style="{ width: `${(item.count / stats.total_posts) * 100}%` }"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </UPageCard>
                    </div>

                    <!-- Pending Review & Top Writers Row -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Pending Review -->
                        <UPageCard
                            title="Pending Review"
                            description="Posts waiting for approval"
                            variant="outline"
                        >
                            <div v-if="pendingReview.length === 0" class="flex flex-col items-center py-8">
                                <UIcon name="i-lucide-check-circle" class="size-12 text-success mb-3" />
                                <p class="text-muted">All caught up!</p>
                            </div>

                            <div v-else class="divide-y divide-default -mx-4">
                                <div
                                    v-for="post in pendingReview"
                                    :key="post.id"
                                    class="flex items-center gap-3 px-4 py-3 hover:bg-elevated/50 transition-colors cursor-pointer"
                                    @click="editPost(post)"
                                >
                                    <div class="flex-1 min-w-0 overflow-hidden">
                                        <p class="font-medium text-highlighted truncate text-sm">{{ post.title }}</p>
                                        <p class="text-xs text-muted truncate">
                                            {{ post.author?.name || 'Unknown' }} • {{ formatRelativeDate(post.updated_at) }}
                                        </p>
                                    </div>
                                    <UBadge
                                        :color="getStatusColor(post.workflow_status || '')"
                                        variant="subtle"
                                        size="sm"
                                        class="shrink-0"
                                    >
                                        {{ post.workflow_status }}
                                    </UBadge>
                                </div>
                            </div>

                            <div v-if="pendingReview.length > 0" class="pt-4 mt-2 border-t border-default">
                                <Link href="/cms/posts/en?workflow_status=review">
                                    <UButton
                                        color="neutral"
                                        variant="ghost"
                                        size="sm"
                                        trailing-icon="i-lucide-arrow-right"
                                        block
                                    >
                                        View all pending
                                    </UButton>
                                </Link>
                            </div>
                        </UPageCard>

                        <!-- Top Writers -->
                        <UPageCard
                            title="Top Writers"
                            description="This month's leaderboard"
                            variant="outline"
                        >
                            <div v-if="stats.top_writers.length === 0" class="flex flex-col items-center py-8">
                                <UIcon name="i-lucide-users" class="size-12 text-muted mb-3" />
                                <p class="text-muted">No published posts this month</p>
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="(writer, index) in stats.top_writers"
                                    :key="writer.user.id"
                                    class="flex items-center gap-3"
                                >
                                    <div class="flex items-center justify-center size-8 rounded-full" :class="{
                                        'bg-warning/20 text-warning': index === 0,
                                        'bg-muted/30 text-muted': index === 1,
                                        'bg-warning/10 text-warning/70': index === 2,
                                        'bg-muted/10 text-muted': index > 2,
                                    }">
                                        <template v-if="index < 3">
                                            <UIcon name="i-lucide-trophy" class="size-4" />
                                        </template>
                                        <template v-else>
                                            <span class="text-xs font-medium">{{ index + 1 }}</span>
                                        </template>
                                    </div>
                                    <UAvatar
                                        v-if="writer.user.avatar_url"
                                        :src="writer.user.avatar_url"
                                        :alt="writer.user.name"
                                        size="sm"
                                    />
                                    <div v-else class="flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary text-xs font-medium">
                                        {{ writer.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-highlighted truncate">{{ writer.user.name }}</p>
                                        <p class="text-xs text-muted">{{ writer.posts_count }} posts</p>
                                    </div>
                                    <div v-if="writer.target_progress !== null" class="text-right">
                                        <p class="text-sm font-medium" :class="{
                                            'text-success': writer.target_progress >= 100,
                                            'text-primary': writer.target_progress >= 75 && writer.target_progress < 100,
                                            'text-warning': writer.target_progress >= 50 && writer.target_progress < 75,
                                            'text-error': writer.target_progress < 50,
                                        }">
                                            {{ writer.target_progress }}%
                                        </p>
                                        <p class="text-xs text-muted">of target</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="stats.top_writers.length > 0" class="pt-4 mt-2 border-t border-default">
                                <Link href="/cms/targets">
                                    <UButton
                                        color="neutral"
                                        variant="ghost"
                                        size="sm"
                                        trailing-icon="i-lucide-arrow-right"
                                        block
                                    >
                                        View all writers
                                    </UButton>
                                </Link>
                            </div>
                        </UPageCard>
                    </div>

                    <!-- Recent Activity -->
                    <UPageCard
                        title="Recent Activity"
                        description="Latest content updates"
                        variant="outline"
                    >
                        <div v-if="recentActivity.length === 0" class="flex flex-col items-center py-8">
                            <UIcon name="i-lucide-activity" class="size-12 text-muted mb-3" />
                            <p class="text-muted">No recent activity</p>
                        </div>

                        <div v-else class="divide-y divide-default -mx-4">
                            <div
                                v-for="post in recentActivity"
                                :key="post.id"
                                class="flex items-center gap-4 px-4 py-3 hover:bg-elevated/50 transition-colors cursor-pointer overflow-hidden"
                                @click="editPost(post)"
                            >
                                <div class="flex items-center justify-center size-8 rounded" :class="{
                                    'bg-success/10': post.status === 'published',
                                    'bg-muted/20': post.status !== 'published',
                                }">
                                    <UIcon
                                        :name="post.status === 'published' ? 'i-lucide-check' : 'i-lucide-file-edit'"
                                        :class="{
                                            'text-success': post.status === 'published',
                                            'text-muted': post.status !== 'published',
                                        }"
                                        class="size-4"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-highlighted truncate">{{ post.title }}</p>
                                    <p class="text-xs text-muted">
                                        {{ post.author?.name || 'Unknown' }} • {{ formatRelativeDate(post.updated_at) }}
                                    </p>
                                </div>
                                <UBadge
                                    :color="getStatusColor(post.workflow_status || post.status || '')"
                                    variant="subtle"
                                    size="sm"
                                >
                                    {{ post.workflow_status || post.status }}
                                </UBadge>
                            </div>
                        </div>
                    </UPageCard>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
