<script setup lang="ts">
import { AlertCircle, CheckCircle2, ClipboardList, MapPin, XCircle } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import KPICard from '@/components/KPICard.vue';
import { Button } from '@/components/ui/button';
import type { DashboardSupervisorStats } from '@/types/models';

defineProps<{
    stats: DashboardSupervisorStats;
}>();
</script>

<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
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
    </div>
</template>
