<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Building2,
    Camera,
    ClipboardList,
    Clock,
    HardHat,
    History,
    LayoutDashboard,
    MapPin,
    Settings,
    Shield,
    UserCheck,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
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
import type { NavGroup } from '@/types';
import type { User } from '@/types';

type NavHref = NavGroup['items'][number]['href'];

const page = usePage();
const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

// El hub de Asistencias vive en /asistencias/{capturar,gestion,evidencias} pero el
// sidebar solo enlaza a /asistencias — hay que resaltarlo también en sus sub-rutas.
const isNavItemActive = (href: NavHref) => (href === '/asistencias' ? isCurrentOrParentUrl(href) : isCurrentUrl(href));

const user = computed(() => page.props.auth.user as User);
const perms = computed(() => user.value.permissions ?? []);
const has = (p: string) => perms.value.includes(p);

const roles = computed(() => user.value.roles ?? []);
const hasRole = (role: string) => roles.value.includes(role);
const isAdmin = computed(() => hasRole('administrador'));
const isRh = computed(() => hasRole('rh'));
const isSupervisor = computed(() => hasRole('supervisor'));
const isCollaborator = computed(() => hasRole('colaborador'));

// Colaborador "puro": sin ningún rol operativo/administrativo encima. Solo a estos
// usuarios se les muestra el flujo personal de "Mi Asistencia" — un admin/RH/supervisor
// no debe ver módulos de autorregistro solo porque también tiene ese permiso.
const isPureCollaborator = computed(
    () => isCollaborator.value && !isAdmin.value && !isRh.value && !isSupervisor.value,
);

const attendancePhotoReviewEnabled = computed(
    () => page.props.settings?.attendance_photo_review_enabled ?? true,
);

const showEvidence = computed(
    () => has('Ver evidencias de asistencia') && !isPureCollaborator.value && attendancePhotoReviewEnabled.value,
);

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
            ...(isPureCollaborator.value ? [{ title: 'Mi Asistencia', href: '/mi-asistencia', icon: Camera }] : []),
            // Capturar/Gestión/Evidencias viven bajo un solo hub con tabs (/asistencias) —
            // un único ítem evita que el sidebar muestre 3 entradas para lo que es una
            // sola sección. El hub redirige al primer tab al que el usuario tenga acceso.
            ...(!isPureCollaborator.value && (has('Registrar asistencias') || has('Ver asistencias') || showEvidence.value)
                ? [{ title: 'Asistencias', href: '/asistencias', icon: ClipboardList }]
                : []),
        ],
    },
    {
        label: 'Reportes',
        items: [
            ...(has('Ver reportes') ? [{ title: 'Reportes', href: '/reportes', icon: BarChart3 }] : []),
            ...(has('Ver auditoría') ? [{ title: 'Auditoría', href: '/auditoria', icon: History }] : []),
        ],
    },
    {
        label: 'Sistema',
        items: [
            ...(has('Ver configuración') ? [{ title: 'Configuración', href: '/configuracion', icon: Settings }] : []),
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
                            :is-active="isNavItemActive(item.href)"
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
