<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BarChart3, Building2, CheckCircle2, Users } from '@lucide/vue';
import KPICard from '@/components/KPICard.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    stats: {
        total_employees: number;
        today_present: number;
        active_clients: number;
    };
}>();
</script>

<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Panel RH / Consulta</h1>
                <p class="text-sm text-muted-foreground mt-1">Vista de resumen — solo lectura</p>
            </div>
            <Button as-child>
                <Link href="/reportes">
                    <BarChart3 class="h-4 w-4 mr-2" /> Ver Reportes
                </Link>
            </Button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <KPICard title="Colaboradores Activos" :value="stats.total_employees.toLocaleString()" :icon="Users" color="blue" />
            <KPICard title="Presentes Hoy" :value="stats.today_present.toLocaleString()" :icon="CheckCircle2" color="green" />
            <KPICard title="Empresas Activas" :value="stats.active_clients.toLocaleString()" :icon="Building2" color="purple" />
        </div>
    </div>
</template>
