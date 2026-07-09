<script setup lang="ts">
import { computed } from 'vue';
import {
    AlertCircle,
    Building2,
    CheckCircle2,
    Clock,
    TrendingUp,
    Users,
    XCircle,
} from '@lucide/vue';
import KPICard from '@/components/KPICard.vue';
import AppChart from '@/components/AppChart.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import type { DashboardAdminStats } from '@/types/models';

const props = defineProps<{
    stats: DashboardAdminStats;
    chart_daily: Array<{ date: string; total: number; present: number; absent: number; late: number }>;
    chart_absents_by_client: Array<{ client_id: number; total: number; client?: { name: string } }>;
}>();

const dailySeries = computed(() => [
    { name: 'Presentes', data: props.chart_daily.map((d) => d.present) },
    { name: 'Faltas', data: props.chart_daily.map((d) => d.absent) },
    { name: 'Retardos', data: props.chart_daily.map((d) => d.late) },
]);

const dailyOptions = computed(() => ({
    colors: ['#22c55e', '#ef4444', '#a855f7'],
    xaxis: { categories: props.chart_daily.map((d) => d.date) },
    legend: { position: 'top' as const },
}));

const absentClientSeries = computed(() => [
    { name: 'Faltas', data: props.chart_absents_by_client.map((c) => c.total) },
]);

const absentClientOptions = computed(() => ({
    colors: ['#ef4444'],
    xaxis: { categories: props.chart_absents_by_client.map((c) => c.client?.name ?? `Empresa ${c.client_id}`) },
    plotOptions: { bar: { borderRadius: 4 } },
}));
</script>

<template>
    <div class="p-6 space-y-6">
        <div>
            <h1 class="text-2xl font-bold">Panel Administrativo</h1>
            <p class="text-sm text-muted-foreground mt-1">Resumen de hoy — {{ new Date().toLocaleDateString('es-MX', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            <KPICard
                title="Colaboradores Activos"
                :value="stats.active_employees.toLocaleString()"
                :icon="Users"
                color="blue"
                subtitle="Total en sistema"
            />
            <KPICard
                title="Presentes Hoy"
                :value="stats.today_present.toLocaleString()"
                :icon="CheckCircle2"
                color="green"
            />
            <KPICard
                title="Faltas Hoy"
                :value="stats.today_absent.toLocaleString()"
                :icon="XCircle"
                color="red"
            />
            <KPICard
                title="Retardos Hoy"
                :value="stats.today_late.toLocaleString()"
                :icon="Clock"
                color="purple"
            />
            <KPICard
                title="Cumplimiento"
                :value="`${stats.compliance_percentage}%`"
                :icon="TrendingUp"
                color="green"
                subtitle="vs total activos"
            />
            <KPICard
                title="Empresas Activas"
                :value="stats.active_clients.toLocaleString()"
                :icon="Building2"
                color="blue"
            />
            <KPICard
                title="Capturas Pendientes"
                :value="stats.pending_captures.toLocaleString()"
                :icon="AlertCircle"
                :color="stats.pending_captures > 0 ? 'orange' : 'green'"
                subtitle="sin registrar hoy"
            />
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <Card class="lg:col-span-2 border-0 shadow-sm">
                <CardHeader>
                    <CardTitle class="text-base">Asistencia — Últimos 30 días</CardTitle>
                    <CardDescription>Presentes, faltas y retardos diarios</CardDescription>
                </CardHeader>
                <CardContent>
                    <AppChart
                        v-if="chart_daily.length"
                        type="area"
                        :series="dailySeries"
                        :options="dailyOptions"
                        :height="280"
                    />
                    <div v-else class="flex items-center justify-center h-48 text-muted-foreground text-sm">
                        Sin datos para mostrar
                    </div>
                </CardContent>
            </Card>

            <Card class="border-0 shadow-sm">
                <CardHeader>
                    <CardTitle class="text-base">Faltas por Empresa (Hoy)</CardTitle>
                    <CardDescription>Top empresas con más ausencias</CardDescription>
                </CardHeader>
                <CardContent>
                    <AppChart
                        v-if="chart_absents_by_client.length"
                        type="bar"
                        :series="absentClientSeries"
                        :options="absentClientOptions"
                        :height="280"
                    />
                    <div v-else class="flex items-center justify-center h-48 text-muted-foreground text-sm">
                        Sin faltas registradas hoy
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
