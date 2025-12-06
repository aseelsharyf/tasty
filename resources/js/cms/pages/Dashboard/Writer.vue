<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import type { PageProps } from '../../types';

const page = usePage<PageProps>();
const user = computed(() => page.props.auth?.user);

interface TargetProgress {
    current: number;
    target: number;
    percentage: number;
    remaining: number;
}

interface Target {
    id: number;
    period: string;
    target_count: number;
    is_assigned: boolean;
    days_remaining: number;
    progress: TargetProgress;
}

interface Streak {
    current: number;
    best: number;
    unit: string;
}

interface Stats {
    total_posts: number;
    published_this_period: number;
    in_review: number;
    drafts: number;
    rejected: number;
    avg_days_to_publish: number | null;
    by_post_type: { type: string; count: number }[];
    current_target: Target | null;
    streak: Streak;
}

interface Post {
    id: number;
    uuid: string;
    title: string;
    status: string;
    workflow_status: string | null;
    post_type?: string;
    language_code: string;
    updated_at: string;
    days_old?: number;
}

const props = defineProps<{
    greeting: string;
    stats: Stats;
    recentPosts: Post[];
    needsAttention: Post[];
}>();

// Target modal
const showTargetModal = ref(false);
const targetForm = ref({
    period_type: 'monthly',
    target_count: 10,
});
const savingTarget = ref(false);

async function saveTarget() {
    savingTarget.value = true;
    try {
        await router.post('/cms/targets', targetForm.value, {
            preserveScroll: true,
            onSuccess: () => {
                showTargetModal.value = false;
            },
        });
    } finally {
        savingTarget.value = false;
    }
}

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

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}

function formatRelativeDate(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));

    if (days === 0) return 'Today';
    if (days === 1) return 'Yesterday';
    if (days < 7) return `${days} days ago`;
    return formatDate(dateString);
}

const target = computed(() => props.stats.current_target);
const progressColor = computed(() => {
    if (!target.value) return 'primary';
    const pct = target.value.progress.percentage;
    if (pct >= 100) return 'success';
    if (pct >= 75) return 'primary';
    if (pct >= 50) return 'warning';
    return 'error';
});

function editPost(post: Post) {
    router.visit(`/cms/posts/${post.language_code}/${post.uuid}/edit`);
}
</script>

<template>
    <Head title="Dashboard" />

    <DashboardLayout>
        <UDashboardPanel id="writer-dashboard">
            <template #header>
                <UDashboardNavbar title="Dashboard">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                    <template #right>
                        <UColorModeButton color="neutral" variant="ghost" />
                        <Link href="/cms/posts/en/create">
                            <UButton icon="i-lucide-plus" label="New Post" />
                        </Link>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="max-w-6xl mx-auto space-y-6">
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
                            <p class="text-muted">Here's your writing progress for {{ new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) }}</p>
                        </div>
                    </div>

                    <!-- Target Progress & Stats Row -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Target Progress Card -->
                        <UPageCard
                            :title="target ? 'Target Progress' : 'Set Your Target'"
                            variant="outline"
                            class="lg:col-span-1"
                        >
                            <div v-if="target" class="flex flex-col items-center py-4">
                                <!-- Circular Progress -->
                                <div class="relative size-32 mb-4">
                                    <svg class="size-full -rotate-90" viewBox="0 0 36 36">
                                        <path
                                            class="stroke-current text-muted/20"
                                            stroke-width="3"
                                            fill="none"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                        />
                                        <path
                                            :class="`stroke-current text-${progressColor}`"
                                            stroke-width="3"
                                            stroke-linecap="round"
                                            fill="none"
                                            :stroke-dasharray="`${target.progress.percentage}, 100`"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                        />
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-2xl font-bold text-highlighted">{{ target.progress.current }}/{{ target.progress.target }}</span>
                                        <span class="text-xs text-muted">articles</span>
                                    </div>
                                </div>

                                <p class="text-sm text-muted mb-2">
                                    {{ target.progress.percentage }}% complete
                                </p>
                                <p class="text-xs text-dimmed">
                                    {{ target.days_remaining }} days remaining
                                </p>

                                <div class="flex items-center gap-2 mt-4">
                                    <UBadge
                                        v-if="target.is_assigned"
                                        color="info"
                                        variant="subtle"
                                        size="xs"
                                    >
                                        Assigned by admin
                                    </UBadge>
                                    <UButton
                                        v-if="!target.is_assigned"
                                        size="xs"
                                        variant="ghost"
                                        color="neutral"
                                        icon="i-lucide-edit"
                                        @click="showTargetModal = true"
                                    >
                                        Edit
                                    </UButton>
                                </div>
                            </div>

                            <div v-else class="flex flex-col items-center py-8">
                                <UIcon name="i-lucide-target" class="size-12 text-muted mb-4" />
                                <p class="text-muted text-sm mb-4 text-center">
                                    Set a monthly target to track your writing progress
                                </p>
                                <UButton
                                    label="Set Target"
                                    icon="i-lucide-plus"
                                    @click="showTargetModal = true"
                                />
                            </div>
                        </UPageCard>

                        <!-- Stats Cards -->
                        <div class="lg:col-span-2 grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-highlighted">{{ stats.published_this_period }}</p>
                                    <p class="text-xs text-muted">Published</p>
                                </div>
                            </UPageCard>

                            <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-warning">{{ stats.in_review }}</p>
                                    <p class="text-xs text-muted">In Review</p>
                                </div>
                            </UPageCard>

                            <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-muted">{{ stats.drafts }}</p>
                                    <p class="text-xs text-muted">Drafts</p>
                                </div>
                            </UPageCard>

                            <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-primary">{{ stats.total_posts }}</p>
                                    <p class="text-xs text-muted">Total Posts</p>
                                </div>
                            </UPageCard>

                            <!-- Streak Card -->
                            <UPageCard variant="outline" :ui="{ body: 'p-4' }" class="sm:col-span-2">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center size-10 rounded-full bg-warning/10">
                                        <UIcon name="i-lucide-flame" class="size-5 text-warning" />
                                    </div>
                                    <div>
                                        <p class="text-lg font-bold text-highlighted">
                                            {{ stats.streak.current }}-day streak
                                        </p>
                                        <p class="text-xs text-muted">
                                            Best: {{ stats.streak.best }} days
                                        </p>
                                    </div>
                                </div>
                            </UPageCard>

                            <!-- Avg Time Card -->
                            <UPageCard variant="outline" :ui="{ body: 'p-4' }" class="sm:col-span-2">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center size-10 rounded-full bg-info/10">
                                        <UIcon name="i-lucide-clock" class="size-5 text-info" />
                                    </div>
                                    <div>
                                        <p class="text-lg font-bold text-highlighted">
                                            {{ stats.avg_days_to_publish ?? '--' }} days
                                        </p>
                                        <p class="text-xs text-muted">
                                            Avg. time to publish
                                        </p>
                                    </div>
                                </div>
                            </UPageCard>
                        </div>
                    </div>

                    <!-- Needs Attention -->
                    <UPageCard
                        v-if="needsAttention.length > 0"
                        title="Needs Your Attention"
                        description="Posts that require action"
                        variant="outline"
                    >
                        <div class="divide-y divide-default -mx-4">
                            <div
                                v-for="post in needsAttention"
                                :key="post.id"
                                class="flex items-center gap-4 px-4 py-3 hover:bg-elevated/50 transition-colors cursor-pointer"
                                @click="editPost(post)"
                            >
                                <div class="flex items-center justify-center size-8 rounded-full" :class="{
                                    'bg-error/10': post.workflow_status === 'rejected',
                                    'bg-warning/10': post.workflow_status !== 'rejected',
                                }">
                                    <UIcon
                                        :name="post.workflow_status === 'rejected' ? 'i-lucide-alert-circle' : 'i-lucide-clock'"
                                        :class="{
                                            'text-error': post.workflow_status === 'rejected',
                                            'text-warning': post.workflow_status !== 'rejected',
                                        }"
                                        class="size-4"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-highlighted truncate">{{ post.title }}</p>
                                    <p class="text-xs text-muted">
                                        <template v-if="post.workflow_status === 'rejected'">
                                            Rejected {{ post.days_old }} days ago - needs revisions
                                        </template>
                                        <template v-else>
                                            Draft sitting for {{ post.days_old }} days
                                        </template>
                                    </p>
                                </div>
                                <UButton
                                    size="xs"
                                    variant="soft"
                                    :color="post.workflow_status === 'rejected' ? 'error' : 'warning'"
                                >
                                    {{ post.workflow_status === 'rejected' ? 'Fix' : 'Continue' }}
                                </UButton>
                            </div>
                        </div>
                    </UPageCard>

                    <!-- Recent Posts -->
                    <UPageCard
                        title="My Recent Posts"
                        description="Your latest work"
                        variant="outline"
                    >
                        <div v-if="recentPosts.length === 0" class="flex flex-col items-center py-8">
                            <UIcon name="i-lucide-file-text" class="size-12 text-muted mb-3" />
                            <p class="text-muted">No posts yet</p>
                            <Link href="/cms/posts/en/create" class="mt-4">
                                <UButton label="Create your first post" />
                            </Link>
                        </div>

                        <div v-else class="divide-y divide-default -mx-4">
                            <div
                                v-for="post in recentPosts"
                                :key="post.id"
                                class="flex items-center gap-4 px-4 py-3 hover:bg-elevated/50 transition-colors cursor-pointer"
                                @click="editPost(post)"
                            >
                                <div class="flex items-center justify-center size-8 rounded bg-muted/20">
                                    <UIcon
                                        :name="post.post_type === 'recipe' ? 'i-lucide-chef-hat' : 'i-lucide-file-text'"
                                        class="size-4 text-muted"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-highlighted truncate">{{ post.title }}</p>
                                    <p class="text-xs text-muted">{{ formatRelativeDate(post.updated_at) }}</p>
                                </div>
                                <UBadge
                                    :color="getStatusColor(post.workflow_status || post.status)"
                                    variant="subtle"
                                    size="sm"
                                >
                                    {{ post.workflow_status || post.status }}
                                </UBadge>
                            </div>
                        </div>

                        <div v-if="recentPosts.length > 0" class="pt-4 mt-2 border-t border-default">
                            <Link href="/cms/posts/en">
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

        <!-- Set Target Modal -->
        <UModal v-model:open="showTargetModal">
            <template #content>
                <UCard>
                    <template #header>
                        <div class="flex items-center gap-2">
                            <UIcon name="i-lucide-target" class="size-5 text-primary" />
                            <h3 class="font-semibold">Set Your Target</h3>
                        </div>
                    </template>

                    <div class="space-y-4">
                        <UFormField label="Period">
                            <USelectMenu
                                v-model="targetForm.period_type"
                                :items="[
                                    { label: 'Weekly', value: 'weekly' },
                                    { label: 'Monthly', value: 'monthly' },
                                    { label: 'Yearly', value: 'yearly' },
                                ]"
                                value-key="value"
                            />
                        </UFormField>

                        <UFormField label="Target (number of articles)">
                            <UInput
                                v-model.number="targetForm.target_count"
                                type="number"
                                min="1"
                                max="100"
                            />
                        </UFormField>
                    </div>

                    <template #footer>
                        <div class="flex justify-end gap-2">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                @click="showTargetModal = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                :loading="savingTarget"
                                @click="saveTarget"
                            >
                                Save Target
                            </UButton>
                        </div>
                    </template>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
