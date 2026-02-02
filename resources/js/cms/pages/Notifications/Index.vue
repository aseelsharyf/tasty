<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import axios from 'axios';
import type { PaginatedResponse } from '../../types';

interface NotificationUser {
    id: number;
    name: string;
    avatar_url: string | null;
}

interface Notification {
    id: number;
    uuid: string;
    type: string;
    title: string;
    body: string | null;
    icon: string;
    color: string;
    action_url: string | null;
    action_label: string | null;
    read_at: string | null;
    triggered_by_user: NotificationUser | null;
    created_at: string;
}

interface NotificationFilters {
    type: string | null;
    filter: string | null;
}

const props = defineProps<{
    notifications: PaginatedResponse<Notification>;
    filters: NotificationFilters;
    unreadCount: number;
}>();

const { cmsPath } = useCmsPath();

const loading = ref(false);
const selectedNotifications = ref<string[]>([]);

// Type options for filtering
const typeOptions = [
    { label: 'All Types', value: '' },
    { label: 'Comments', value: 'comment' },
    { label: 'Comment Resolved', value: 'comment_resolved' },
    { label: 'Submitted for Review', value: 'workflow_submitted' },
    { label: 'Approved', value: 'workflow_approved' },
    { label: 'Rejected', value: 'workflow_rejected' },
    { label: 'Published', value: 'workflow_published' },
    { label: 'Mentions', value: 'mention' },
    { label: 'Assignments', value: 'assignment' },
    { label: 'System', value: 'system' },
];

const filterOptions = [
    { label: 'All', value: '' },
    { label: 'Unread', value: 'unread' },
    { label: 'Read', value: 'read' },
];

// Color mapping for notification types
const colorMap: Record<string, string> = {
    info: 'info',
    success: 'success',
    warning: 'warning',
    error: 'error',
    neutral: 'neutral',
};

function getNotificationColor(color: string): string {
    return colorMap[color] || 'neutral';
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function isUnread(notification: Notification): boolean {
    return notification.read_at === null;
}

// Filter handling
function applyFilters(newFilters: Partial<NotificationFilters>) {
    const params: Record<string, string> = {};

    if (newFilters.type || props.filters.type) {
        params.type = newFilters.type ?? props.filters.type ?? '';
    }
    if (newFilters.filter || props.filters.filter) {
        params.filter = newFilters.filter ?? props.filters.filter ?? '';
    }

    // Remove empty params
    Object.keys(params).forEach(key => {
        if (!params[key]) delete params[key];
    });

    router.get(cmsPath('/notifications'), params, {
        preserveState: true,
        preserveScroll: true,
    });
}

// Mark single notification as read
async function markAsRead(notification: Notification) {
    if (!isUnread(notification)) return;

    try {
        await axios.post(cmsPath(`/notifications/${notification.uuid}/read`));
        router.reload({ only: ['notifications', 'unreadCount'] });
    } catch (error) {
        console.error('Failed to mark as read:', error);
    }
}

// Mark all as read
async function markAllAsRead() {
    loading.value = true;
    try {
        await axios.post(cmsPath('/notifications/mark-all-read'));
        router.reload({ only: ['notifications', 'unreadCount'] });
    } catch (error) {
        console.error('Failed to mark all as read:', error);
    } finally {
        loading.value = false;
    }
}

// Delete notification
async function deleteNotification(notification: Notification) {
    try {
        await axios.delete(cmsPath(`/notifications/${notification.uuid}`));
        router.reload({ only: ['notifications', 'unreadCount'] });
    } catch (error) {
        console.error('Failed to delete notification:', error);
    }
}

// Delete all read notifications
async function deleteReadNotifications() {
    loading.value = true;
    try {
        await axios.delete(cmsPath('/notifications/read'));
        router.reload({ only: ['notifications', 'unreadCount'] });
    } catch (error) {
        console.error('Failed to delete read notifications:', error);
    } finally {
        loading.value = false;
    }
}

// Handle notification click
function handleNotificationClick(notification: Notification) {
    markAsRead(notification);

    if (notification.action_url) {
        router.visit(notification.action_url);
    }
}

// Pagination
function goToPage(page: number) {
    const params: Record<string, string | number> = { page };

    if (props.filters.type) params.type = props.filters.type;
    if (props.filters.filter) params.filter = props.filters.filter;

    router.get(cmsPath('/notifications'), params, {
        preserveState: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Notifications" />

    <DashboardLayout>
        <UDashboardPanel id="notifications">
            <template #header>
                <UDashboardNavbar title="Notifications" :ui="{ right: 'gap-3' }">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <!-- Unread count badge -->
                        <UBadge v-if="unreadCount > 0" color="primary" variant="soft">
                            {{ unreadCount }} unread
                        </UBadge>

                        <UDropdownMenu
                            :items="[
                                [
                                    {
                                        label: 'Mark all as read',
                                        icon: 'i-lucide-check-check',
                                        disabled: unreadCount === 0,
                                        onSelect: markAllAsRead,
                                    },
                                    {
                                        label: 'Delete read notifications',
                                        icon: 'i-lucide-trash-2',
                                        onSelect: deleteReadNotifications,
                                    },
                                ],
                            ]"
                        >
                            <UButton
                                icon="i-lucide-more-vertical"
                                color="neutral"
                                variant="ghost"
                            />
                        </UDropdownMenu>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-3 mb-6">
                    <USelectMenu
                        :model-value="filters.type || ''"
                        :items="typeOptions"
                        value-key="value"
                        placeholder="All Types"
                        class="w-48"
                        @update:model-value="applyFilters({ type: $event })"
                    />

                    <UButtonGroup>
                        <UButton
                            v-for="option in filterOptions"
                            :key="option.value"
                            :color="(filters.filter || '') === option.value ? 'primary' : 'neutral'"
                            :variant="(filters.filter || '') === option.value ? 'soft' : 'ghost'"
                            size="sm"
                            @click="applyFilters({ filter: option.value })"
                        >
                            {{ option.label }}
                        </UButton>
                    </UButtonGroup>
                </div>

                <!-- Notifications List -->
                <div v-if="notifications.data.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                    <UIcon name="i-lucide-bell-off" class="size-16 text-muted mb-4" />
                    <h3 class="text-lg font-medium text-highlighted">No notifications</h3>
                    <p class="text-sm text-muted mt-1">
                        {{ filters.filter === 'unread' ? "You're all caught up!" : 'Nothing to show here.' }}
                    </p>
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="notification in notifications.data"
                        :key="notification.uuid"
                        class="flex items-start gap-4 p-4 rounded-lg border border-default hover:border-primary/50 transition-colors cursor-pointer"
                        :class="{ 'bg-primary/5 border-primary/20': isUnread(notification) }"
                        @click="handleNotificationClick(notification)"
                    >
                        <!-- Icon with unread indicator -->
                        <div class="relative shrink-0">
                            <div
                                :class="[
                                    'flex items-center justify-center size-10 rounded-full',
                                    `bg-${getNotificationColor(notification.color)}/10`
                                ]"
                            >
                                <UIcon
                                    :name="notification.icon"
                                    :class="[
                                        'size-5',
                                        `text-${getNotificationColor(notification.color)}`
                                    ]"
                                />
                            </div>
                            <!-- Unread dot -->
                            <span
                                v-if="isUnread(notification)"
                                class="absolute -top-0.5 -left-0.5 size-3 bg-primary rounded-full ring-2 ring-default"
                            />
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-highlighted">{{ notification.title }}</p>
                                    <p v-if="notification.body" class="text-sm text-muted mt-1">
                                        {{ notification.body }}
                                    </p>
                                </div>

                                <!-- Actions -->
                                <UDropdownMenu
                                    :items="[
                                        [
                                            {
                                                label: isUnread(notification) ? 'Mark as read' : 'Already read',
                                                icon: 'i-lucide-check',
                                                disabled: !isUnread(notification),
                                                onSelect: () => markAsRead(notification),
                                            },
                                        ],
                                        [
                                            {
                                                label: 'Delete',
                                                icon: 'i-lucide-trash-2',
                                                color: 'error',
                                                onSelect: () => deleteNotification(notification),
                                            },
                                        ],
                                    ]"
                                    @click.stop
                                >
                                    <UButton
                                        icon="i-lucide-more-horizontal"
                                        color="neutral"
                                        variant="ghost"
                                        size="xs"
                                        @click.stop
                                    />
                                </UDropdownMenu>
                            </div>

                            <!-- Meta info -->
                            <div class="flex items-center gap-2 mt-2 text-xs text-dimmed">
                                <span>{{ formatDate(notification.created_at) }}</span>
                                <span v-if="notification.triggered_by_user">
                                    by {{ notification.triggered_by_user.name }}
                                </span>
                                <UBadge
                                    v-if="notification.action_label"
                                    :color="getNotificationColor(notification.color)"
                                    variant="subtle"
                                    size="xs"
                                >
                                    {{ notification.action_label }}
                                </UBadge>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="notifications.last_page > 1" class="flex justify-center mt-6">
                    <UPagination
                        :model-value="notifications.current_page"
                        :total="notifications.total"
                        :items-per-page="notifications.per_page"
                        @update:model-value="goToPage"
                    />
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
