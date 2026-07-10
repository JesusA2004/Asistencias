<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { BarChart3, Download, FileText, Search, X } from '@lucide/vue';
import type {ColumnDef} from '@tanstack/vue-table';
import { computed, ref, watch } from 'vue';
import AppDataTable from '@/components/AppDataTable.vue';
import DatePicker from '@/components/DatePicker.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/composables/usePermissions';
import { formatShortDateMx } from '@/lib/formatters';
import type { Attendance, Client, Employee, PaginatedData, ServicePoint, Shift } from '@/types/models';

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
                    <Button variant="outline" as="a" :href="`/reportes/excel?${exportParams()}`">
                        <Download class="h-4 w-4 mr-2" /> Excel
                    </Button>
                    <Button variant="outline" as="a" :href="`/reportes/pdf?${exportParams()}`" target="_blank">
                        <FileText class="h-4 w-4 mr-2" /> PDF
                    </Button>
                </template>
            </template>
        </PageHeader>

        <!-- Filters -->
        <div class="space-y-3 mb-6 bg-muted/30 p-4 rounded-lg border">
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
        </div>

        <div v-if="!hasValidRange" class="text-center py-16 text-muted-foreground border rounded-lg border-dashed">
            <BarChart3 class="h-8 w-8 mx-auto mb-2 opacity-50" />
            Selecciona un rango de fechas para generar el reporte.
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
    </div>
</template>
