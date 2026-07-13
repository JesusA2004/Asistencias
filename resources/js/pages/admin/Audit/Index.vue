<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Eye, History, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AppPagination from '@/components/AppPagination.vue';
import DatePicker from '@/components/DatePicker.vue';
import EmptyState from '@/components/EmptyState.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { useInertiaLoading } from '@/composables/useInertiaLoading';
import { formatDateMx, formatDateTimeMx } from '@/lib/formatters';
import { auditActionVisual } from '@/lib/status';
import type { AttendanceAudit, PaginatedData } from '@/types/models';

type SelectModel = string | number | null;

const props = defineProps<{
    audits: PaginatedData<AttendanceAudit>;
    users: { id: number; name: string }[];
    filters: Record<string, string | undefined>;
}>();

const { isLoading } = useInertiaLoading();

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

/** Línea corta tipo "Estado: falta → presente, Entrada: — → 07:00". */
const changeSummary = (audit: AttendanceAudit): string => {
    const keys = diffKeys(audit);

    if (!keys.length) {
        return audit.reason ?? 'Sin cambios de campo registrados.';
    }

    const old = (audit.old_values ?? {}) as Record<string, unknown>;
    const fresh = (audit.new_values ?? {}) as Record<string, unknown>;

    return keys
        .slice(0, 3)
        .map((key) => `${FIELD_LABELS[key] ?? key}: ${formatFieldValue(key, old[key])} → ${formatFieldValue(key, fresh[key])}`)
        .join(' · ');
};
</script>

<template>
    <div class="w-full p-6">
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

        <template v-if="isLoading">
            <div class="space-y-3">
                <div v-for="i in 5" :key="i" class="rounded-xl border bg-card p-4 space-y-2">
                    <Skeleton class="h-4 w-1/3" />
                    <Skeleton class="h-3 w-2/3" />
                </div>
            </div>
        </template>

        <EmptyState
            v-else-if="!audits.data.length"
            title="Sin registros de auditoría"
            description="No hay cambios registrados para los filtros aplicados."
            :icon="History"
        />

        <template v-else>
            <div class="space-y-3">
                <div
                    v-for="audit in audits.data"
                    :key="audit.id"
                    class="rounded-xl border bg-card p-4 shadow-sm transition-all hover:shadow-md hover:border-primary/30"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1 space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge variant="outline" :class="['text-xs', auditActionVisual(audit.action).badgeClass]">
                                    {{ auditActionVisual(audit.action).label }}
                                </Badge>
                                <span class="text-xs text-muted-foreground whitespace-nowrap">{{ formatDateTimeMx(audit.created_at) }}</span>
                            </div>
                            <div class="text-sm">
                                <span class="font-medium">{{ audit.attendance?.employee?.name }} {{ audit.attendance?.employee?.last_name }}</span>
                                <span class="font-mono text-xs text-muted-foreground ml-2">{{ audit.attendance?.employee?.employee_number }}</span>
                                <span v-if="audit.attendance?.client?.name" class="text-xs text-muted-foreground"> · {{ audit.attendance.client.name }}</span>
                            </div>
                            <p class="text-sm text-muted-foreground truncate">{{ changeSummary(audit) }}</p>
                            <p class="text-xs text-muted-foreground">
                                Realizó: <span class="font-medium text-foreground">{{ audit.changer?.name ?? '—' }}</span>
                                <template v-if="audit.reason"> · Motivo: {{ audit.reason }}</template>
                            </p>
                        </div>
                        <Button
                            v-if="audit.old_values || audit.new_values"
                            variant="ghost"
                            size="sm"
                            class="shrink-0"
                            @click="openDetails(audit)"
                        >
                            <Eye class="h-4 w-4 mr-1.5" /> Ver detalle
                        </Button>
                    </div>
                </div>
            </div>

            <AppPagination class="mt-4" :pagination="audits" @page-change="onPage" />
        </template>

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
