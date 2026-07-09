<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Building2,
    ClipboardList,
    Clock,
    Eye,
    HardHat,
    History,
    LayoutDashboard,
    MapPin,
    PenLine,
    Shield,
    UserCheck,
    Users,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem, NavGroup } from '@/types';
import type { User } from '@/types';

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

const user = computed(() => page.props.auth.user as User);
const perms = computed(() => user.value.permissions ?? []);
const has = (p: string) => perms.value.includes(p);

const navGroups = computed((): NavGroup[] => [
    {
        label: '',
        items: [
            { title: 'Panel principal', href: '/dashboard', icon: LayoutDashboard },
        ],
    },
    {
        label: 'Catálogos',
        items: [
            ...(has('Ver usuarios') ? [{ title: 'Usuarios', href: '/usuarios', icon: Users }] : []),
            ...(has('Ver roles y permisos') ? [{ title: 'Roles y Permisos', href: '/roles', icon: Shield }] : []),
            ...(has('Ver colaboradores') ? [{ title: 'Colaboradores', href: '/colaboradores', icon: HardHat }] : []),
            ...(has('Ver empresas') ? [{ title: 'Empresas', href: '/empresas', icon: Building2 }] : []),
            ...(has('Ver puntos de servicio') ? [{ title: 'Puntos de Servicio', href: '/puntos-servicio', icon: MapPin }] : []),
            ...(has('Ver turnos') ? [{ title: 'Turnos', href: '/turnos', icon: Clock }] : []),
            ...(has('Ver asignaciones') ? [{ title: 'Asignaciones', href: '/asignaciones', icon: UserCheck }] : []),
        ],
    },
    {
        label: 'Asistencias',
        items: [
            ...(has('Registrar asistencias') ? [{ title: 'Capturar Asistencia', href: '/asistencias/capturar', icon: ClipboardList }] : []),
            ...(has('Ver asistencias') ? [{ title: 'Gestión Asistencias', href: '/asistencias', icon: PenLine }] : []),
            ...(has('Ver mis asistencias') ? [{ title: 'Mis Asistencias', href: '/mis-asistencias', icon: Eye }] : []),
        ],
    },
    {
        label: 'Reportes',
        items: [
            ...(has('Ver reportes') ? [{ title: 'Reportes', href: '/reportes', icon: BarChart3 }] : []),
            ...(has('Ver auditoría') ? [{ title: 'Auditoría', href: '/auditoria', icon: History }] : []),
        ],
    },
]);

const visibleGroups = computed(() => navGroups.value.filter((g) => g.items.length > 0));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup
                v-for="group in visibleGroups"
                :key="group.label"
                class="px-2 py-0"
            >
                <SidebarGroupLabel v-if="group.label">{{ group.label }}</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-for="item in group.items" :key="item.title">
                        <SidebarMenuButton
                            as-child
                            :is-active="isCurrentUrl(item.href)"
                            :tooltip="item.title"
                        >
                            <Link :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
