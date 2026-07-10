<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Pencil, Search, Trash2, X } from '@lucide/vue';
import type {ColumnDef} from '@tanstack/vue-table';
import { computed, ref, watch } from 'vue';
import AppDataTable from '@/components/AppDataTable.vue';
import DatePicker from '@/components/DatePicker.vue';
import FormActions from '@/components/FormActions.vue';
import FormDialogContent from '@/components/FormDialogContent.vue';
import FormField from '@/components/FormField.vue';
import FormTextarea from '@/components/FormTextarea.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/composables/usePermissions';
import { formatDateMx, formatShortDateMx } from '@/lib/formatters';
import type { Attendance, Client, PaginatedData, ServicePoint } from '@/types/models';

const props = defineProps<{
    attendances: PaginatedData<Attendance>;
    clients: Client[];
    servicePoints: (ServicePoint & { client_id: number })[];
    filters: Record<string, string | undefined>;
}>();

const { hasPermission } = usePermissions();
const showCorrectModal = ref(false);
const correctingAttendance = ref<Attendance | null>(null);
const deleteId = ref<number | null>(null);
const deleteReason = ref('');
const showDeleteModal = ref(false);

const correctForm = useForm({
    status: '' as Attendance['status'],
    entry_time: '',
    exit_time: '',
    notes: '',
    reason: '',
});

const openCorrect = (a: Attendance) => {
    correctingAttendance.value = a;
    correctForm.clearErrors();
    correctForm.status = a.status;
    correctForm.entry_time = a.entry_time ?? '';
    correctForm.exit_time = a.exit_time ?? '';
    correctForm.notes = a.notes ?? '';
    correctForm.reason = '';
    showCorrectModal.value = true;
};

const submitCorrect = () => {
    if (!correctingAttendance.value) {
return;
}

    correctForm.patch(`/asistencias/${correctingAttendance.value.id}/corregir`, {
        onSuccess: () => {
 showCorrectModal.value = false; 
},
    });
};

const openDelete = (id: number) => {
    deleteId.value = id;
    deleteReason.value = '';
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!deleteId.value) {
return;
}

    router.delete(`/asistencias/${deleteId.value}`, {
        data: { reason: deleteReason.value },
        onSuccess: () => {
 showDeleteModal.value = false; deleteId.value = null; 
},
    });
};

const onPage = (p: number) => router.get('/asistencias', { ...props.filters, page: p }, { preserveState: true });

type SelectModel = string | number | null;

const filterDate = ref(props.filters.date ?? '');
const filterClient = ref<SelectModel>(props.filters.client_id ?? '');
const filterSP = ref<SelectModel>(props.filters.service_point_id ?? '');
const filterStatus = ref<SelectModel>(props.filters.status ?? '');
const filterEmployeeSearch = ref(props.filters.employee_search ?? '');

const STATUSES = ['presente', 'falta', 'descanso', 'permiso', 'incapacidad', 'retardo'];
const clientOptions = computed(() => props.clients.map((c) => ({ value: c.id, label: c.name })));
const servicePointOptions = computed(() =>
    props.servicePoints
        .filter((sp) => !filterClient.value || String(sp.client_id) === String(filterClient.value))
        .map((sp) => ({ value: sp.id, label: sp.name })),
);
const statusOptions = computed(() => STATUSES.map((s) => ({ value: s, label: s.charAt(0).toUpperCase() + s.slice(1) })));

const hasActiveFilters = computed(() => !!(
    filterDate.value || filterClient.value || filterSP.value || filterStatus.value || filterEmployeeSearch.value
));

const applyFilters = () => {
    router.get('/asistencias', {
        date: filterDate.value || undefined,
        client_id: filterClient.value || undefined,
        service_point_id: filterSP.value || undefined,
        status: filterStatus.value || undefined,
        employee_search: filterEmployeeSearch.value || undefined,
    }, { preserveState: true, replace: true });
};

watch([filterDate, filterClient, filterSP, filterStatus], applyFilters);

let searchDebounce: ReturnType<typeof setTimeout>;
watch(filterEmployeeSearch, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(applyFilters, 400);
});

const clearFilters = () => {
    filterDate.value = '';
    filterClient.value = '';
    filterSP.value = '';
    filterStatus.value = '';
    filterEmployeeSearch.value = '';
    router.get('/asistencias', {}, { preserveState: true, replace: true });
};

const columns: ColumnDef<Attendance>[] = [
    { accessorKey: 'attendance_date', header: 'Fecha' },
    { accessorKey: 'employee', header: 'Colaborador' },
    { accessorKey: 'client', header: 'Empresa', cell: ({ row }) => row.original.client?.name },
    { accessorKey: 'service_point', header: 'Punto', cell: ({ row }) => row.original.service_point?.name },
    { accessorKey: 'status', header: 'Estado' },
    { accessorKey: 'schedule', header: 'Horario' },
    { accessorKey: 'supervisor', header: 'Supervisor', cell: ({ row }) => row.original.supervisor?.name },
];
</script>

<template>
    <div class="p-6">
        <PageHeader title="Gestión de Asistencias" description="Consulta y corrección de registros de asistencia" />

        <!-- Filters -->
        <div class="space-y-3 mb-6 bg-muted/30 p-4 rounded-lg border">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <FormField label="Fecha" class="text-xs">
                    <DatePicker v-model="filterDate" placeholder="Todas las fechas" />
                </FormField>
                <SearchableSelect v-model="filterClient" :options="clientOptions" label="Empresa" placeholder="Todas" />
                <SearchableSelect v-model="filterSP" :options="servicePointOptions" label="Punto de servicio" placeholder="Todos" />
                <SearchableSelect v-model="filterStatus" :options="statusOptions" label="Estado" placeholder="Todos" />
            </div>
            <div class="flex flex-wrap items-end gap-3">
                <FormField label="Buscar colaborador" class="flex-1 min-w-[220px]">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input v-model="filterEmployeeSearch" placeholder="Nombre o número de empleado..." class="pl-9" />
                    </div>
                </FormField>
                <Button v-if="hasActiveFilters" variant="ghost" size="sm" class="mb-2" @click="clearFilters">
                    <X class="h-3.5 w-3.5 mr-1" /> Limpiar filtros
                </Button>
            </div>
        </div>

        <AppDataTable
            :columns="columns"
            :data="attendances.data"
            :pagination="attendances"
            :searchable="false"
            empty-title="Sin registros"
            empty-description="No hay asistencias con los filtros seleccionados."
            @page-change="onPage"
        >
            <template #cell-attendance_date="{ value }">
                <span class="text-sm">{{ formatShortDateMx(value as string) }}</span>
            </template>
            <template #cell-employee="{ item }">
                <div class="font-medium text-sm">{{ item.employee?.name }} {{ item.employee?.last_name }}</div>
                <div class="text-xs text-muted-foreground font-mono">{{ item.employee?.employee_number }}</div>
            </template>
            <template #cell-status="{ item }">
                <StatusBadge :status="item.status" />
            </template>
            <template #cell-schedule="{ item }">
                <span class="text-xs font-mono">{{ item.entry_time ?? '--:--' }} – {{ item.exit_time ?? '--:--' }}</span>
            </template>
            <template #actions="{ item }">
                <Button v-if="hasPermission('Corregir asistencias')" variant="ghost" size="sm" @click="openCorrect(item)">
                    <Pencil class="h-3.5 w-3.5" />
                </Button>
                <Button v-if="hasPermission('Eliminar asistencias')" variant="ghost" size="sm" class="text-destructive" @click="openDelete(item.id)">
                    <Trash2 class="h-3.5 w-3.5" />
                </Button>
            </template>
        </AppDataTable>

        <!-- Correct modal -->
        <Dialog :open="showCorrectModal" @update:open="showCorrectModal = $event">
            <FormDialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Corregir Asistencia</DialogTitle>
                    <DialogDescription>
                        {{ correctingAttendance?.employee?.name }} {{ correctingAttendance?.employee?.last_name }} — {{ formatDateMx(correctingAttendance?.attendance_date) }}
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitCorrect" class="space-y-4">
                    <FormField label="Estado" required :error="correctForm.errors.status">
                        <Select v-model="correctForm.status">
                            <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="s in STATUSES" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </FormField>
                    <div class="grid grid-cols-2 gap-3">
                        <FormField label="Entrada">
                            <Input type="time" v-model="correctForm.entry_time" />
                        </FormField>
                        <FormField label="Salida">
                            <Input type="time" v-model="correctForm.exit_time" />
                        </FormField>
                    </div>
                    <FormTextarea v-model="correctForm.notes" label="Notas" :rows="2" :error="correctForm.errors.notes" />
                    <FormTextarea
                        v-model="correctForm.reason"
                        label="Motivo de corrección"
                        required
                        :rows="3"
                        placeholder="Describe el motivo de la corrección (mínimo 10 caracteres)..."
                        :error="correctForm.errors.reason"
                    />
                    <FormActions submit-label="Guardar Corrección" :processing="correctForm.processing" @cancel="showCorrectModal = false" />
                </form>
            </FormDialogContent>
        </Dialog>

        <!-- Delete modal with reason -->
        <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event">
            <FormDialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>¿Eliminar asistencia?</DialogTitle>
                    <DialogDescription>Esta acción registrará la eliminación en auditoría. Ingresa el motivo obligatorio.</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <FormField label="Motivo de eliminación" required hint="Mínimo 10 caracteres.">
                        <Textarea v-model="deleteReason" :rows="3" placeholder="Describe el motivo..." />
                    </FormField>
                </div>
                <div class="flex justify-end gap-2">
                    <Button variant="outline" @click="showDeleteModal = false">Cancelar</Button>
                    <Button
                        variant="destructive"
                        :disabled="deleteReason.length < 10"
                        @click="confirmDelete"
                    >
                        Eliminar
                    </Button>
                </div>
            </FormDialogContent>
        </Dialog>
    </div>
</template>
