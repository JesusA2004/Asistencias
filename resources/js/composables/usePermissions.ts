import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { User } from '@/types';

export function usePermissions() {
    const page = usePage();
    const user = computed(() => page.props.auth.user as User);

    const hasPermission = (permission: string): boolean => {
        return (user.value.permissions ?? []).includes(permission);
    };

    const hasRole = (role: string): boolean => {
        return (user.value.roles ?? []).includes(role);
    };

    const hasAnyPermission = (permissions: string[]): boolean => {
        return permissions.some((p) => hasPermission(p));
    };

    const hasAllPermissions = (permissions: string[]): boolean => {
        return permissions.every((p) => hasPermission(p));
    };

    return { hasPermission, hasRole, hasAnyPermission, hasAllPermissions, user };
}
