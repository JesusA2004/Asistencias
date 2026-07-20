<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ClipboardList, Image, PenLine } from '@lucide/vue';
import { computed } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn } from '@/lib/utils';
import type { User } from '@/types';

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

const user = computed(() => page.props.auth.user as User);
const perms = computed(() => user.value.permissions ?? []);
const has = (p: string) => perms.value.includes(p);

const TABS = [
    { key: 'capturar', title: 'Capturar', href: '/asistencias/capturar', icon: ClipboardList, permission: 'Registrar asistencias' },
    { key: 'gestion', title: 'Gestión', href: '/asistencias/gestion', icon: PenLine, permission: 'Ver asistencias' },
    { key: 'evidencias', title: 'Evidencias', href: '/asistencias/evidencias', icon: Image, permission: 'Ver evidencias de asistencia' },
] as const;

const visibleTabs = computed(() => TABS.filter((t) => has(t.permission)));
</script>

<template>
    <div v-if="visibleTabs.length > 1" class="mb-6 flex gap-1 overflow-x-auto border-b">
        <Link
            v-for="tab in visibleTabs"
            :key="tab.key"
            :href="tab.href"
            :class="cn(
                'flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                isCurrentUrl(tab.href)
                    ? 'border-primary text-primary'
                    : 'border-transparent text-muted-foreground hover:text-foreground',
            )"
        >
            <component :is="tab.icon" class="h-4 w-4" />
            {{ tab.title }}
        </Link>
    </div>
</template>
