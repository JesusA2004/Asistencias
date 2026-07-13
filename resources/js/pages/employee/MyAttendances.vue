<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Calendar, CheckCircle2, Clock, UserX, XCircle } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DatePicker from '@/components/DatePicker.vue';
import EmptyState from '@/components/EmptyState.vue';
import FormField from '@/components/FormField.vue';
import KPICard from '@/components/KPICard.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useInertiaLoading } from '@/composables/useInertiaLoading';
import { formatDateMx, formatTimeMx } from '@/lib/formatters';
import { ATTENDANCE_STATUS_OPTIONS } from '@/lib/status';
import type { Attendance, Employee } from '@/types/models';

const props = defineProps<{
    attendances: Attendance[] | null;
    employee: (Employee & { client?: { name: string }; shift?: { name: string; start_time: string; end_time: string } }) | null;
    stats: { present: number; absent: number; late: number; rest: number; total: number } | null;
    filters: { date_from?: string; date_to?: string; status?: string };
}>();

const { isLoading } = useInertiaLoading();

const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const status = ref<string | number | null>(props.filters.status ?? '');

const statusOptions = ATTENDANCE_STATUS_OPTIONS;

const hasActiveFilters = computed(() => !!(status.value));

const reload = () => {
    router.get('/mis-asistencias', {
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
};

watch([dateFrom, dateTo, status], reload);

const clearFilters = () => {
    status.value = '';
};
</script>

<template>
    <div class="w-full p-6">
        <PageHeader title="Mis Asistencias" description="Historial de asistencia personal" />

        <EmptyState
            v-if="!employee"
            title="Cuenta no vinculada"
            description="Tu cuenta no está vinculada a un colaborador. Contacta al administrador del sistema."
            :icon="UserX"
        />

        <template v-else>
            <!-- Employee info -->
            <Card class="mb-6 border-0 bg-gradient-to-r from-blue-50 to-indigo-50 shadow-sm dark:from-blue-950 dark:to-indigo-950">
                <CardContent class="p-4">
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
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

            <!-- Filters -->
            <div class="mb-6 grid grid-cols-2 gap-3 rounded-xl border bg-muted/30 p-4 md:grid-cols-4">
                <FormField label="Desde">
                    <DatePicker v-model="dateFrom" placeholder="Fecha inicial" />
                </FormField>
                <FormField label="Hasta">
                    <DatePicker v-model="dateTo" placeholder="Fecha final" />
                </FormField>
                <SearchableSelect v-model="status" :options="statusOptions" label="Estado" placeholder="Todos" />
                <div class="flex items-end">
                    <Button v-if="hasActiveFilters" variant="ghost" size="sm" class="w-full" @click="clearFilters">
                        Limpiar filtros
                    </Button>
                </div>
            </div>

            <!-- Stats -->
            <div v-if="stats" class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-5">
                <KPICard title="Presentes" :value="stats.present" :icon="CheckCircle2" color="green" />
                <KPICard title="Faltas" :value="stats.absent" :icon="XCircle" color="red" />
                <KPICard title="Retardos" :value="stats.late" :icon="Clock" color="purple" />
                <KPICard title="Descanso/Permiso" :value="stats.rest" :icon="Calendar" color="blue" />
                <KPICard title="Total" :value="stats.total" :icon="Calendar" color="orange" />
            </div>

            <!-- Attendance list -->
            <Card class="border-0 shadow-sm" :class="{ 'opacity-60': isLoading }">
                <CardHeader>
                    <CardTitle class="text-base">Registros del periodo</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <EmptyState
                        v-if="!attendances?.length"
                        title="Sin registros"
                        description="No hay asistencias registradas para el rango de fechas y filtros seleccionados."
                        :icon="Calendar"
                    />
                    <div v-else class="divide-y">
                        <div
                            v-for="a in attendances"
                            :key="a.id"
                            class="flex flex-col gap-3 p-4 transition-colors hover:bg-muted/40 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p class="text-sm font-medium">{{ formatDateMx(a.attendance_date) }}</p>
                                <p class="text-xs text-muted-foreground">{{ a.service_point?.name ?? '—' }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="text-right">
                                    <p class="font-mono text-xs">{{ formatTimeMx(a.entry_time) }} – {{ formatTimeMx(a.exit_time) }}</p>
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
