import type { NavigationMenuItem } from '@nuxt/ui';

export function useSettingsNav() {
    const mainNav: NavigationMenuItem[][] = [[
        { label: 'General', icon: 'i-lucide-settings', to: '/cms/settings/general' },
        { label: 'Media', icon: 'i-lucide-image', to: '/cms/settings/media' },
        { label: 'Post Types', icon: 'i-lucide-file-text', to: '/cms/settings/post-types' },
        { label: 'Workflows', icon: 'i-lucide-git-branch', to: '/cms/settings/workflows' },
        { label: 'Languages', icon: 'i-lucide-globe', to: '/cms/settings/languages' },
        { label: 'SEO', icon: 'i-lucide-search', to: '/cms/settings/seo' },
        { label: 'Section Categories', icon: 'i-lucide-layers', to: '/cms/settings/section-categories' },
        { label: 'Units', icon: 'i-lucide-scale', to: '/cms/units' },
        { label: 'Ingredients', icon: 'i-lucide-carrot', to: '/cms/ingredients' },
    ]];

    return {
        mainNav,
    };
}
