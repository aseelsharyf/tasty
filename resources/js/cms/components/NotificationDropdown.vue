<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useCmsPath } from '../composables/useCmsPath';

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
    is_read: boolean;
    triggered_by: NotificationUser | null;
    created_at: string;
    time_ago: string;
}

const { cmsPath } = useCmsPath();

const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const loading = ref(false);
const dropdownOpen = ref(false);
let pollInterval: ReturnType<typeof setInterval> | null = null;

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

async function fetchRecentNotifications() {
    loading.value = true;
    try {
        const response = await axios.get(cmsPath('/notifications/recent'));
        notifications.value = response.data.notifications;
        unreadCount.value = response.data.unread_count;
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    } finally {
        loading.value = false;
    }
}

async function fetchUnreadCount() {
    try {
        const response = await axios.get(cmsPath('/notifications/unread-count'));
        unreadCount.value = response.data.count;
    } catch (error) {
        console.error('Failed to fetch unread count:', error);
    }
}

async function markAsRead(notification: Notification) {
    if (notification.is_read) return;

    try {
        await axios.post(cmsPath(`/notifications/${notification.uuid}/read`));
        notification.is_read = true;
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
}

async function markAllAsRead() {
    try {
        await axios.post(cmsPath('/notifications/mark-all-read'));
        notifications.value.forEach(n => n.is_read = true);
        unreadCount.value = 0;
    } catch (error) {
        console.error('Failed to mark all as read:', error);
    }
}

function handleNotificationClick(notification: Notification) {
    markAsRead(notification);
    dropdownOpen.value = false;

    if (notification.action_url) {
        router.visit(notification.action_url);
    }
}

function viewAllNotifications() {
    dropdownOpen.value = false;
    router.visit(cmsPath('/notifications'));
}

// Start polling when component is mounted
onMounted(() => {
    fetchRecentNotifications();

    // Poll for new notifications every 30 seconds
    pollInterval = setInterval(() => {
        if (!dropdownOpen.value) {
            fetchUnreadCount();
        }
    }, 30000);
});

// Clean up polling on unmount
onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});

// Refresh notifications when dropdown opens
function onDropdownOpen() {
    dropdownOpen.value = true;
    fetchRecentNotifications();
}

function onDropdownClose() {
    dropdownOpen.value = false;
}
</script>

<template>
    <UPopover
        :ui="{
            content: 'w-80 sm:w-96 p-0',
        }"
        @update:open="(open: boolean) => open ? onDropdownOpen() : onDropdownClose()"
    >
        <UButton
            icon="i-lucide-bell"
            color="neutral"
            variant="ghost"
            class="relative"
        >
            <!-- Unread badge -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-4 h-4 px-1 text-[10px] font-medium text-white bg-error rounded-full"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </UButton>

        <template #content>
            <div class="flex flex-col max-h-[28rem]">
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-default">
                    <h3 class="font-semibold text-highlighted">Notifications</h3>
                    <UButton
                        v-if="unreadCount > 0"
                        variant="link"
                        size="xs"
                        color="primary"
                        @click="markAllAsRead"
                    >
                        Mark all read
                    </UButton>
                </div>

                <!-- Notifications List -->
                <div class="flex-1 overflow-y-auto">
                    <div v-if="loading && notifications.length === 0" class="flex items-center justify-center py-8">
                        <UIcon name="i-lucide-loader-2" class="size-5 animate-spin text-muted" />
                    </div>

                    <div v-else-if="notifications.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
                        <UIcon name="i-lucide-bell-off" class="size-10 text-muted mb-2" />
                        <p class="text-sm text-muted">No notifications</p>
                        <p class="text-xs text-dimmed mt-1">You're all caught up!</p>
                    </div>

                    <div v-else class="divide-y divide-default">
                        <button
                            v-for="notification in notifications"
                            :key="notification.uuid"
                            class="w-full flex items-start gap-3 px-4 py-3 text-left hover:bg-elevated/50 transition-colors"
                            :class="{ 'bg-primary/5': !notification.is_read }"
                            @click="handleNotificationClick(notification)"
                        >
                            <!-- Unread indicator -->
                            <div class="relative shrink-0 mt-1">
                                <div
                                    :class="[
                                        'flex items-center justify-center size-8 rounded-full',
                                        `bg-${getNotificationColor(notification.color)}/10`
                                    ]"
                                >
                                    <UIcon
                                        :name="notification.icon"
                                        :class="[
                                            'size-4',
                                            `text-${getNotificationColor(notification.color)}`
                                        ]"
                                    />
                                </div>
                                <!-- Blue dot for unread -->
                                <span
                                    v-if="!notification.is_read"
                                    class="absolute -top-0.5 -left-0.5 size-2.5 bg-primary rounded-full ring-2 ring-default"
                                />
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-highlighted line-clamp-1">
                                    {{ notification.title }}
                                </p>
                                <p v-if="notification.body" class="text-xs text-muted line-clamp-2 mt-0.5">
                                    {{ notification.body }}
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-dimmed">{{ notification.time_ago }}</span>
                                    <span v-if="notification.triggered_by" class="text-xs text-dimmed">
                                        by {{ notification.triggered_by.name }}
                                    </span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-default p-2">
                    <UButton
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        block
                        trailing-icon="i-lucide-arrow-right"
                        @click="viewAllNotifications"
                    >
                        View all notifications
                    </UButton>
                </div>
            </div>
        </template>
    </UPopover>
</template>
