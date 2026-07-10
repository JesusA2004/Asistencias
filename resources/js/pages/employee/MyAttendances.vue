<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Calendar, CheckCircle2, Clock, XCircle } from '@lucide/vue';
import { ref } from 'vue';
import KPICard from '@/components/KPICard.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateMx } from '@/lib/formatters';
import type { Attendance, Employee } from '@/types/models';

const props = defineProps<{
    attendances: Attendance[] | null;
    employee: (Employee & { client?: { name: string }; shift?: { name: string; start_time: string; end_time: string } }) | null;
    stats: { present: number; absent: number; late: number; rest: number; total: number } | null;
    filters: { month?: string };
}>();

const selectedMonth = ref(props.filters.month ?? new Date().toISOString().slice(0, 7));

const changeMonth = () => {
    router.get('/mis-asistencias', { month: selectedMonth.value }, { preserveState: true });
};

const STATUS_ICONS: Record<string, string> = {
    presente: '✅',
    falta: '❌',
    retardo: '⏰',
    descanso: '😴',
    permiso: '📋',
    incapacidad: '🏥',
};
</script>

<template>
    <div class="p-6 max-w-4xl mx-auto">
        <PageHeader title="Mis Asistencias" description="Historial de asistencia personal" />

        <div v-if="!employee" class="rounded-lg border border-dashed p-12 text-center text-muted-foreground">
            Tu cuenta no está vinculada a un colaborador. Contacta al administrador del sistema.
        </div>

        <template v-else>
            <!-- Employee info -->
            <Card class="mb-6 border-0 shadow-sm bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950 dark:to-indigo-950">
                <CardContent class="p-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-muted-foreground">Nombre</p>
                            <p class="font-semibold">{{ employee.name }} {{ employee.last_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">No. Empleado</p>
                            <p class="font-mono font-medium">{{ employee.employee_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Empresa</p>
                            <p class="font-medium">{{ employee.client?.name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Turno</p>
                            <p class="font-medium">{{ employee.shift?.name ?? '—' }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Month selector -->
            <div class="flex items-center gap-3 mb-6">
                <Label>Mes:</Label>
                <Input type="month" v-model="selectedMonth" class="w-48" @change="changeMonth" />
            </div>

            <!-- Stats -->
            <div v-if="stats" class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <KPICard title="Presentes" :value="stats.present" :icon="CheckCircle2" color="green" />
                <KPICard title="Faltas" :value="stats.absent" :icon="XCircle" color="red" />
                <KPICard title="Retardos" :value="stats.late" :icon="Clock" color="purple" />
                <KPICard title="Descanso/Permiso" :value="stats.rest" :icon="Calendar" color="blue" />
                <KPICard title="Total" :value="stats.total" :icon="Calendar" color="orange" />
            </div>

            <!-- Attendance list -->
            <Card class="border-0 shadow-sm">
                <CardHeader>
                    <CardTitle class="text-base">Registros de {{ selectedMonth }}</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div v-if="!attendances?.length" class="py-12 text-center text-muted-foreground text-sm">
                        Sin registros para este mes.
                    </div>
                    <div class="divide-y" v-else>
                        <div
                            v-for="a in attendances"
                            :key="a.id"
                            class="flex items-center justify-between p-4 hover:bg-muted/40 transition-colors"
                        >
                            <div class="flex items-center gap-4">
                                <div class="w-8 text-lg text-center">{{ STATUS_ICONS[a.status] ?? '—' }}</div>
                                <div>
                                    <p class="font-medium text-sm">{{ formatDateMx(a.attendance_date) }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ a.service_point?.name ?? '—' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right hidden sm:block">
                                    <p class="text-xs font-mono">{{ a.entry_time ?? '--:--' }} – {{ a.exit_time ?? '--:--' }}</p>
                                    <p v-if="a.notes" class="text-xs text-muted-foreground">{{ a.notes }}</p>
                                </div>
                                <StatusBadge :status="a.status" />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
