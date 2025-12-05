<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '../layouts/DashboardLayout.vue';
import type { DropdownMenuItem } from '@nuxt/ui';

defineProps<{
    stats: {
        users: number;
    };
}>();

const quickActions: DropdownMenuItem[][] = [
    [
        {
            label: 'New Post',
            icon: 'i-lucide-file-plus',
            to: '/cms/posts/create',
        },
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
                    <UPageCard variant="outline">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center size-12 rounded-xl bg-primary/10">
                                <UIcon name="i-lucide-users" class="size-6 text-primary" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted">Total Users</p>
                                <p class="text-2xl font-semibold text-highlighted">{{ stats.users }}</p>
                            </div>
                        </div>
                    </UPageCard>

                    <UPageCard variant="outline">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center size-12 rounded-xl bg-success/10">
                                <UIcon name="i-lucide-file-text" class="size-6 text-success" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted">Published Posts</p>
                                <p class="text-2xl font-semibold text-highlighted">0</p>
                            </div>
                        </div>
                    </UPageCard>

                    <UPageCard variant="outline">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center size-12 rounded-xl bg-warning/10">
                                <UIcon name="i-lucide-clipboard-check" class="size-6 text-warning" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted">In Copydesk</p>
                                <p class="text-2xl font-semibold text-highlighted">0</p>
                            </div>
                        </div>
                    </UPageCard>

                    <UPageCard variant="outline">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center size-12 rounded-xl bg-info/10">
                                <UIcon name="i-lucide-message-square" class="size-6 text-info" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted">Pending Comments</p>
                                <p class="text-2xl font-semibold text-highlighted">0</p>
                            </div>
                        </div>
                    </UPageCard>
                </div>

                <!-- Quick Actions & Recent Activity -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <UPageCard
                        title="Quick Actions"
                        description="Common tasks you can perform"
                        variant="outline"
                    >
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <Link href="/cms/posts/create">
                                <UButton
                                    color="neutral"
                                    variant="soft"
                                    icon="i-lucide-file-plus"
                                    block
                                    class="justify-start"
                                >
                                    New Post
                                </UButton>
                            </Link>
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
                        title="Recent Activity"
                        description="Latest actions on your site"
                        variant="outline"
                    >
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <UIcon name="i-lucide-activity" class="size-12 text-muted mb-3" />
                            <p class="text-muted text-sm">No recent activity</p>
                            <p class="text-dimmed text-xs mt-1">Activity will appear here once you start using the CMS</p>
                        </div>
                    </UPageCard>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
