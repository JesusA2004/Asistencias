<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { BarChart3, Download, FileText } from '@lucide/vue';
import type {ColumnDef} from '@tanstack/vue-table';
import { ref } from 'vue';
import AppDataTable from '@/components/AppDataTable.vue';
import DatePicker from '@/components/DatePicker.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import type { Attendance, Client, PaginatedData, ServicePoint } from '@/types/models';

const props = defineProps<{
    attendances: PaginatedData<Attendance> | null;
    summary: { total: number; presente: number; falta: number; retardo: number; descanso: number; permiso: number; incapacidad: number } | null;
    clients: Client[];
    servicePoints: ServicePoint[];
    filters: Record<string, string | undefined>;
}>();

const { hasPermission } = usePermissions();

const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const clientId = ref(props.filters.client_id ?? '');
const spId = ref(props.filters.service_point_id ?? '');
const status = ref(props.filters.status ?? '');

const search = () => {
    router.get('/reportes', {
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        client_id: clientId.value || undefined,
        service_point_id: spId.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true });
};

const exportParams = () => new URLSearchParams({
    date_from: dateFrom.value,
    date_to: dateTo.value,
    ...(clientId.value && { client_id: clientId.value }),
    ...(spId.value && { service_point_id: spId.value }),
    ...(status.value && { status: status.value }),
}).toString();

const onPage = (p: number) => {
    router.get('/reportes', { ...props.filters, page: p }, { preserveState: true });
};

const STATUSES = ['presente', 'falta', 'descanso', 'permiso', 'incapacidad', 'retardo'];

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
        <PageHeader title="Reportes de Asistencia" description="Genera y exporta reportes por rango de fechas">
            <template #actions>
                <template v-if="hasPermission('Exportar reportes') && dateFrom && dateTo">
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
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6 bg-muted/30 p-4 rounded-lg border">
            <FormField label="Desde" required>
                <DatePicker v-model="dateFrom" placeholder="Fecha inicial" />
            </FormField>
            <FormField label="Hasta" required>
                <DatePicker v-model="dateTo" placeholder="Fecha final" />
            </FormField>
            <FormField label="Empresa">
                <Select :model-value="clientId || '__all__'" @update:model-value="(v) => clientId = v === '__all__' ? '' : String(v)">
                    <SelectTrigger class="w-full"><SelectValue placeholder="Todas" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="__all__">Todas</SelectItem>
                        <SelectItem v-for="c in clients" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
            <FormField label="Estado">
                <Select :model-value="status || '__all__'" @update:model-value="(v) => status = v === '__all__' ? '' : String(v)">
                    <SelectTrigger class="w-full"><SelectValue placeholder="Todos" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="__all__">Todos</SelectItem>
                        <SelectItem v-for="s in STATUSES" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
            <div class="flex items-end">
                <Button class="w-full" :disabled="!dateFrom || !dateTo" @click="search">
                    <BarChart3 class="h-4 w-4 mr-2" /> Generar
                </Button>
            </div>
        </div>

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
            <template #cell-status="{ item }">
                <StatusBadge :status="item.status" />
            </template>
            <template #cell-schedule="{ item }">
                <span class="font-mono text-xs">{{ item.entry_time ?? '--:--' }} – {{ item.exit_time ?? '--:--' }}</span>
            </template>
        </AppDataTable>
    </div>
</template>
