<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, ClipboardList, MapPin, XCircle } from '@lucide/vue';
import { computed } from 'vue';
import AppChart from '@/components/AppChart.vue';
import KPICard from '@/components/KPICard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { Attendance, DashboardSupervisorStats } from '@/types/models';

const props = defineProps<{
    stats: DashboardSupervisorStats;
    assigned_locations_list: Array<{ client: string; service_point: string }>;
    recent_captures: Array<Attendance & { employee?: { name: string; last_name: string }; service_point?: { name: string } }>;
    chart_7_days: Array<{ date: string; total: number; present: number; absent: number; late: number }>;
}>();

const chartSeries = computed(() => [
    { name: 'Presentes', data: props.chart_7_days.map((d) => d.present) },
    { name: 'Faltas', data: props.chart_7_days.map((d) => d.absent) },
    { name: 'Retardos', data: props.chart_7_days.map((d) => d.late) },
]);

const chartOptions = computed(() => ({
    colors: ['#22c55e', '#ef4444', '#a855f7'],
    xaxis: { categories: props.chart_7_days.map((d) => d.date) },
    legend: { position: 'top' as const },
}));
</script>

<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-bold">Mi Panel de Supervisor</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    {{ new Date().toLocaleDateString('es-MX', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                </p>
            </div>
            <Button as-child>
                <Link href="/asistencias/capturar">
                    <ClipboardList class="h-4 w-4 mr-2" />
                    Capturar Asistencia
                </Link>
            </Button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <KPICard
                title="Ubicaciones Asignadas"
                :value="stats.assigned_locations"
                :icon="MapPin"
                color="blue"
            />
            <KPICard
                title="Capturas Pendientes"
                :value="stats.pending_captures_today"
                :icon="AlertCircle"
                :color="stats.pending_captures_today > 0 ? 'orange' : 'green'"
                subtitle="sin registrar hoy"
            />
            <KPICard
                title="Capturadas Hoy"
                :value="stats.captured_today"
                :icon="CheckCircle2"
                color="green"
            />
            <KPICard
                title="Faltas Reportadas"
                :value="stats.absences_today"
                :icon="XCircle"
                color="red"
            />
            <KPICard
                title="Retardos Reportados"
                :value="stats.lates_today"
                :icon="ClipboardList"
                color="purple"
            />
        </div>

        <div class="bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                Recuerda: Las asistencias deben capturarse el día correspondiente. Una vez guardadas, solo el administrador puede corregirlas.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <Card class="lg:col-span-2 border-0 shadow-sm">
                <CardHeader>
                    <CardTitle class="text-base">Asistencia — Últimos 7 días</CardTitle>
                    <CardDescription>Registros capturados por ti</CardDescription>
                </CardHeader>
                <CardContent>
                    <AppChart
                        v-if="chart_7_days.length"
                        type="area"
                        :series="chartSeries"
                        :options="chartOptions"
                        :height="260"
                    />
                    <div v-else class="flex items-center justify-center h-48 text-muted-foreground text-sm">
                        Aún no tienes capturas registradas
                    </div>
                </CardContent>
            </Card>

            <Card class="border-0 shadow-sm">
                <CardHeader>
                    <CardTitle class="text-base">Mis Ubicaciones</CardTitle>
                    <CardDescription>Empresas y puntos asignados</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div v-if="!assigned_locations_list.length" class="text-sm text-muted-foreground text-center py-8">Sin asignaciones todavía</div>
                    <div v-for="(loc, i) in assigned_locations_list" :key="i" class="flex items-center gap-2 py-1.5 border-b last:border-0 text-sm">
                        <MapPin class="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                        <div class="min-w-0">
                            <p class="font-medium truncate">{{ loc.client }}</p>
                            <p class="text-xs text-muted-foreground truncate">{{ loc.service_point }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card class="border-0 shadow-sm">
            <CardHeader>
                <CardTitle class="text-base">Historial de Capturas Recientes</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="!recent_captures.length" class="text-sm text-muted-foreground text-center py-8">Aún no has capturado asistencias</div>
                <div class="space-y-2">
                    <div
                        v-for="c in recent_captures"
                        :key="c.id"
                        class="flex items-center justify-between py-2 border-b last:border-0"
                    >
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate">{{ c.employee?.name }} {{ c.employee?.last_name }}</p>
                            <p class="text-xs text-muted-foreground truncate">{{ c.service_point?.name }} — {{ c.attendance_date }}</p>
                        </div>
                        <StatusBadge :status="c.status" />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
