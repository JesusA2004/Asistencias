<script setup lang="ts">
import { Calendar, CheckCircle2, Clock, XCircle } from '@lucide/vue';
import KPICard from '@/components/KPICard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDateMx } from '@/lib/formatters';
import type { Attendance } from '@/types/models';

defineProps<{
    stats: { month_present: number; month_absent: number; month_late: number; month_total: number } | null;
    recent_attendances: Attendance[];
}>();
</script>

<template>
    <div class="p-6 space-y-6">
        <div>
            <h1 class="text-2xl font-bold">Mis Asistencias</h1>
            <p class="text-sm text-muted-foreground mt-1">Resumen del mes actual</p>
        </div>

        <div v-if="stats" class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <KPICard title="Días Presentes" :value="stats.month_present" :icon="CheckCircle2" color="green" />
            <KPICard title="Faltas" :value="stats.month_absent" :icon="XCircle" color="red" />
            <KPICard title="Retardos" :value="stats.month_late" :icon="Clock" color="purple" />
            <KPICard title="Total Registros" :value="stats.month_total" :icon="Calendar" color="blue" />
        </div>

        <div v-else class="rounded-lg border border-dashed p-8 text-center">
            <p class="text-muted-foreground">Tu cuenta no está vinculada a un colaborador. Contacta al administrador.</p>
        </div>

        <Card v-if="recent_attendances.length" class="border-0 shadow-sm">
            <CardHeader>
                <CardTitle class="text-base">Registros Recientes</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <div
                        v-for="a in recent_attendances"
                        :key="a.id"
                        class="flex items-center justify-between py-2 border-b last:border-0"
                    >
                        <div>
                            <p class="text-sm font-medium">{{ formatDateMx(a.attendance_date) }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ a.entry_time ?? '--:--' }} – {{ a.exit_time ?? '--:--' }}
                            </p>
                        </div>
                        <StatusBadge :status="a.status" />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
