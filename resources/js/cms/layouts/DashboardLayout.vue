<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { usePermission } from '../composables/usePermission';
import { useSidebar } from '../composables/useSidebar';
import TastyLogo from '../components/TastyLogo.vue';
import type { PageProps } from '../types';
import type { NavigationMenuItem, DropdownMenuItem, CommandPaletteItem, CommandPaletteGroup } from '@nuxt/ui';

interface Language {
    id: number;
    code: string;
    name: string;
    native_name: string;
    direction: 'ltr' | 'rtl';
    is_default: boolean;
}

const page = usePage<PageProps>();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash);

// Toast visibility with auto-dismiss
const showToast = ref(false);
let toastTimeout: ReturnType<typeof setTimeout> | null = null;

watch(flash, (newFlash) => {
    if (newFlash?.success || newFlash?.error) {
        showToast.value = true;

        // Clear any existing timeout
        if (toastTimeout) {
            clearTimeout(toastTimeout);
        }

        // Auto-dismiss after 4 seconds
        toastTimeout = setTimeout(() => {
            showToast.value = false;
        }, 4000);
    }
}, { immediate: true });

function dismissToast() {
    showToast.value = false;
    if (toastTimeout) {
        clearTimeout(toastTimeout);
    }
}

const { can } = usePermission();

// Languages for navigation
const languages = ref<Language[]>([]);

async function fetchLanguages() {
    try {
        const response = await fetch('/cms/languages');
        languages.value = await response.json();
    } catch (error) {
        console.error('Failed to fetch languages:', error);
    }
}

onMounted(() => {
    fetchLanguages();
});

const sidebarOpen = ref(false);
const sidebarCollapsed = ref(false);

// Use shared sidebar state
const { isHidden: sidebarHidden } = useSidebar();

// Helper to check if current URL matches
function isActive(path: string, exact = false): boolean {
    // Parse the path and current URL to handle query parameters
    const [pathBase, pathQuery] = path.split('?');
    const [urlBase, urlQuery] = page.url.split('?');

    if (exact) {
        // For exact match, compare both base and query string
        return page.url === path;
    }

    // If path has query params, check if current URL has same base and query params
    if (pathQuery) {
        return urlBase === pathBase && urlQuery === pathQuery;
    }

    // For paths without query params, match base path exactly (not startsWith)
    // to avoid /cms/posts matching /cms/posts?status=draft
    return urlBase === pathBase;
}

// Check if URL starts with path (for parent menu items)
function isActivePrefix(path: string): boolean {
    const [urlBase] = page.url.split('?');
    return urlBase.startsWith(path);
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
        {
            label: 'Targets',
            icon: 'i-lucide-target',
            to: '/cms/targets',
            active: isActivePrefix('/cms/targets'),
            onSelect: () => { sidebarOpen.value = false; },
        },
    ];

    if (can(['posts.view', 'posts.create', 'posts.edit-own'])) {
        // Generate language-specific post navigation
        const postChildren: NavigationMenuItem[] = languages.value.map((lang) => ({
            label: lang.name,
            to: `/cms/posts/${lang.code}`,
            icon: lang.direction === 'rtl' ? 'i-lucide-align-right' : 'i-lucide-align-left',
            active: isActivePrefix(`/cms/posts/${lang.code}`),
        }));

        // Fallback if languages haven't loaded yet
        if (postChildren.length === 0) {
            postChildren.push(
                { label: 'English', to: '/cms/posts/en', icon: 'i-lucide-align-left', active: isActivePrefix('/cms/posts/en') },
                { label: 'Dhivehi', to: '/cms/posts/dv', icon: 'i-lucide-align-right', active: isActivePrefix('/cms/posts/dv') },
            );
        }

        items.push({
            label: 'Posts',
            icon: 'i-lucide-file-text',
            to: `/cms/posts/${languages.value.find(l => l.is_default)?.code || 'en'}`,
            active: isActivePrefix('/cms/posts'),
            open: isActivePrefix('/cms/posts'),
            children: postChildren,
        });
    }

    if (can('media.view')) {
        const mediaChildren: NavigationMenuItem[] = [
            {
                label: 'All Media',
                to: '/cms/media',
                icon: 'i-lucide-grid-3x3',
                active: isActive('/cms/media', true) || (isActive('/cms/media') && !page.url.includes('type=')),
            },
            {
                label: 'Images',
                to: '/cms/media?type=images',
                icon: 'i-lucide-image',
                active: page.url.includes('type=images'),
            },
            {
                label: 'Videos',
                to: '/cms/media?type=videos',
                icon: 'i-lucide-video',
                active: page.url.includes('type=videos'),
            },
        ];

        items.push({
            label: 'Media',
            icon: 'i-lucide-image',
            to: '/cms/media',
            active: isActivePrefix('/cms/media'),
            open: isActivePrefix('/cms/media'),
            children: mediaChildren,
        });
    }

    return items;
});

const taxonomyNavItems = computed<NavigationMenuItem[]>(() => {
    const items: NavigationMenuItem[] = [];
    const hasTaxonomyAccess = can('categories.view') || can('tags.view') || can('sponsors.view');

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

    if (can('sponsors.view')) {
        items.push({
            label: 'Sponsors',
            icon: 'i-lucide-handshake',
            to: '/cms/sponsors',
            active: isActive('/cms/sponsors'),
            onSelect: () => { sidebarOpen.value = false; },
        });
    }

    return items;
});

const shopNavItems = computed<NavigationMenuItem[]>(() => {
    const items: NavigationMenuItem[] = [];
    const hasShopAccess = can('products.view') || can('product-categories.view');

    if (hasShopAccess) {
        items.push({
            label: 'Shop',
            type: 'label',
        });
    }

    if (can('products.view')) {
        items.push({
            label: 'Products',
            icon: 'i-lucide-package',
            to: '/cms/products',
            active: isActivePrefix('/cms/products'),
            onSelect: () => { sidebarOpen.value = false; },
        });
    }

    if (can('product-categories.view')) {
        items.push({
            label: 'Categories',
            icon: 'i-lucide-boxes',
            to: '/cms/product-categories',
            active: isActivePrefix('/cms/product-categories'),
            onSelect: () => { sidebarOpen.value = false; },
        });
    }

    return items;
});

const layoutNavItems = computed<NavigationMenuItem[]>(() => {
    const items: NavigationMenuItem[] = [];
    const hasLayoutAccess = can('menus.view') || can('pages.view') || can('settings.view');

    if (hasLayoutAccess) {
        items.push({
            label: 'Content',
            type: 'label',
        });
    }

    // Homepage (top-level)
    if (can('settings.view')) {
        items.push({
            label: 'Homepage',
            icon: 'i-lucide-home',
            to: '/cms/layouts/homepage',
            active: isActivePrefix('/cms/layouts/homepage'),
        });
    }

    // Layouts section
    if (can('settings.view')) {
        items.push({
            label: 'Layouts',
            icon: 'i-lucide-layout-template',
            to: '/cms/layouts',
            active: isActive('/cms/layouts', true) || isActivePrefix('/cms/layouts/categories') || isActivePrefix('/cms/layouts/tags'),
            open: isActivePrefix('/cms/layouts/categories') || isActivePrefix('/cms/layouts/tags'),
            children: [
                {
                    label: 'Categories & Tags',
                    to: '/cms/layouts',
                    icon: 'i-lucide-folder-tree',
                    active: isActive('/cms/layouts', true) || isActivePrefix('/cms/layouts/categories') || isActivePrefix('/cms/layouts/tags'),
                },
            ],
        });
    }

    if (can('pages.view')) {
        // Generate language-specific page navigation
        const pageChildren: NavigationMenuItem[] = languages.value.map((lang) => ({
            label: lang.name,
            to: `/cms/pages/${lang.code}`,
            icon: lang.direction === 'rtl' ? 'i-lucide-align-right' : 'i-lucide-align-left',
            active: isActivePrefix(`/cms/pages/${lang.code}`),
        }));

        // Fallback if languages haven't loaded yet
        if (pageChildren.length === 0) {
            pageChildren.push(
                { label: 'English', to: '/cms/pages/en', icon: 'i-lucide-align-left', active: isActivePrefix('/cms/pages/en') },
                { label: 'Dhivehi', to: '/cms/pages/dv', icon: 'i-lucide-align-right', active: isActivePrefix('/cms/pages/dv') },
            );
        }

        items.push({
            label: 'Pages',
            icon: 'i-lucide-file-text',
            to: `/cms/pages/${languages.value.find(l => l.is_default)?.code || 'en'}`,
            active: isActivePrefix('/cms/pages'),
            open: isActivePrefix('/cms/pages'),
            children: pageChildren,
        });
    }

    if (can('menus.view')) {
        items.push({
            label: 'Menus',
            icon: 'i-lucide-menu',
            to: '/cms/menus',
            active: isActivePrefix('/cms/menus'),
            onSelect: () => { sidebarOpen.value = false; },
        });
    }

    return items;
});

const engagementNavItems = computed<NavigationMenuItem[]>(() => {
    const items: NavigationMenuItem[] = [];
    const hasEngagementAccess = can('comments.view') || can('subscribers.view');

    if (hasEngagementAccess) {
        items.push({
            label: 'Engagement',
            type: 'label',
        });
    }

    if (can('comments.view')) {
        items.push({
            label: 'Comments',
            icon: 'i-lucide-message-square',
            to: '/cms/comments',
            active: isActivePrefix('/cms/comments'),
            open: isActivePrefix('/cms/comments'),
            children: [
                {
                    label: 'All Comments',
                    to: '/cms/comments',
                    icon: 'i-lucide-messages-square',
                    active: isActive('/cms/comments', true) || (isActive('/cms/comments') && !page.url.includes('status=')),
                },
                {
                    label: 'Moderation Queue',
                    to: '/cms/comments/queue',
                    icon: 'i-lucide-inbox',
                    active: isActivePrefix('/cms/comments/queue'),
                },
                {
                    label: 'Approved',
                    to: '/cms/comments?status=approved',
                    icon: 'i-lucide-check-circle',
                    active: page.url.includes('status=approved'),
                },
                {
                    label: 'Spam',
                    to: '/cms/comments?status=spam',
                    icon: 'i-lucide-shield-alert',
                    active: page.url.includes('status=spam'),
                },
            ],
        });
    }

    if (can('subscribers.view')) {
        items.push({
            label: 'Subscribers',
            icon: 'i-lucide-mail',
            to: '/cms/subscribers',
            active: isActivePrefix('/cms/subscribers'),
            onSelect: () => { sidebarOpen.value = false; },
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
            active: isActivePrefix('/cms/users') || isActivePrefix('/cms/roles'),
            open: isActivePrefix('/cms/users') || isActivePrefix('/cms/roles'),
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
            active: isActivePrefix('/cms/settings'),
            open: isActivePrefix('/cms/settings'),
            children: [
                {
                    label: 'General',
                    to: '/cms/settings',
                    icon: 'i-lucide-sliders-horizontal',
                },
                {
                    label: 'Languages',
                    to: '/cms/settings/languages',
                    icon: 'i-lucide-languages',
                },
                {
                    label: 'Post Types',
                    to: '/cms/settings/post-types',
                    icon: 'i-lucide-file-cog',
                },
                {
                    label: 'Media',
                    to: '/cms/settings/media',
                    icon: 'i-lucide-crop',
                },
                {
                    label: 'Section Categories',
                    to: '/cms/settings/section-categories',
                    icon: 'i-lucide-layout-grid',
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

// Search functionality
const searchOpen = ref(false);
const searchTerm = ref('');

// Build search groups from navigation
const searchGroups = computed<CommandPaletteGroup<CommandPaletteItem>[]>(() => {
    const groups: CommandPaletteGroup<CommandPaletteItem>[] = [];

    // Navigation group
    const navItems: CommandPaletteItem[] = [];

    // Add main nav items
    for (const item of mainNavItems.value) {
        if (item.to) {
            navItems.push({
                label: item.label || '',
                icon: item.icon as string,
                to: item.to as string,
                onSelect: () => {
                    router.visit(item.to as string);
                    searchOpen.value = false;
                },
            });
        }
        if (item.children) {
            for (const child of item.children) {
                navItems.push({
                    label: child.label || '',
                    suffix: item.label,
                    icon: child.icon as string,
                    to: child.to as string,
                    onSelect: () => {
                        router.visit(child.to as string);
                        searchOpen.value = false;
                    },
                });
            }
        }
    }

    // Add taxonomy nav items
    for (const item of taxonomyNavItems.value) {
        if (item.type === 'label') continue;
        if (item.to) {
            navItems.push({
                label: item.label || '',
                icon: item.icon as string,
                to: item.to as string,
                onSelect: () => {
                    router.visit(item.to as string);
                    searchOpen.value = false;
                },
            });
        }
    }

    // Add shop nav items
    for (const item of shopNavItems.value) {
        if (item.type === 'label') continue;
        if (item.to) {
            navItems.push({
                label: item.label || '',
                icon: item.icon as string,
                to: item.to as string,
                onSelect: () => {
                    router.visit(item.to as string);
                    searchOpen.value = false;
                },
            });
        }
    }

    // Add layout nav items (includes Homepage)
    for (const item of layoutNavItems.value) {
        if (item.type === 'label') continue;
        if (item.to) {
            navItems.push({
                label: item.label || '',
                icon: item.icon as string,
                to: item.to as string,
                onSelect: () => {
                    router.visit(item.to as string);
                    searchOpen.value = false;
                },
            });
        }
    }

    // Add engagement nav items
    for (const item of engagementNavItems.value) {
        if (item.type === 'label') continue;
        if (item.to) {
            navItems.push({
                label: item.label || '',
                icon: item.icon as string,
                to: item.to as string,
                onSelect: () => {
                    router.visit(item.to as string);
                    searchOpen.value = false;
                },
            });
        }
        if (item.children) {
            for (const child of item.children) {
                navItems.push({
                    label: child.label || '',
                    suffix: item.label,
                    icon: child.icon as string,
                    to: child.to as string,
                    onSelect: () => {
                        router.visit(child.to as string);
                        searchOpen.value = false;
                    },
                });
            }
        }
    }

    // Add admin nav items
    for (const item of adminNavItems.value) {
        if (item.type === 'label') continue;
        if (item.to) {
            navItems.push({
                label: item.label || '',
                icon: item.icon as string,
                to: item.to as string,
                onSelect: () => {
                    router.visit(item.to as string);
                    searchOpen.value = false;
                },
            });
        }
        if (item.children) {
            for (const child of item.children) {
                navItems.push({
                    label: child.label || '',
                    suffix: item.label,
                    icon: child.icon as string,
                    to: child.to as string,
                    onSelect: () => {
                        router.visit(child.to as string);
                        searchOpen.value = false;
                    },
                });
            }
        }
    }

    if (navItems.length > 0) {
        groups.push({
            id: 'navigation',
            label: 'Navigation',
            items: navItems,
        });
    }

    return groups;
});

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
            <div :class="sidebarHidden ? 'hidden' : 'contents'">
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
                            :class="collapsed ? 'h-5 w-auto' : 'h-7 w-auto'"
                            class="text-highlighted"
                        />
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
                        @click="searchOpen = true"
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

                    <!-- Shop Section -->
                    <UNavigationMenu
                        v-if="shopNavItems.length > 0"
                        :collapsed="collapsed"
                        :items="shopNavItems"
                        orientation="vertical"
                        highlight
                        tooltip
                        popover
                        class="mt-4"
                    />

                    <!-- Content Section (includes Homepage, Pages, Menus) -->
                    <UNavigationMenu
                        v-if="layoutNavItems.length > 0"
                        :collapsed="collapsed"
                        :items="layoutNavItems"
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
                    <div class="flex items-center gap-2" :class="collapsed ? 'flex-col' : ''">
                        <!-- User Dropdown -->
                        <UDropdownMenu
                            :items="userMenuItems"
                            :content="{ align: 'center', collisionPadding: 12 }"
                            :ui="{ content: collapsed ? 'w-48' : 'w-48' }"
                            class="flex-1"
                        >
                            <UButton
                                v-bind="{
                                    avatar: { alt: user?.name },
                                    label: collapsed ? undefined : user?.name,
                                    trailingIcon: collapsed ? undefined : 'i-lucide-chevrons-up-down',
                                }"
                                color="neutral"
                                variant="ghost"
                                :block="!collapsed"
                                :square="collapsed"
                                class="data-[state=open]:bg-elevated"
                                :ui="{
                                    trailingIcon: 'text-dimmed',
                                }"
                            />
                        </UDropdownMenu>
                    </div>
                </template>
            </UDashboardSidebar>
            </div>

            <slot />

            <!-- Dashboard Search (Command Palette) -->
            <UDashboardSearch
                v-model:open="searchOpen"
                v-model:search-term="searchTerm"
                :groups="searchGroups"
                placeholder="Search navigation..."
            />

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
                        v-if="showToast && (flash?.success || flash?.error)"
                        class="fixed bottom-4 right-4 z-[100] max-w-sm shadow-xl rounded-lg"
                    >
                        <UAlert
                            v-if="flash.success"
                            color="success"
                            variant="solid"
                            :title="flash.success"
                            icon="i-lucide-check-circle"
                            :close-button="{ icon: 'i-lucide-x', color: 'white', variant: 'link' }"
                            @close="dismissToast"
                        />
                        <UAlert
                            v-if="flash.error"
                            color="error"
                            variant="solid"
                            :title="flash.error"
                            icon="i-lucide-alert-circle"
                            :close-button="{ icon: 'i-lucide-x', color: 'white', variant: 'link' }"
                            @close="dismissToast"
                        />
                    </div>
                </Transition>
            </Teleport>
        </UDashboardGroup>

    </UApp>
</template>
