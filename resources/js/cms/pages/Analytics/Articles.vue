<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { ArticleAnalytics } from '../../types';

const { cmsPath } = useCmsPath();

const props = defineProps<{
    analytics: ArticleAnalytics;
    period: string;
}>();

const periods = [
    { label: '7 days', value: '7d' },
    { label: '30 days', value: '30d' },
    { label: '90 days', value: '90d' },
];

function changePeriod(value: string) {
    router.visit(cmsPath(`/analytics/articles?period=${value}`), {
        preserveState: true,
        preserveScroll: true,
    });
}

// Chart helpers
const viewsOverTime = computed(() => props.analytics?.views_over_time || []);
const chartMax = computed(() => {
    if (!viewsOverTime.value.length) return 1;
    return Math.max(...viewsOverTime.value.map(d => d.count), 1);
});
const CHART_HEIGHT = 160; // matches h-40 (10rem)
function getBarHeight(count: number): string {
    const px = (count / chartMax.value) * CHART_HEIGHT;
    return `${Math.max(px, 2)}px`;
}

const totalTypeViews = computed(() => {
    return (props.analytics?.views_by_type || []).reduce((sum, item) => sum + item.views, 0) || 1;
});

const totalCategoryViews = computed(() => {
    return (props.analytics?.views_by_category || []).reduce((sum, item) => sum + item.views, 0) || 1;
});

function formatNumber(num: number): string {
    if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`;
    if (num >= 1000) return `${(num / 1000).toFixed(1)}K`;
    return num.toString();
}

function editPost(article: { uuid?: string; language_code?: string }) {
    if (article.uuid) {
        router.visit(cmsPath(`/posts/${article.language_code || 'en'}/${article.uuid}/edit`));
    }
}
</script>

<template>
    <Head title="Article Analytics" />

    <DashboardLayout>
        <UDashboardPanel id="analytics-articles">
            <template #header>
                <UDashboardNavbar title="Article Analytics">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                    <template #right>
                        <div class="flex items-center gap-2">
                            <UButtonGroup>
                                <UButton
                                    v-for="p in periods"
                                    :key="p.value"
                                    :color="period === p.value ? 'primary' : 'neutral'"
                                    :variant="period === p.value ? 'solid' : 'ghost'"
                                    size="sm"
                                    @click="changePeriod(p.value)"
                                >
                                    {{ p.label }}
                                </UButton>
                            </UButtonGroup>
                        </div>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="max-w-7xl mx-auto space-y-6">
                    <!-- Summary Stats -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-highlighted">{{ formatNumber(analytics.summary.today) }}</p>
                                <p class="text-xs text-muted">Views Today</p>
                            </div>
                        </UPageCard>
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-primary">{{ formatNumber(analytics.summary.this_week) }}</p>
                                <p class="text-xs text-muted">This Week</p>
                            </div>
                        </UPageCard>
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-success">{{ formatNumber(analytics.summary.this_month) }}</p>
                                <p class="text-xs text-muted">This Month</p>
                            </div>
                        </UPageCard>
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-muted">{{ formatNumber(analytics.summary.total) }}</p>
                                <p class="text-xs text-muted">All Time</p>
                            </div>
                        </UPageCard>
                    </div>

                    <!-- Views Over Time Chart -->
                    <UPageCard
                        title="Views Over Time"
                        :description="`Article views in the last ${period === '7d' ? '7' : period === '90d' ? '90' : '30'} days`"
                        variant="outline"
                    >
                        <div v-if="viewsOverTime.length === 0" class="h-40 flex items-center justify-center">
                            <p class="text-muted text-sm">No data available</p>
                        </div>
                        <template v-else>
                            <div class="h-40 flex items-end overflow-hidden" :class="viewsOverTime.length > 31 ? 'gap-px' : 'gap-0.5'">
                                <div
                                    v-for="day in viewsOverTime"
                                    :key="day.date"
                                    class="flex-1 min-w-0 group cursor-pointer"
                                    :title="`${day.date}: ${day.count} views`"
                                >
                                    <div
                                        class="w-full bg-primary/30 group-hover:bg-primary rounded-t transition-all"
                                        :class="{ 'bg-primary': day.count > 0 }"
                                        :style="{ height: getBarHeight(day.count) }"
                                    />
                                </div>
                            </div>
                            <div class="flex justify-between mt-2 text-xs text-muted">
                                <span>{{ viewsOverTime[0]?.date.slice(5) }}</span>
                                <span>{{ viewsOverTime[viewsOverTime.length - 1]?.date.slice(5) }}</span>
                            </div>
                        </template>
                    </UPageCard>

                    <!-- Top Articles -->
                    <UPageCard
                        title="Top Articles"
                        description="Most viewed articles in this period"
                        variant="outline"
                    >
                        <div v-if="analytics.top_articles.length === 0" class="flex flex-col items-center py-8">
                            <UIcon name="i-lucide-file-text" class="size-12 text-muted mb-3" />
                            <p class="text-muted">No views recorded yet</p>
                        </div>
                        <div v-else class="divide-y divide-default -mx-6">
                            <div
                                v-for="(article, index) in analytics.top_articles"
                                :key="article.id"
                                class="flex items-center gap-4 px-6 py-3 hover:bg-elevated/50 transition-colors cursor-pointer"
                                @click="editPost(article)"
                            >
                                <span class="text-sm font-medium text-muted w-6 text-right">{{ index + 1 }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-highlighted truncate">{{ article.title }}</p>
                                    <p class="text-xs text-muted">
                                        {{ article.author?.name || 'Unknown' }}
                                        <span v-if="article.category"> &middot; {{ article.category }}</span>
                                    </p>
                                </div>
                                <UBadge v-if="article.post_type" :color="article.post_type === 'recipe' ? 'warning' : 'info'" variant="subtle" size="sm">
                                    {{ article.post_type }}
                                </UBadge>
                                <span class="text-sm font-semibold text-highlighted">{{ formatNumber(article.views) }}</span>
                            </div>
                        </div>
                    </UPageCard>

                    <!-- Views by Type & Category -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                        <UPageCard
                            title="Views by Post Type"
                            variant="outline"
                        >
                            <div v-if="analytics.views_by_type.length === 0" class="py-8 text-center text-muted text-sm">No data</div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="item in analytics.views_by_type"
                                    :key="item.type"
                                    class="flex items-center gap-3"
                                >
                                    <div class="flex items-center justify-center size-8 rounded bg-muted/20">
                                        <UIcon
                                            :name="item.type === 'recipe' ? 'i-lucide-chef-hat' : 'i-lucide-file-text'"
                                            class="size-4 text-muted"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm font-medium text-highlighted capitalize">{{ item.type || 'Article' }}</span>
                                            <span class="text-sm text-muted">{{ formatNumber(item.views) }}</span>
                                        </div>
                                        <div class="h-1.5 bg-muted/20 rounded-full overflow-hidden">
                                            <div
                                                class="h-full bg-primary rounded-full"
                                                :style="{ width: `${(item.views / totalTypeViews) * 100}%` }"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </UPageCard>

                        <UPageCard
                            title="Views by Category"
                            variant="outline"
                        >
                            <div v-if="analytics.views_by_category.length === 0" class="py-8 text-center text-muted text-sm">No data</div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="item in analytics.views_by_category"
                                    :key="item.category"
                                    class="flex items-center gap-3"
                                >
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm font-medium text-highlighted">{{ item.category }}</span>
                                            <span class="text-sm text-muted">{{ formatNumber(item.views) }}</span>
                                        </div>
                                        <div class="h-1.5 bg-muted/20 rounded-full overflow-hidden">
                                            <div
                                                class="h-full bg-success rounded-full"
                                                :style="{ width: `${(item.views / totalCategoryViews) * 100}%` }"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </UPageCard>
                    </div>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
