<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { AuthorAnalytics } from '../../types';

const { cmsPath } = useCmsPath();

const props = defineProps<{
    analytics: AuthorAnalytics;
    period: string;
}>();

const periods = [
    { label: '7 days', value: '7d' },
    { label: '30 days', value: '30d' },
    { label: '90 days', value: '90d' },
];

function changePeriod(value: string) {
    router.visit(cmsPath(`/analytics/authors?period=${value}`), {
        preserveState: true,
        preserveScroll: true,
    });
}

// Chart helpers
const publishingTrend = computed(() => props.analytics?.publishing_trend || []);
const chartMax = computed(() => {
    if (!publishingTrend.value.length) return 1;
    return Math.max(...publishingTrend.value.map(d => d.count), 1);
});
const CHART_HEIGHT = 160; // matches h-40 (10rem)
function getBarHeight(count: number): string {
    const px = (count / chartMax.value) * CHART_HEIGHT;
    return `${Math.max(px, 2)}px`;
}

function formatNumber(num: number): string {
    if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`;
    if (num >= 1000) return `${(num / 1000).toFixed(1)}K`;
    return num.toString();
}

// Top by views vs top by volume
const topByViews = computed(() => {
    return [...(props.analytics?.leaderboard || [])]
        .sort((a, b) => b.total_views - a.total_views)
        .slice(0, 5);
});

const topByVolume = computed(() => {
    return [...(props.analytics?.leaderboard || [])]
        .sort((a, b) => b.published_count - a.published_count)
        .slice(0, 5);
});
</script>

<template>
    <Head title="Author Analytics" />

    <DashboardLayout>
        <UDashboardPanel id="analytics-authors">
            <template #header>
                <UDashboardNavbar title="Author Analytics">
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
                    <!-- Author Leaderboard -->
                    <UPageCard
                        title="Author Leaderboard"
                        description="Authors ranked by published articles and views"
                        variant="outline"
                    >
                        <div v-if="analytics.leaderboard.length === 0" class="flex flex-col items-center py-8">
                            <UIcon name="i-lucide-users" class="size-12 text-muted mb-3" />
                            <p class="text-muted">No published articles in this period</p>
                        </div>

                        <div v-else class="overflow-x-auto -mx-6">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-default">
                                        <th class="text-left px-6 py-2 text-muted font-medium">#</th>
                                        <th class="text-left px-6 py-2 text-muted font-medium">Author</th>
                                        <th class="text-right px-6 py-2 text-muted font-medium">Published</th>
                                        <th class="text-right px-6 py-2 text-muted font-medium">Views</th>
                                        <th class="text-right px-6 py-2 text-muted font-medium">Avg Views</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(author, index) in analytics.leaderboard"
                                        :key="author.user.id"
                                        class="border-b border-default/50 hover:bg-elevated/50"
                                    >
                                        <td class="px-6 py-3">
                                            <div class="flex items-center justify-center size-6 rounded-full" :class="{
                                                'bg-warning/20 text-warning': index === 0,
                                                'bg-muted/30 text-muted': index === 1,
                                                'bg-warning/10 text-warning/70': index === 2,
                                                'bg-muted/10 text-muted': index > 2,
                                            }">
                                                <UIcon v-if="index < 3" name="i-lucide-trophy" class="size-3" />
                                                <span v-else class="text-xs">{{ index + 1 }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-3">
                                            <div class="flex items-center gap-3">
                                                <UAvatar
                                                    v-if="author.user.avatar_url"
                                                    :src="author.user.avatar_url"
                                                    :alt="author.user.name"
                                                    size="sm"
                                                />
                                                <div v-else class="flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary text-xs font-medium">
                                                    {{ author.user.name.charAt(0).toUpperCase() }}
                                                </div>
                                                <span class="font-medium text-highlighted">{{ author.user.name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-3 text-right font-medium">{{ author.published_count }}</td>
                                        <td class="px-6 py-3 text-right font-medium">{{ formatNumber(author.total_views) }}</td>
                                        <td class="px-6 py-3 text-right text-muted">{{ formatNumber(author.avg_views) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </UPageCard>

                    <!-- Top by Views vs Top by Volume -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                        <UPageCard
                            title="Top by Views"
                            description="Most read authors"
                            variant="outline"
                        >
                            <div v-if="topByViews.length === 0" class="py-6 text-center text-muted text-sm">No data</div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="author in topByViews"
                                    :key="author.user.id"
                                    class="flex items-center gap-3"
                                >
                                    <UAvatar
                                        v-if="author.user.avatar_url"
                                        :src="author.user.avatar_url"
                                        :alt="author.user.name"
                                        size="xs"
                                    />
                                    <div v-else class="flex items-center justify-center size-6 rounded-full bg-primary/10 text-primary text-xs font-medium">
                                        {{ author.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="flex-1 truncate text-sm text-highlighted">{{ author.user.name }}</span>
                                    <span class="text-sm font-medium text-primary">{{ formatNumber(author.total_views) }}</span>
                                </div>
                            </div>
                        </UPageCard>

                        <UPageCard
                            title="Top by Volume"
                            description="Most prolific authors"
                            variant="outline"
                        >
                            <div v-if="topByVolume.length === 0" class="py-6 text-center text-muted text-sm">No data</div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="author in topByVolume"
                                    :key="author.user.id"
                                    class="flex items-center gap-3"
                                >
                                    <UAvatar
                                        v-if="author.user.avatar_url"
                                        :src="author.user.avatar_url"
                                        :alt="author.user.name"
                                        size="xs"
                                    />
                                    <div v-else class="flex items-center justify-center size-6 rounded-full bg-primary/10 text-primary text-xs font-medium">
                                        {{ author.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="flex-1 truncate text-sm text-highlighted">{{ author.user.name }}</span>
                                    <span class="text-sm font-medium text-success">{{ author.published_count }} posts</span>
                                </div>
                            </div>
                        </UPageCard>
                    </div>

                    <!-- Publishing Trend Chart -->
                    <UPageCard
                        title="Publishing Trend"
                        :description="`Posts published per day in the last ${period === '7d' ? '7' : period === '90d' ? '90' : '30'} days`"
                        variant="outline"
                    >
                        <div v-if="publishingTrend.length === 0" class="h-40 flex items-center justify-center">
                            <p class="text-muted text-sm">No data available</p>
                        </div>
                        <template v-else>
                            <div class="h-40 flex items-end overflow-hidden" :class="publishingTrend.length > 31 ? 'gap-px' : 'gap-0.5'">
                                <div
                                    v-for="day in publishingTrend"
                                    :key="day.date"
                                    class="flex-1 min-w-0 group cursor-pointer"
                                    :title="`${day.date}: ${day.count} posts`"
                                >
                                    <div
                                        class="w-full bg-success/30 group-hover:bg-success rounded-t transition-all"
                                        :class="{ 'bg-success': day.count > 0 }"
                                        :style="{ height: getBarHeight(day.count) }"
                                    />
                                </div>
                            </div>
                            <div class="flex justify-between mt-2 text-xs text-muted">
                                <span>{{ publishingTrend[0]?.date.slice(5) }}</span>
                                <span>{{ publishingTrend[publishingTrend.length - 1]?.date.slice(5) }}</span>
                            </div>
                        </template>
                    </UPageCard>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
