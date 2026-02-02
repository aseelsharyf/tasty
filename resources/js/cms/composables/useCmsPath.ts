import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface PagePropsWithCmsPath {
    cmsBasePath?: string;
}

export function useCmsPath() {
    const page = usePage<PagePropsWithCmsPath>();

    const basePath = computed(() => {
        // Use nullish coalescing to handle empty string correctly
        const path = page.props.cmsBasePath ?? '/cms';
        // Ensure path doesn't end with slash (unless it's just '/')
        return path === '/' ? '' : path.replace(/\/$/, '');
    });

    /**
     * Generate a CMS path by prepending the base path.
     * @param path - The path to prefix (e.g., '/posts/en' or 'posts/en')
     * @returns The full CMS path (e.g., '/cms/posts/en' or '/posts/en')
     */
    function cmsPath(path: string): string {
        // Ensure path starts with slash
        const normalizedPath = path.startsWith('/') ? path : `/${path}`;
        return `${basePath.value}${normalizedPath}`;
    }

    /**
     * Generate a CMS fetch URL for API calls.
     * @param path - The API path to prefix (e.g., '/api/posts' or 'api/posts')
     * @returns The full CMS API path
     */
    function cmsFetchUrl(path: string): string {
        return cmsPath(path);
    }

    return {
        basePath,
        cmsPath,
        cmsFetchUrl,
    };
}
