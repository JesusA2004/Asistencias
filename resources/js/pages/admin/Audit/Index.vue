<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Eye, History, X } from '@lucide/vue';
import type {ColumnDef} from '@tanstack/vue-table';
import { computed, ref, watch } from 'vue';
import AppDataTable from '@/components/AppDataTable.vue';
import DatePicker from '@/components/DatePicker.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { formatDateMx, formatDateTimeMx } from '@/lib/formatters';
import type { AttendanceAudit, PaginatedData } from '@/types/models';

type SelectModel = string | number | null;

const props = defineProps<{
    audits: PaginatedData<AttendanceAudit>;
    users: { id: number; name: string }[];
    filters: Record<string, string | undefined>;
}>();

const filterAction = ref<SelectModel>(props.filters.action ?? '');
const filterChangedBy = ref<SelectModel>(props.filters.changed_by ?? '');
const filterFrom = ref(props.filters.date_from ?? '');
const filterTo = ref(props.filters.date_to ?? '');

const actionOptions = [
    { value: 'creado', label: 'Creado' },
    { value: 'actualizado', label: 'Actualizado' },
    { value: 'corregido', label: 'Corregido' },
    { value: 'eliminado', label: 'Eliminado' },
];
const userOptions = computed(() => props.users.map((u) => ({ value: u.id, label: u.name })));

const hasActiveFilters = computed(() => !!(
    filterAction.value || filterChangedBy.value || filterFrom.value || filterTo.value
));

const applyFilters = () => {
    router.get('/auditoria', {
        action: filterAction.value || undefined,
        changed_by: filterChangedBy.value || undefined,
        date_from: filterFrom.value || undefined,
        date_to: filterTo.value || undefined,
    }, { preserveState: true, replace: true });
};

watch([filterAction, filterChangedBy, filterFrom, filterTo], applyFilters);

const clearFilters = () => {
    filterAction.value = '';
    filterChangedBy.value = '';
    filterFrom.value = '';
    filterTo.value = '';
    router.get('/auditoria', {}, { preserveState: true, replace: true });
};

const onPage = (p: number) => router.get('/auditoria', { ...props.filters, page: p }, { preserveState: true });

const actionColors: Record<string, string> = {
    creado: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-400',
    actualizado: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950 dark:text-blue-400',
    corregido: 'bg-yellow-100 text-yellow-700 border-yellow-200 dark:bg-yellow-950 dark:text-yellow-400',
    eliminado: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-950 dark:text-red-400',
};

const viewingAudit = ref<AttendanceAudit | null>(null);
const showDetails = ref(false);

const openDetails = (audit: AttendanceAudit) => {
    viewingAudit.value = audit;
    showDetails.value = true;
};

const FIELD_LABELS: Record<string, string> = {
    status: 'Estado',
    entry_time: 'Entrada',
    exit_time: 'Salida',
    notes: 'Notas',
    attendance_date: 'Fecha',
};

const diffKeys = (audit: AttendanceAudit | null) => {
    if (!audit) {
return [];
}

    const old = (audit.old_values ?? {}) as Record<string, unknown>;
    const fresh = (audit.new_values ?? {}) as Record<string, unknown>;

    return Array.from(new Set([...Object.keys(old), ...Object.keys(fresh)]));
};

const formatFieldValue = (key: string, value: unknown): string => {
    if (value === undefined || value === null || value === '') {
        return '—';
    }

    if (key === 'attendance_date') {
        return formatDateMx(String(value));
    }

    return String(value);
};

const columns: ColumnDef<AttendanceAudit>[] = [
    { accessorKey: 'created_at', header: 'Fecha/Hora' },
    { accessorKey: 'action', header: 'Acción' },
    { accessorKey: 'employee', header: 'Colaborador' },
    { accessorKey: 'client', header: 'Empresa', cell: ({ row }) => row.original.attendance?.client?.name ?? '—' },
    { accessorKey: 'changer', header: 'Realizó', cell: ({ row }) => row.original.changer?.name ?? '—' },
    { accessorKey: 'reason', header: 'Motivo', cell: ({ getValue }) => getValue() || '—' },
];
</script>

<template>
    <div class="p-6">
        <PageHeader title="Auditoría" description="Registro de todas las acciones realizadas sobre asistencias" />

        <!-- Filters -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6 bg-muted/30 p-4 rounded-lg border">
            <SearchableSelect v-model="filterAction" :options="actionOptions" label="Acción" placeholder="Todas" />
            <SearchableSelect v-model="filterChangedBy" :options="userOptions" label="Usuario" placeholder="Todos" />
            <FormField label="Desde">
                <DatePicker v-model="filterFrom" placeholder="Todas las fechas" />
            </FormField>
            <FormField label="Hasta">
                <DatePicker v-model="filterTo" placeholder="Todas las fechas" />
            </FormField>
            <div class="flex items-end">
                <Button v-if="hasActiveFilters" variant="ghost" size="sm" class="w-full" @click="clearFilters">
                    <X class="h-3.5 w-3.5 mr-1" /> Limpiar filtros
                </Button>
            </div>
        </div>

        <AppDataTable
            :columns="columns"
            :data="audits.data"
            :pagination="audits"
            :searchable="false"
            empty-title="Sin registros de auditoría"
            empty-description="No hay cambios registrados para los filtros aplicados."
            :empty-icon="History"
            @page-change="onPage"
        >
            <template #cell-created_at="{ value }">
                <span class="text-xs whitespace-nowrap">{{ formatDateTimeMx(value as string) }}</span>
            </template>
            <template #cell-action="{ item }">
                <Badge variant="outline" :class="['text-xs', actionColors[item.action] ?? '']">
                    {{ item.action }}
                </Badge>
            </template>
            <template #cell-employee="{ item }">
                <div class="text-sm">{{ item.attendance?.employee?.name }} {{ item.attendance?.employee?.last_name }}</div>
                <div class="text-xs text-muted-foreground font-mono">{{ item.attendance?.employee?.employee_number }}</div>
            </template>
            <template #actions="{ item }">
                <Button
                    v-if="item.old_values || item.new_values"
                    variant="ghost"
                    size="sm"
                    @click="openDetails(item)"
                >
                    <Eye class="h-4 w-4 mr-1.5" /> Ver cambios
                </Button>
            </template>
        </AppDataTable>

        <Sheet :open="showDetails" @update:open="showDetails = $event">
            <SheetContent class="sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>Detalle de auditoría</SheetTitle>
                    <SheetDescription>
                        {{ viewingAudit?.attendance?.employee?.name }} {{ viewingAudit?.attendance?.employee?.last_name }} — {{ formatDateTimeMx(viewingAudit?.created_at) }}
                    </SheetDescription>
                </SheetHeader>
                <div class="px-4 pb-4 space-y-4">
                    <div v-if="viewingAudit?.reason">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">Motivo</p>
                        <p class="text-sm">{{ viewingAudit.reason }}</p>
                    </div>
                    <Separator v-if="viewingAudit?.reason" />
                    <div class="space-y-3">
                        <div v-for="key in diffKeys(viewingAudit)" :key="key" class="text-sm">
                            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">
                                {{ FIELD_LABELS[key] ?? key }}
                            </p>
                            <div class="flex items-center gap-2 flex-wrap">
                                <Badge v-if="(viewingAudit?.old_values as Record<string, unknown>)?.[key] !== undefined" variant="outline" class="text-red-600 border-red-200 dark:text-red-400">
                                    {{ formatFieldValue(key, (viewingAudit?.old_values as Record<string, unknown>)?.[key]) }}
                                </Badge>
                                <span v-if="(viewingAudit?.old_values as Record<string, unknown>)?.[key] !== undefined && (viewingAudit?.new_values as Record<string, unknown>)?.[key] !== undefined" class="text-muted-foreground text-xs">→</span>
                                <Badge v-if="(viewingAudit?.new_values as Record<string, unknown>)?.[key] !== undefined" variant="outline" class="text-green-600 border-green-200 dark:text-green-400">
                                    {{ formatFieldValue(key, (viewingAudit?.new_values as Record<string, unknown>)?.[key]) }}
                                </Badge>
                            </div>
                        </div>
                        <p v-if="!diffKeys(viewingAudit).length" class="text-sm text-muted-foreground">Sin cambios registrados.</p>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    </div>
</template>
