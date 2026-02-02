import type { NavigationMenuItem } from '@nuxt/ui';
import { useCmsPath } from './useCmsPath';

export function useSettingsNav() {
    const { cmsPath } = useCmsPath();

    const mainNav: NavigationMenuItem[][] = [[
        { label: 'General', icon: 'i-lucide-settings', to: cmsPath('/settings/general') },
        { label: 'Media', icon: 'i-lucide-image', to: cmsPath('/settings/media') },
        { label: 'Post Types', icon: 'i-lucide-file-text', to: cmsPath('/settings/post-types') },
        { label: 'Workflows', icon: 'i-lucide-git-branch', to: cmsPath('/settings/workflows') },
        { label: 'Languages', icon: 'i-lucide-globe', to: cmsPath('/settings/languages') },
        { label: 'SEO', icon: 'i-lucide-search', to: cmsPath('/settings/seo') },
        { label: 'Sections', icon: 'i-lucide-layers', to: cmsPath('/settings/section-categories') },
        { label: 'Units', icon: 'i-lucide-scale', to: cmsPath('/units') },
        { label: 'Ingredients', icon: 'i-lucide-carrot', to: cmsPath('/ingredients') },
        { label: 'Ad Placements', icon: 'i-lucide-megaphone', to: cmsPath('/ad-placements') },
    ]];

    return {
        mainNav,
    };
}
