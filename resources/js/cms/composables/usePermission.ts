import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { PageProps } from '../types';

export function usePermission() {
    const page = usePage<PageProps>();

    const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
    const roles = computed(() => page.props.auth?.user?.roles ?? []);

    function can(permission: string | string[]): boolean {
        const perms = permissions.value;
        if (Array.isArray(permission)) {
            return permission.some((p) => perms.includes(p));
        }
        return perms.includes(permission);
    }

    function canAll(permissionList: string[]): boolean {
        const perms = permissions.value;
        return permissionList.every((p) => perms.includes(p));
    }

    function hasRole(role: string | string[]): boolean {
        const userRoles = roles.value;
        if (Array.isArray(role)) {
            return role.some((r) => userRoles.includes(r));
        }
        return userRoles.includes(role);
    }

    function hasAllRoles(roleList: string[]): boolean {
        const userRoles = roles.value;
        return roleList.every((r) => userRoles.includes(r));
    }

    function isAdmin(): boolean {
        return hasRole('Admin');
    }

    return {
        permissions,
        roles,
        can,
        canAll,
        hasRole,
        hasAllRoles,
        isAdmin,
    };
}
