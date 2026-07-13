<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { AlertTriangle, BarChart3, ChevronDown, Download, FileText, Search, X } from '@lucide/vue';
import type {ColumnDef} from '@tanstack/vue-table';
import { computed, ref, watch } from 'vue';
import AppChart from '@/components/AppChart.vue';
import AppDataTable from '@/components/AppDataTable.vue';
import DatePicker from '@/components/DatePicker.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/composables/usePermissions';
import { formatShortDateMx } from '@/lib/formatters';
import { ATTENDANCE_STATUS_CONFIG, ATTENDANCE_STATUS_HEX, ATTENDANCE_STATUS_ORDER } from '@/lib/status';
import type { Attendance, Client, Employee, PaginatedData, ServicePoint, Shift } from '@/types/models';

type ChartBucket = { label: string; total: number };

type ReportFilters = {
    date_from?: string;
    date_to?: string;
    client_id?: string;
    service_point_id?: string;
    employee_id?: string;
    supervisor_id?: string;
    shift_id?: string;
    status?: string;
    search?: string;
    only_incidents?: string;
    only_present?: string;
};

const props = defineProps<{
    attendances: PaginatedData<Attendance> | null;
    summary: { total: number; presente: number; falta: number; retardo: number; descanso: number; permiso: number; incapacidad: number } | null;
    byClient: ChartBucket[] | null;
    incidentsByServicePoint: ChartBucket[] | null;
    trend: ChartBucket[] | null;
    clients: Client[];
    servicePoints: (ServicePoint & { client_id: number })[];
    employees: (Employee & { client_id: number | null })[];
    supervisors: { id: number; name: string }[];
    shifts: Shift[];
    filters: ReportFilters;
}>();

const { hasPermission } = usePermissions();

type SelectModel = string | number | null;

const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const clientId = ref<SelectModel>(props.filters.client_id ?? '');
const spId = ref<SelectModel>(props.filters.service_point_id ?? '');
const employeeId = ref<SelectModel>(props.filters.employee_id ?? '');
const supervisorId = ref<SelectModel>(props.filters.supervisor_id ?? '');
const shiftId = ref<SelectModel>(props.filters.shift_id ?? '');
const status = ref<SelectModel>(props.filters.status ?? '');
const search = ref(props.filters.search ?? '');
const onlyIncidents = ref(props.filters.only_incidents === '1');
const onlyPresent = ref(props.filters.only_present === '1');

const STATUSES = [
    { value: 'presente', label: 'Presente' },
    { value: 'falta', label: 'Falta' },
    { value: 'retardo', label: 'Retardo' },
    { value: 'descanso', label: 'Descanso' },
    { value: 'permiso', label: 'Permiso' },
    { value: 'incapacidad', label: 'Incapacidad' },
];

const clientOptions = computed(() => props.clients.map((c) => ({ value: c.id, label: c.name })));
const servicePointOptions = computed(() =>
    props.servicePoints
        .filter((sp) => !clientId.value || String(sp.client_id) === String(clientId.value))
        .map((sp) => ({ value: sp.id, label: sp.name })),
);
const employeeOptions = computed(() =>
    props.employees
        .filter((e) => !clientId.value || String(e.client_id) === String(clientId.value))
        .map((e) => ({ value: e.id, label: `${e.name} ${e.last_name}`, description: e.employee_number })),
);
const supervisorOptions = computed(() => props.supervisors.map((s) => ({ value: s.id, label: s.name })));
const shiftOptions = computed(() => props.shifts.map((s) => ({ value: s.id, label: s.name })));
const statusOptions = computed(() => STATUSES);

const hasValidRange = computed(() => !!dateFrom.value && !!dateTo.value);

// Paleta categórica de referencia (orden fijo, nunca ciclada arbitrariamente)
// para series identitarias como "empresa". Ver skill dataviz/references/palette.md.
const CATEGORICAL_HEX = ['#2a78d6', '#1baf7a', '#eda100', '#008300', '#4a3aa7', '#e34948', '#e87ba4', '#eb6834'];

const statusDonutSeries = computed(() => ATTENDANCE_STATUS_ORDER.map((s) => props.summary?.[s] ?? 0));
const statusDonutOptions = computed(() => ({
    labels: ATTENDANCE_STATUS_ORDER.map((s) => ATTENDANCE_STATUS_CONFIG[s].label),
    colors: ATTENDANCE_STATUS_ORDER.map((s) => ATTENDANCE_STATUS_HEX[s]),
    legend: { position: 'bottom' as const },
    dataLabels: { enabled: true, formatter: (val: number) => `${val.toFixed(0)}%` },
}));

const byClientSeries = computed(() => [{ name: 'Asistencias', data: (props.byClient ?? []).map((c) => c.total) }]);
const byClientOptions = computed(() => ({
    xaxis: { categories: (props.byClient ?? []).map((c) => c.label) },
    colors: CATEGORICAL_HEX,
    legend: { show: false },
    plotOptions: { bar: { distributed: true, borderRadius: 4, columnWidth: '55%' } },
}));

const incidentsSeries = computed(() => [{ name: 'Incidencias', data: (props.incidentsByServicePoint ?? []).map((c) => c.total) }]);
const incidentsOptions = computed(() => ({
    xaxis: { categories: (props.incidentsByServicePoint ?? []).map((c) => c.label) },
    colors: ['#dc2626'],
    legend: { show: false },
    plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
}));

const trendSeries = computed(() => [{ name: 'Registros', data: (props.trend ?? []).map((t) => t.total) }]);
const trendOptions = computed(() => ({
    xaxis: { categories: (props.trend ?? []).map((t) => formatShortDateMx(t.label)) },
    colors: ['#2563eb'],
    legend: { show: false },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
}));

const hasChartData = computed(() => !!(props.byClient?.length || props.incidentsByServicePoint?.length || props.trend?.length));

const filtersOpen = ref(true);
const pdfConfirmOpen = ref(false);

const isLargeReport = computed(() => !!(props.summary && props.summary.total > 1000));

const onPdfClick = (e: MouseEvent) => {
    if (isLargeReport.value) {
        e.preventDefault();
        pdfConfirmOpen.value = true;
    }
};

const confirmPdfExport = () => {
    pdfConfirmOpen.value = false;
    window.open(`/reportes/pdf?${exportParams()}`, '_blank');
};

const hasActiveFilters = computed(() =>
    !!(clientId.value || spId.value || employeeId.value || supervisorId.value || shiftId.value
        || status.value || search.value || onlyIncidents.value || onlyPresent.value),
);

const buildParams = () => ({
    date_from: dateFrom.value || undefined,
    date_to: dateTo.value || undefined,
    client_id: clientId.value || undefined,
    service_point_id: spId.value || undefined,
    employee_id: employeeId.value || undefined,
    supervisor_id: supervisorId.value || undefined,
    shift_id: shiftId.value || undefined,
    status: status.value || undefined,
    search: search.value || undefined,
    only_incidents: onlyIncidents.value ? '1' : undefined,
    only_present: onlyPresent.value ? '1' : undefined,
});

const reload = () => {
    if (!hasValidRange.value) {
        return;
    }

    router.get('/reportes', buildParams(), { preserveState: true, replace: true });
};

let debounceTimer: ReturnType<typeof setTimeout>;
const reloadDebounced = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(reload, 400);
};

// Filtros en tiempo real: cualquier cambio recarga automáticamente en cuanto
// hay un rango de fechas válido. El texto libre lleva debounce.
watch([clientId, spId, employeeId, supervisorId, shiftId, status], reload);
watch(search, reloadDebounced);
watch([dateFrom, dateTo], () => {
    if (hasValidRange.value) {
        reload();
    }
});
watch(onlyIncidents, (v) => {
    if (v) {
onlyPresent.value = false;
}

    reload();
});
watch(onlyPresent, (v) => {
    if (v) {
onlyIncidents.value = false;
}

    reload();
});

const clearFilters = () => {
    clientId.value = '';
    spId.value = '';
    employeeId.value = '';
    supervisorId.value = '';
    shiftId.value = '';
    status.value = '';
    search.value = '';
    onlyIncidents.value = false;
    onlyPresent.value = false;
};

const exportParams = () => new URLSearchParams(
    Object.entries(buildParams()).filter(([, v]) => v !== undefined) as [string, string][],
).toString();

const onPage = (p: number) => {
    router.get('/reportes', { ...buildParams(), page: p }, { preserveState: true });
};

const columns: ColumnDef<Attendance>[] = [
    { accessorKey: 'attendance_date', header: 'Fecha' },
    { accessorKey: 'employee_number', header: 'No. Emp', cell: ({ row }) => row.original.employee?.employee_number },
    { accessorKey: 'employee', header: 'Nombre', cell: ({ row }) => `${row.original.employee?.name ?? ''} ${row.original.employee?.last_name ?? ''}` },
    { accessorKey: 'client', header: 'Empresa', cell: ({ row }) => row.original.client?.name },
    { accessorKey: 'service_point', header: 'Punto', cell: ({ row }) => row.original.service_point?.name },
    { accessorKey: 'status', header: 'Estado' },
    { accessorKey: 'schedule', header: 'Horario' },
    { accessorKey: 'supervisor', header: 'Supervisor', cell: ({ row }) => row.original.supervisor?.name },
];
</script>

<template>
    <div class="p-6">
        <PageHeader title="Reportes de Asistencia" description="Filtra y exporta reportes por rango de fechas">
            <template #actions>
                <template v-if="hasPermission('Exportar reportes') && hasValidRange">
                    <Button variant="outline" as="a" :href="`/reportes/excel?${exportParams()}`" title="Recomendado para reportes grandes">
                        <Download class="h-4 w-4 mr-2" /> Excel
                    </Button>
                    <Button
                        variant="outline"
                        as="a"
                        :href="`/reportes/pdf?${exportParams()}`"
                        target="_blank"
                        title="Recomendado para reportes ejecutivos"
                        @click="onPdfClick"
                    >
                        <FileText class="h-4 w-4 mr-2" /> PDF
                    </Button>
                </template>
            </template>
        </PageHeader>

        <!-- Filters -->
        <Collapsible v-model:open="filtersOpen" class="mb-6 rounded-lg border bg-muted/30 p-4">
            <CollapsibleTrigger class="flex w-full items-center justify-between text-sm font-semibold text-foreground">
                <span>Filtros</span>
                <ChevronDown :class="['h-4 w-4 transition-transform', filtersOpen ? 'rotate-180' : '']" />
            </CollapsibleTrigger>
            <CollapsibleContent class="mt-3 space-y-3">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <FormField label="Desde" required>
                    <DatePicker v-model="dateFrom" placeholder="Fecha inicial" />
                </FormField>
                <FormField label="Hasta" required>
                    <DatePicker v-model="dateTo" placeholder="Fecha final" />
                </FormField>
                <SearchableSelect v-model="clientId" :options="clientOptions" label="Empresa" placeholder="Todas" />
                <SearchableSelect v-model="spId" :options="servicePointOptions" label="Punto de servicio" placeholder="Todos" />
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <SearchableSelect v-model="employeeId" :options="employeeOptions" label="Colaborador" placeholder="Todos" />
                <SearchableSelect v-model="supervisorId" :options="supervisorOptions" label="Supervisor" placeholder="Todos" />
                <SearchableSelect v-model="shiftId" :options="shiftOptions" label="Turno" placeholder="Todos" />
                <SearchableSelect v-model="status" :options="statusOptions" label="Estado" placeholder="Todos" />
            </div>
            <div class="flex flex-wrap items-end gap-3">
                <FormField label="Buscar" class="flex-1 min-w-[220px]">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input v-model="search" placeholder="Nombre o número de empleado..." class="pl-9" />
                    </div>
                </FormField>
                <label class="flex items-center gap-2 pb-2 cursor-pointer">
                    <Checkbox v-model:checked="onlyIncidents" />
                    <Label class="text-sm font-normal cursor-pointer">Solo incidencias (faltas + retardos)</Label>
                </label>
                <label class="flex items-center gap-2 pb-2 cursor-pointer">
                    <Checkbox v-model:checked="onlyPresent" />
                    <Label class="text-sm font-normal cursor-pointer">Solo presentes</Label>
                </label>
                <Button v-if="hasActiveFilters" variant="ghost" size="sm" class="mb-2" @click="clearFilters">
                    <X class="h-3.5 w-3.5 mr-1" /> Limpiar filtros
                </Button>
            </div>
            </CollapsibleContent>
        </Collapsible>

        <div v-if="!hasValidRange" class="text-center py-16 text-muted-foreground border rounded-lg border-dashed">
            <BarChart3 class="h-8 w-8 mx-auto mb-2 opacity-50" />
            <p>Selecciona un rango de fechas para comenzar.</p>
            <p class="mt-1 text-xs">Usa Excel para reportes grandes · PDF recomendado para reportes ejecutivos.</p>
        </div>

        <template v-else>
            <!-- Summary cards -->
            <div v-if="summary" class="grid grid-cols-3 md:grid-cols-7 gap-3 mb-6">
                <Card class="border-0 shadow-sm text-center p-3">
                    <div class="text-xl font-bold">{{ summary.total }}</div>
                    <div class="text-xs text-muted-foreground">Total</div>
                </Card>
                <Card class="border-0 shadow-sm text-center p-3 bg-green-50 dark:bg-green-950">
                    <div class="text-xl font-bold text-green-700 dark:text-green-400">{{ summary.presente }}</div>
                    <div class="text-xs text-green-600 dark:text-green-500">Presentes</div>
                </Card>
                <Card class="border-0 shadow-sm text-center p-3 bg-red-50 dark:bg-red-950">
                    <div class="text-xl font-bold text-red-700 dark:text-red-400">{{ summary.falta }}</div>
                    <div class="text-xs text-red-600 dark:text-red-500">Faltas</div>
                </Card>
                <Card class="border-0 shadow-sm text-center p-3 bg-purple-50 dark:bg-purple-950">
                    <div class="text-xl font-bold text-purple-700 dark:text-purple-400">{{ summary.retardo }}</div>
                    <div class="text-xs text-purple-600 dark:text-purple-500">Retardos</div>
                </Card>
                <Card class="border-0 shadow-sm text-center p-3 bg-blue-50 dark:bg-blue-950">
                    <div class="text-xl font-bold text-blue-700 dark:text-blue-400">{{ summary.descanso }}</div>
                    <div class="text-xs text-blue-600 dark:text-blue-500">Descansos</div>
                </Card>
                <Card class="border-0 shadow-sm text-center p-3 bg-yellow-50 dark:bg-yellow-950">
                    <div class="text-xl font-bold text-yellow-700 dark:text-yellow-400">{{ summary.permiso }}</div>
                    <div class="text-xs text-yellow-600 dark:text-yellow-500">Permisos</div>
                </Card>
                <Card class="border-0 shadow-sm text-center p-3 bg-orange-50 dark:bg-orange-950">
                    <div class="text-xl font-bold text-orange-700 dark:text-orange-400">{{ summary.incapacidad }}</div>
                    <div class="text-xs text-orange-600 dark:text-orange-500">Incapacidades</div>
                </Card>
            </div>

            <Alert v-if="summary && summary.total > 1000 && hasPermission('Exportar reportes')" class="mb-6 border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/40">
                <AlertTriangle class="h-4 w-4 text-amber-600" />
                <AlertDescription class="text-amber-700 dark:text-amber-400">
                    El reporte tiene {{ summary.total }} registros. El PDF exporta un máximo de 1000 filas; usa Excel para obtener el reporte completo.
                </AlertDescription>
            </Alert>

            <!-- Gráficas -->
            <div v-if="hasChartData" class="grid grid-cols-1 gap-4 mb-6 lg:grid-cols-2">
                <Card class="border-0 shadow-sm p-4">
                    <h3 class="text-sm font-semibold">Distribución de estados</h3>
                    <p class="mb-2 text-xs text-muted-foreground">Proporción de cada estado en el periodo filtrado</p>
                    <AppChart type="donut" :series="statusDonutSeries" :options="statusDonutOptions" :height="280" />
                </Card>
                <Card class="border-0 shadow-sm p-4">
                    <h3 class="text-sm font-semibold">Asistencias por empresa</h3>
                    <p class="mb-2 text-xs text-muted-foreground">Total de registros por empresa en el periodo filtrado</p>
                    <AppChart type="bar" :series="byClientSeries" :options="byClientOptions" :height="280" />
                </Card>
                <Card class="border-0 shadow-sm p-4">
                    <h3 class="text-sm font-semibold">Incidencias por punto de servicio</h3>
                    <p class="mb-2 text-xs text-muted-foreground">Faltas y retardos agrupados por ubicación</p>
                    <AppChart type="bar" :series="incidentsSeries" :options="incidentsOptions" :height="280" />
                </Card>
                <Card class="border-0 shadow-sm p-4">
                    <h3 class="text-sm font-semibold">Tendencia de registros por fecha</h3>
                    <p class="mb-2 text-xs text-muted-foreground">Volumen diario de asistencias capturadas</p>
                    <AppChart type="area" :series="trendSeries" :options="trendOptions" :height="280" />
                </Card>
            </div>

            <!-- Results table -->
            <AppDataTable
                v-if="attendances"
                :columns="columns"
                :data="attendances.data"
                :pagination="attendances"
                :searchable="false"
                empty-title="Sin resultados"
                empty-description="No hay registros para los filtros seleccionados."
                @page-change="onPage"
            >
                <template #cell-attendance_date="{ value }">
                    <span class="text-sm">{{ formatShortDateMx(value as string) }}</span>
                </template>
                <template #cell-status="{ item }">
                    <StatusBadge :status="item.status" />
                </template>
                <template #cell-schedule="{ item }">
                    <span class="font-mono text-xs">{{ item.entry_time ?? '--:--' }} – {{ item.exit_time ?? '--:--' }}</span>
                </template>
            </AppDataTable>
        </template>

        <AlertDialog v-model:open="pdfConfirmOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>El PDF solo exporta 1000 registros</AlertDialogTitle>
                    <AlertDialogDescription>
                        Este reporte tiene {{ summary?.total }} registros. El PDF exportará únicamente los primeros 1000;
                        usa Excel si necesitas el reporte completo. ¿Deseas continuar con el PDF?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="pdfConfirmOpen = false">Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="confirmPdfExport">Exportar PDF de todos modos</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
