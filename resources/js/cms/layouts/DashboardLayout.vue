<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { usePermission } from '../composables/usePermission';
import TastyLogo from '../components/TastyLogo.vue';
import type { PageProps } from '../types';
import type { NavigationMenuItem, DropdownMenuItem } from '@nuxt/ui';

const page = usePage<PageProps>();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash);

const { can } = usePermission();

const sidebarOpen = ref(false);
const sidebarCollapsed = ref(false);

// Helper to check if current URL matches
function isActive(path: string, exact = false): boolean {
    if (exact) {
        return page.url === path;
    }
    return page.url.startsWith(path);
}

// Build navigation items based on permissions
const mainNavItems = computed<NavigationMenuItem[]>(() => {
    const items: NavigationMenuItem[] = [
        {
            label: 'Dashboard',
            icon: 'i-lucide-layout-dashboard',
            to: '/cms',
            active: isActive('/cms', true),
            onSelect: () => { sidebarOpen.value = false; },
        },
    ];

    if (can(['posts.view', 'posts.create', 'posts.edit-own'])) {
        items.push({
            label: 'Posts',
            icon: 'i-lucide-file-text',
            to: '/cms/posts',
            active: isActive('/cms/posts'),
            defaultOpen: isActive('/cms/posts'),
            children: [
                {
                    label: 'All Posts',
                    to: '/cms/posts',
                    icon: 'i-lucide-list',
                },
                {
                    label: 'Drafts',
                    to: '/cms/posts/drafts',
                    icon: 'i-lucide-file-edit',
                },
                {
                    label: 'In Copydesk',
                    to: '/cms/posts/copydesk',
                    icon: 'i-lucide-clipboard-check',
                },
                {
                    label: 'Published',
                    to: '/cms/posts/published',
                    icon: 'i-lucide-globe',
                },
                {
                    label: 'Scheduled',
                    to: '/cms/posts/scheduled',
                    icon: 'i-lucide-calendar-clock',
                },
                {
                    label: 'Trashed',
                    to: '/cms/posts/trashed',
                    icon: 'i-lucide-trash-2',
                },
            ],
        });
    }

    if (can('pages.view')) {
        items.push({
            label: 'Pages',
            icon: 'i-lucide-file',
            to: '/cms/pages',
            active: isActive('/cms/pages'),
            onSelect: () => { sidebarOpen.value = false; },
        });
    }

    if (can('media.view')) {
        const mediaChildren: NavigationMenuItem[] = [
            {
                label: 'All Media',
                to: '/cms/media',
                icon: 'i-lucide-folder',
            },
            {
                label: 'Images',
                to: '/cms/media/images',
                icon: 'i-lucide-image',
            },
            {
                label: 'Documents',
                to: '/cms/media/documents',
                icon: 'i-lucide-file-text',
            },
        ];

        if (can('media.upload')) {
            mediaChildren.push({
                label: 'Upload New',
                to: '/cms/media/upload',
                icon: 'i-lucide-upload',
            });
        }

        items.push({
            label: 'Media',
            icon: 'i-lucide-image',
            to: '/cms/media',
            active: isActive('/cms/media'),
            defaultOpen: isActive('/cms/media'),
            children: mediaChildren,
        });
    }

    return items;
});

const taxonomyNavItems = computed<NavigationMenuItem[]>(() => {
    const items: NavigationMenuItem[] = [];
    const hasTaxonomyAccess = can('categories.view') || can('tags.view');

    if (hasTaxonomyAccess) {
        items.push({
            label: 'Taxonomy',
            type: 'label',
        });
    }

    if (can('categories.view')) {
        items.push({
            label: 'Categories',
            icon: 'i-lucide-folder-tree',
            to: '/cms/categories',
            active: isActive('/cms/categories'),
            onSelect: () => { sidebarOpen.value = false; },
        });
    }

    if (can('tags.view')) {
        items.push({
            label: 'Tags',
            icon: 'i-lucide-tags',
            to: '/cms/tags',
            active: isActive('/cms/tags'),
            onSelect: () => { sidebarOpen.value = false; },
        });
    }

    return items;
});

const engagementNavItems = computed<NavigationMenuItem[]>(() => {
    const items: NavigationMenuItem[] = [];

    if (can('comments.view')) {
        items.push({
            label: 'Engagement',
            type: 'label',
        });
        items.push({
            label: 'Comments',
            icon: 'i-lucide-message-square',
            to: '/cms/comments',
            active: isActive('/cms/comments'),
            defaultOpen: isActive('/cms/comments'),
            children: [
                {
                    label: 'All Comments',
                    to: '/cms/comments',
                    icon: 'i-lucide-messages-square',
                },
                {
                    label: 'Pending',
                    to: '/cms/comments/pending',
                    icon: 'i-lucide-clock',
                },
                {
                    label: 'Approved',
                    to: '/cms/comments/approved',
                    icon: 'i-lucide-check-circle',
                },
                {
                    label: 'Spam',
                    to: '/cms/comments/spam',
                    icon: 'i-lucide-shield-alert',
                },
            ],
        });
    }

    return items;
});

const adminNavItems = computed<NavigationMenuItem[]>(() => {
    const items: NavigationMenuItem[] = [];
    const hasAdminAccess = can('users.view') || can('roles.view') || can('analytics.view') || can('settings.view');

    if (hasAdminAccess) {
        items.push({
            label: 'Administration',
            type: 'label',
        });
    }

    if (can('users.view')) {
        const userChildren: NavigationMenuItem[] = [
            {
                label: 'All Users',
                to: '/cms/users',
                icon: 'i-lucide-users',
            },
        ];

        if (can('users.create')) {
            userChildren.push({
                label: 'Add New',
                to: '/cms/users/create',
                icon: 'i-lucide-user-plus',
            });
        }

        if (can('roles.view')) {
            userChildren.push({
                label: 'Roles & Permissions',
                to: '/cms/roles',
                icon: 'i-lucide-shield',
            });
        }

        items.push({
            label: 'Users',
            icon: 'i-lucide-users',
            to: '/cms/users',
            active: isActive('/cms/users') || isActive('/cms/roles'),
            defaultOpen: isActive('/cms/users') || isActive('/cms/roles'),
            children: userChildren,
        });
    }

    if (can('analytics.view')) {
        items.push({
            label: 'Analytics',
            icon: 'i-lucide-bar-chart-3',
            to: '/cms/analytics',
            active: isActive('/cms/analytics'),
            onSelect: () => { sidebarOpen.value = false; },
        });
    }

    if (can('settings.view')) {
        items.push({
            label: 'Settings',
            icon: 'i-lucide-settings',
            to: '/cms/settings',
            active: isActive('/cms/settings'),
            defaultOpen: isActive('/cms/settings'),
            children: [
                {
                    label: 'General',
                    to: '/cms/settings',
                    icon: 'i-lucide-sliders-horizontal',
                },
                {
                    label: 'Writing',
                    to: '/cms/settings/writing',
                    icon: 'i-lucide-pencil',
                },
                {
                    label: 'Reading',
                    to: '/cms/settings/reading',
                    icon: 'i-lucide-book-open',
                },
                {
                    label: 'SEO',
                    to: '/cms/settings/seo',
                    icon: 'i-lucide-search',
                },
                {
                    label: 'Permalinks',
                    to: '/cms/settings/permalinks',
                    icon: 'i-lucide-link',
                },
                {
                    label: 'Integrations',
                    to: '/cms/settings/integrations',
                    icon: 'i-lucide-plug',
                },
            ],
        });
    }

    return items;
});

// Bottom navigation items
const bottomNavItems = computed<NavigationMenuItem[]>(() => [
    {
        label: 'View Website',
        icon: 'i-lucide-external-link',
        to: '/',
        target: '_blank',
    },
]);

// User menu items
const userMenuItems = computed<DropdownMenuItem[][]>(() => [
    [
        {
            type: 'label',
            label: user.value?.name || '',
            avatar: {
                alt: user.value?.name,
            },
        },
    ],
    [
        {
            label: 'Profile',
            icon: 'i-lucide-user',
            to: '/cms/profile',
        },
        {
            label: 'Settings',
            icon: 'i-lucide-settings',
            to: '/cms/settings',
        },
    ],
    [
        {
            label: 'Log out',
            icon: 'i-lucide-log-out',
            onSelect: () => {
                router.post('/cms/logout');
            },
        },
    ],
]);
</script>

<template>
    <UApp>
        <UDashboardGroup unit="rem" storage="local">
            <UDashboardSidebar
                id="cms-sidebar"
                v-model:open="sidebarOpen"
                v-model:collapsed="sidebarCollapsed"
                collapsible
                resizable
                :min-size="14"
                :max-size="20"
                :default-size="16"
                class="bg-elevated/25"
                :ui="{
                    footer: 'lg:border-t lg:border-default',
                }"
            >
                <template #header="{ collapsed }">
                    <a
                        href="/cms"
                        class="flex items-center gap-2.5 hover:opacity-80 transition-opacity"
                        :class="collapsed ? 'justify-center' : ''"
                    >
                        <TastyLogo
                            v-if="!collapsed"
                            class="h-7 w-auto text-highlighted"
                        />
                        <div
                            v-else
                            class="flex items-center justify-center size-8 rounded-lg bg-primary text-primary-foreground shrink-0"
                        >
                            <span class="font-bold text-sm">T</span>
                        </div>
                    </a>
                </template>

                <template #default="{ collapsed }">
                    <!-- Search Button -->
                    <UButton
                        :label="collapsed ? undefined : 'Search...'"
                        icon="i-lucide-search"
                        color="neutral"
                        variant="outline"
                        block
                        :square="collapsed"
                        class="mb-2"
                    >
                        <template v-if="!collapsed" #trailing>
                            <div class="flex items-center gap-0.5 ms-auto">
                                <UKbd value="meta" variant="subtle" />
                                <UKbd value="K" variant="subtle" />
                            </div>
                        </template>
                    </UButton>

                    <!-- Main Navigation -->
                    <UNavigationMenu
                        :collapsed="collapsed"
                        :items="mainNavItems"
                        orientation="vertical"
                        highlight
                        tooltip
                        popover
                    />

                    <!-- Taxonomy Section -->
                    <UNavigationMenu
                        v-if="taxonomyNavItems.length > 0"
                        :collapsed="collapsed"
                        :items="taxonomyNavItems"
                        orientation="vertical"
                        highlight
                        tooltip
                        popover
                        class="mt-4"
                    />

                    <!-- Engagement Section -->
                    <UNavigationMenu
                        v-if="engagementNavItems.length > 0"
                        :collapsed="collapsed"
                        :items="engagementNavItems"
                        orientation="vertical"
                        highlight
                        tooltip
                        popover
                        class="mt-4"
                    />

                    <!-- Administration Section -->
                    <UNavigationMenu
                        v-if="adminNavItems.length > 0"
                        :collapsed="collapsed"
                        :items="adminNavItems"
                        orientation="vertical"
                        highlight
                        tooltip
                        popover
                        class="mt-4"
                    />

                    <!-- Bottom Navigation -->
                    <UNavigationMenu
                        :collapsed="collapsed"
                        :items="bottomNavItems"
                        orientation="vertical"
                        tooltip
                        class="mt-auto"
                    />
                </template>

                <template #footer="{ collapsed }">
                    <UDropdownMenu
                        :items="userMenuItems"
                        :content="{ align: 'center', collisionPadding: 12 }"
                        :ui="{ content: collapsed ? 'w-48' : 'w-(--reka-dropdown-menu-trigger-width)' }"
                    >
                        <UButton
                            v-bind="{
                                avatar: { alt: user?.name },
                                label: collapsed ? undefined : user?.name,
                                trailingIcon: collapsed ? undefined : 'i-lucide-chevrons-up-down',
                            }"
                            color="neutral"
                            variant="ghost"
                            block
                            :square="collapsed"
                            class="data-[state=open]:bg-elevated"
                            :ui="{
                                trailingIcon: 'text-dimmed',
                            }"
                        />
                    </UDropdownMenu>
                </template>
            </UDashboardSidebar>

            <slot />

            <!-- Flash Messages Toast -->
            <Teleport to="body">
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-2"
                >
                    <div
                        v-if="flash?.success || flash?.error"
                        class="fixed bottom-4 right-4 z-50 max-w-sm"
                    >
                        <UAlert
                            v-if="flash.success"
                            color="success"
                            variant="soft"
                            :title="flash.success"
                            icon="i-lucide-check-circle"
                        />
                        <UAlert
                            v-if="flash.error"
                            color="error"
                            variant="soft"
                            :title="flash.error"
                            icon="i-lucide-alert-circle"
                        />
                    </div>
                </Transition>
            </Teleport>
        </UDashboardGroup>
    </UApp>
</template>
