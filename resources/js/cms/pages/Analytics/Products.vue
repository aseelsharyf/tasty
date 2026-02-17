<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { ProductAnalytics } from '../../types';

const { cmsPath } = useCmsPath();

const props = defineProps<{
    analytics: ProductAnalytics;
    period: string;
}>();

const periods = [
    { label: '7 days', value: '7d' },
    { label: '30 days', value: '30d' },
    { label: '90 days', value: '90d' },
];

function changePeriod(value: string) {
    router.visit(cmsPath(`/analytics/products?period=${value}`), {
        preserveState: true,
        preserveScroll: true,
    });
}

// Chart helpers for dual bar chart
const overTime = computed(() => props.analytics?.over_time || []);
const chartMaxViews = computed(() => {
    if (!overTime.value.length) return 1;
    return Math.max(...overTime.value.map(d => Math.max(d.views, d.clicks)), 1);
});
const CHART_HEIGHT = 160; // matches h-40 (10rem)
function getBarHeight(count: number): string {
    const px = (count / chartMaxViews.value) * CHART_HEIGHT;
    return `${Math.max(px, 2)}px`;
}

const totalStoreViews = computed(() => {
    return (props.analytics?.by_store || []).reduce((sum, item) => sum + item.views, 0) || 1;
});

const totalCategoryViews = computed(() => {
    return (props.analytics?.by_category || []).reduce((sum, item) => sum + item.views, 0) || 1;
});

function formatNumber(num: number): string {
    if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`;
    if (num >= 1000) return `${(num / 1000).toFixed(1)}K`;
    return num.toString();
}
</script>

<template>
    <Head title="Product Analytics" />

    <DashboardLayout>
        <UDashboardPanel id="analytics-products">
            <template #header>
                <UDashboardNavbar title="Product Analytics">
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
                    <div class="grid grid-cols-3 gap-4">
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-highlighted">{{ formatNumber(analytics.summary.views) }}</p>
                                <p class="text-xs text-muted">Total Views</p>
                            </div>
                        </UPageCard>
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-primary">{{ formatNumber(analytics.summary.clicks) }}</p>
                                <p class="text-xs text-muted">Total Clicks</p>
                            </div>
                        </UPageCard>
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold" :class="{
                                    'text-success': analytics.summary.ctr >= 5,
                                    'text-warning': analytics.summary.ctr >= 2 && analytics.summary.ctr < 5,
                                    'text-muted': analytics.summary.ctr < 2,
                                }">{{ analytics.summary.ctr }}%</p>
                                <p class="text-xs text-muted">Click-Through Rate</p>
                            </div>
                        </UPageCard>
                    </div>

                    <!-- Views & Clicks Over Time (Dual Chart) -->
                    <UPageCard
                        title="Views & Clicks Over Time"
                        variant="outline"
                    >
                        <div v-if="overTime.length === 0" class="h-40 flex items-center justify-center">
                            <p class="text-muted text-sm">No data available</p>
                        </div>
                        <template v-else>
                            <!-- Legend -->
                            <div class="flex items-center gap-4 mb-4">
                                <div class="flex items-center gap-1.5">
                                    <div class="size-3 rounded bg-primary" />
                                    <span class="text-xs text-muted">Views</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="size-3 rounded bg-success" />
                                    <span class="text-xs text-muted">Clicks</span>
                                </div>
                            </div>
                            <div class="h-40 flex items-end overflow-hidden" :class="overTime.length > 31 ? 'gap-px' : 'gap-0.5'">
                                <div
                                    v-for="day in overTime"
                                    :key="day.date"
                                    class="flex-1 min-w-0 flex items-end gap-px group cursor-pointer"
                                    :title="`${day.date}: ${day.views} views, ${day.clicks} clicks`"
                                >
                                    <div
                                        class="flex-1 min-w-0 bg-primary/30 group-hover:bg-primary rounded-t transition-all"
                                        :class="{ 'bg-primary': day.views > 0 }"
                                        :style="{ height: getBarHeight(day.views) }"
                                    />
                                    <div
                                        class="flex-1 min-w-0 bg-success/30 group-hover:bg-success rounded-t transition-all"
                                        :class="{ 'bg-success': day.clicks > 0 }"
                                        :style="{ height: getBarHeight(day.clicks) }"
                                    />
                                </div>
                            </div>
                            <div class="flex justify-between mt-2 text-xs text-muted">
                                <span>{{ overTime[0]?.date.slice(5) }}</span>
                                <span>{{ overTime[overTime.length - 1]?.date.slice(5) }}</span>
                            </div>
                        </template>
                    </UPageCard>

                    <!-- Top Products Tables -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                        <!-- Top by Views -->
                        <UPageCard
                            title="Top Products by Views"
                            variant="outline"
                        >
                            <div v-if="analytics.top_by_views.length === 0" class="py-8 text-center text-muted text-sm">No views recorded</div>
                            <div v-else class="divide-y divide-default -mx-6">
                                <div
                                    v-for="(product, index) in analytics.top_by_views"
                                    :key="product.id"
                                    class="flex items-center gap-3 px-6 py-2.5"
                                >
                                    <span class="text-xs text-muted w-4 text-right">{{ index + 1 }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-highlighted truncate">{{ product.title }}</p>
                                        <p class="text-xs text-muted">
                                            {{ product.store || 'No store' }}
                                            <span v-if="product.category"> &middot; {{ product.category }}</span>
                                        </p>
                                    </div>
                                    <span class="text-sm font-semibold text-highlighted">{{ formatNumber(product.views) }}</span>
                                </div>
                            </div>
                        </UPageCard>

                        <!-- Top by Clicks -->
                        <UPageCard
                            title="Top Products by Clicks"
                            variant="outline"
                        >
                            <div v-if="analytics.top_by_clicks.length === 0" class="py-8 text-center text-muted text-sm">No clicks recorded</div>
                            <div v-else class="divide-y divide-default -mx-6">
                                <div
                                    v-for="(product, index) in analytics.top_by_clicks"
                                    :key="product.id"
                                    class="flex items-center gap-3 px-6 py-2.5"
                                >
                                    <span class="text-xs text-muted w-4 text-right">{{ index + 1 }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-highlighted truncate">{{ product.title }}</p>
                                        <p class="text-xs text-muted">
                                            {{ product.store || 'No store' }}
                                            <span v-if="product.category"> &middot; {{ product.category }}</span>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-semibold text-highlighted">{{ formatNumber(product.clicks) }}</span>
                                        <p class="text-xs text-muted">{{ product.ctr }}% CTR</p>
                                    </div>
                                </div>
                            </div>
                        </UPageCard>
                    </div>

                    <!-- Store & Category Breakdowns -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                        <UPageCard
                            title="Views by Store"
                            variant="outline"
                        >
                            <div v-if="analytics.by_store.length === 0" class="py-8 text-center text-muted text-sm">No data</div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="item in analytics.by_store"
                                    :key="item.store"
                                    class="flex items-center gap-3"
                                >
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm font-medium text-highlighted">{{ item.store }}</span>
                                            <span class="text-sm text-muted">{{ formatNumber(item.views) }}</span>
                                        </div>
                                        <div class="h-1.5 bg-muted/20 rounded-full overflow-hidden">
                                            <div
                                                class="h-full bg-primary rounded-full"
                                                :style="{ width: `${(item.views / totalStoreViews) * 100}%` }"
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
                            <div v-if="analytics.by_category.length === 0" class="py-8 text-center text-muted text-sm">No data</div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="item in analytics.by_category"
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
