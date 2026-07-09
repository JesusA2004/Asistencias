<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Eye, History } from '@lucide/vue';
import type {ColumnDef} from '@tanstack/vue-table';
import { ref } from 'vue';
import AppDataTable from '@/components/AppDataTable.vue';
import DatePicker from '@/components/DatePicker.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import type { AttendanceAudit, PaginatedData } from '@/types/models';

const props = defineProps<{
    audits: PaginatedData<AttendanceAudit>;
    filters: Record<string, string | undefined>;
}>();

const filterAction = ref(props.filters.action ?? '');
const filterFrom = ref(props.filters.date_from ?? '');
const filterTo = ref(props.filters.date_to ?? '');

const applyFilters = () => {
    router.get('/auditoria', {
        action: filterAction.value || undefined,
        date_from: filterFrom.value || undefined,
        date_to: filterTo.value || undefined,
    }, { preserveState: true });
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
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6 bg-muted/30 p-4 rounded-lg border">
            <FormField label="Acción">
                <Select :model-value="filterAction || '__all__'" @update:model-value="(v) => { filterAction = v === '__all__' ? '' : String(v); applyFilters(); }">
                    <SelectTrigger class="w-full"><SelectValue placeholder="Todas" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="__all__">Todas</SelectItem>
                        <SelectItem value="creado">Creado</SelectItem>
                        <SelectItem value="actualizado">Actualizado</SelectItem>
                        <SelectItem value="corregido">Corregido</SelectItem>
                        <SelectItem value="eliminado">Eliminado</SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
            <FormField label="Desde">
                <DatePicker v-model="filterFrom" placeholder="Todas las fechas" @update:model-value="applyFilters" />
            </FormField>
            <FormField label="Hasta">
                <DatePicker v-model="filterTo" placeholder="Todas las fechas" @update:model-value="applyFilters" />
            </FormField>
            <div class="flex items-end">
                <Button variant="outline" size="sm" class="w-full" @click="applyFilters">Filtrar</Button>
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
                <span class="font-mono text-xs whitespace-nowrap">{{ value }}</span>
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
                        {{ viewingAudit?.attendance?.employee?.name }} {{ viewingAudit?.attendance?.employee?.last_name }} — {{ viewingAudit?.created_at }}
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
                                    {{ (viewingAudit?.old_values as Record<string, unknown>)?.[key] ?? '—' }}
                                </Badge>
                                <span v-if="(viewingAudit?.old_values as Record<string, unknown>)?.[key] !== undefined && (viewingAudit?.new_values as Record<string, unknown>)?.[key] !== undefined" class="text-muted-foreground text-xs">→</span>
                                <Badge v-if="(viewingAudit?.new_values as Record<string, unknown>)?.[key] !== undefined" variant="outline" class="text-green-600 border-green-200 dark:text-green-400">
                                    {{ (viewingAudit?.new_values as Record<string, unknown>)?.[key] ?? '—' }}
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
