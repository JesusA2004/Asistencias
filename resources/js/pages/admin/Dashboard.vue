<script setup lang="ts">
import {
    AlertCircle,
    AlertTriangle,
    TrendingDown,
    TrendingUp,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppChart from '@/components/AppChart.vue';
import KPICard from '@/components/KPICard.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import type { DashboardAdminStats } from '@/types/models';

const props = defineProps<{
    stats: DashboardAdminStats;
    chart_daily: Array<{ date: string; total: number; present: number; absent: number; late: number }>;
    chart_absents_by_client: Array<{ client_id: number; total: number; client?: { name: string } }>;
    status_distribution: Array<{ status: string; total: number }>;
    compliance_by_location: Array<{ name: string; total_employees: number; compliance: number }>;
    lates_by_supervisor: Array<{ supervisor_id: number; total: number; supervisor?: { name: string } }>;
    top_incident_points: Array<{ service_point_id: number; incidents: number; service_point?: { name: string } }>;
    pending_captures_by_client: Array<{ name: string; pending: number; total: number }>;
    weekly_comparison: {
        current: { total: number; presente: number; falta: number; retardo: number };
        previous: { total: number; presente: number; falta: number; retardo: number };
    };
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

const STATUS_LABELS: Record<string, string> = {
    presente: 'Presente',
    falta: 'Falta',
    retardo: 'Retardo',
    descanso: 'Descanso',
    permiso: 'Permiso',
    incapacidad: 'Incapacidad',
};
const STATUS_COLORS: Record<string, string> = {
    presente: '#22c55e',
    falta: '#ef4444',
    retardo: '#a855f7',
    descanso: '#3b82f6',
    permiso: '#eab308',
    incapacidad: '#f97316',
};

const distributionSeries = computed(() => props.status_distribution.map((s) => s.total));
const distributionOptions = computed(() => ({
    labels: props.status_distribution.map((s) => STATUS_LABELS[s.status] ?? s.status),
    colors: props.status_distribution.map((s) => STATUS_COLORS[s.status] ?? '#94a3b8'),
    legend: { position: 'bottom' as const },
}));
const hasDistributionData = computed(() => props.status_distribution.some((s) => s.total > 0));

const complianceSeries = computed(() => [
    { name: 'Cumplimiento %', data: props.compliance_by_location.map((c) => c.compliance) },
]);
const complianceOptions = computed(() => ({
    colors: ['#3b82f6'],
    plotOptions: { bar: { borderRadius: 4, horizontal: true } },
    xaxis: { categories: props.compliance_by_location.map((c) => c.name), max: 100 },
}));

const latesSeries = computed(() => [
    { name: 'Retardos', data: props.lates_by_supervisor.map((s) => s.total) },
]);
const latesOptions = computed(() => ({
    colors: ['#a855f7'],
    plotOptions: { bar: { borderRadius: 4 } },
    xaxis: { categories: props.lates_by_supervisor.map((s) => s.supervisor?.name ?? `#${s.supervisor_id}`) },
}));

const weeklyDelta = (key: 'total' | 'presente' | 'falta' | 'retardo') => {
    const cur = props.weekly_comparison.current[key] ?? 0;
    const prev = props.weekly_comparison.previous[key] ?? 0;

    if (prev === 0) {
return cur > 0 ? 100 : 0;
}

    return Math.round(((cur - prev) / prev) * 100);
};
</script>

<template>
    <div class="p-6 space-y-6">
        <div>
            <h1 class="text-2xl font-bold">Panel Administrativo</h1>
            <p class="text-sm text-muted-foreground mt-1">Resumen de hoy — {{ new Date().toLocaleDateString('es-MX', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <KPICard
                title="Colaboradores Activos"
                :value="stats.active_employees.toLocaleString()"
                :icon="Users"
                color="blue"
                subtitle="Total en sistema"
            />
            <KPICard
                title="Cumplimiento Hoy"
                :value="`${stats.compliance_percentage}%`"
                :icon="TrendingUp"
                color="green"
                subtitle="vs total activos"
            />
            <KPICard
                title="Incidencias Hoy"
                :value="stats.today_incidents.toLocaleString()"
                :icon="AlertTriangle"
                :color="stats.today_incidents > 0 ? 'red' : 'green'"
                subtitle="faltas + retardos"
            />
            <KPICard
                title="Capturas Pendientes"
                :value="stats.pending_captures.toLocaleString()"
                :icon="AlertCircle"
                :color="stats.pending_captures > 0 ? 'orange' : 'green'"
                subtitle="sin registrar hoy"
            />
        </div>

        <!-- Charts Row 1 -->
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
                    <CardTitle class="text-base">Distribución de Estados (Hoy)</CardTitle>
                    <CardDescription>Proporción por tipo de registro</CardDescription>
                </CardHeader>
                <CardContent>
                    <AppChart
                        v-if="hasDistributionData"
                        type="donut"
                        :series="distributionSeries"
                        :options="distributionOptions"
                        :height="280"
                    />
                    <div v-else class="flex items-center justify-center h-48 text-muted-foreground text-sm">
                        Aún no hay capturas hoy
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Charts Row 2 -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <Card class="border-0 shadow-sm">
                <CardHeader>
                    <CardTitle class="text-base">Cumplimiento por Ubicación</CardTitle>
                    <CardDescription>Puntos con menor % de asistencia hoy</CardDescription>
                </CardHeader>
                <CardContent>
                    <AppChart
                        v-if="compliance_by_location.length"
                        type="bar"
                        :series="complianceSeries"
                        :options="complianceOptions"
                        :height="280"
                    />
                    <div v-else class="flex items-center justify-center h-48 text-muted-foreground text-sm">
                        Sin datos para mostrar
                    </div>
                </CardContent>
            </Card>

            <Card class="border-0 shadow-sm">
                <CardHeader>
                    <CardTitle class="text-base">Retardos por Supervisor</CardTitle>
                    <CardDescription>Acumulado del mes actual</CardDescription>
                </CardHeader>
                <CardContent>
                    <AppChart
                        v-if="lates_by_supervisor.length"
                        type="bar"
                        :series="latesSeries"
                        :options="latesOptions"
                        :height="280"
                    />
                    <div v-else class="flex items-center justify-center h-48 text-muted-foreground text-sm">
                        Sin retardos registrados este mes
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

        <!-- Row 3: rankings + comparativo semanal -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <Card class="border-0 shadow-sm">
                <CardHeader>
                    <CardTitle class="text-base flex items-center gap-2"><AlertTriangle class="h-4 w-4 text-orange-500" /> Top Incidencias</CardTitle>
                    <CardDescription>Puntos con más faltas + retardos (mes)</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div v-if="!top_incident_points.length" class="text-sm text-muted-foreground text-center py-8">Sin incidencias este mes</div>
                    <div v-for="(p, i) in top_incident_points" :key="p.service_point_id" class="flex items-center justify-between text-sm py-1.5 border-b last:border-0">
                        <span class="flex items-center gap-2 min-w-0">
                            <span class="text-xs text-muted-foreground w-4 shrink-0">{{ i + 1 }}.</span>
                            <span class="truncate">{{ p.service_point?.name ?? `Punto #${p.service_point_id}` }}</span>
                        </span>
                        <Badge variant="outline" class="text-orange-600 border-orange-200 dark:text-orange-400 shrink-0">{{ p.incidents }}</Badge>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-0 shadow-sm">
                <CardHeader>
                    <CardTitle class="text-base">Capturas Pendientes por Empresa</CardTitle>
                    <CardDescription>Colaboradores sin registro hoy</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-if="!pending_captures_by_client.length" class="text-sm text-muted-foreground text-center py-8">Todas las empresas están al día</div>
                    <div v-for="c in pending_captures_by_client" :key="c.name" class="space-y-1">
                        <div class="flex items-center justify-between text-sm">
                            <span class="truncate">{{ c.name }}</span>
                            <span class="text-muted-foreground text-xs">{{ c.pending }}/{{ c.total }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-muted overflow-hidden">
                            <div class="h-full bg-orange-500 rounded-full" :style="{ width: `${Math.round((c.pending / c.total) * 100)}%` }" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-0 shadow-sm">
                <CardHeader>
                    <CardTitle class="text-base">Comparativo Semanal</CardTitle>
                    <CardDescription>Esta semana vs. semana anterior</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Total registros</span>
                        <div class="flex items-center gap-1.5">
                            <span class="font-semibold">{{ weekly_comparison.current.total }}</span>
                            <span :class="['text-xs flex items-center gap-0.5', weeklyDelta('total') >= 0 ? 'text-green-600' : 'text-red-600']">
                                <component :is="weeklyDelta('total') >= 0 ? TrendingUp : TrendingDown" class="h-3 w-3" />
                                {{ Math.abs(weeklyDelta('total')) }}%
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Presentes</span>
                        <div class="flex items-center gap-1.5">
                            <span class="font-semibold">{{ weekly_comparison.current.presente }}</span>
                            <span :class="['text-xs flex items-center gap-0.5', weeklyDelta('presente') >= 0 ? 'text-green-600' : 'text-red-600']">
                                <component :is="weeklyDelta('presente') >= 0 ? TrendingUp : TrendingDown" class="h-3 w-3" />
                                {{ Math.abs(weeklyDelta('presente')) }}%
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Faltas</span>
                        <div class="flex items-center gap-1.5">
                            <span class="font-semibold">{{ weekly_comparison.current.falta }}</span>
                            <span :class="['text-xs flex items-center gap-0.5', weeklyDelta('falta') <= 0 ? 'text-green-600' : 'text-red-600']">
                                <component :is="weeklyDelta('falta') <= 0 ? TrendingDown : TrendingUp" class="h-3 w-3" />
                                {{ Math.abs(weeklyDelta('falta')) }}%
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Retardos</span>
                        <div class="flex items-center gap-1.5">
                            <span class="font-semibold">{{ weekly_comparison.current.retardo }}</span>
                            <span :class="['text-xs flex items-center gap-0.5', weeklyDelta('retardo') <= 0 ? 'text-green-600' : 'text-red-600']">
                                <component :is="weeklyDelta('retardo') <= 0 ? TrendingDown : TrendingUp" class="h-3 w-3" />
                                {{ Math.abs(weeklyDelta('retardo')) }}%
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
