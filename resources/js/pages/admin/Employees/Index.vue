<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { HardHat, Pencil, Plus, Trash2, Upload, X } from '@lucide/vue';
import type {ColumnDef} from '@tanstack/vue-table';
import { computed, ref, watch } from 'vue';
import AppDataTable from '@/components/AppDataTable.vue';
import DeleteDialog from '@/components/DeleteDialog.vue';
import FormActions from '@/components/FormActions.vue';
import FormDialogContent from '@/components/FormDialogContent.vue';
import FormField from '@/components/FormField.vue';
import FormInput from '@/components/FormInput.vue';
import FormSelect from '@/components/FormSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { SelectItem } from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import type { Client, Employee, PaginatedData, ServicePoint, Shift } from '@/types/models';

const props = defineProps<{
    employees: PaginatedData<Employee>;
    clients: Client[];
    servicePoints: ServicePoint[];
    shifts: Shift[];
    supervisors: { id: number; name: string }[];
    countsByClient: { client_id: number; client_name: string; total: number }[];
    countsByServicePoint: { service_point_id: number; service_point_name: string; total: number }[];
    filters: Record<string, string | undefined>;
}>();

const { hasPermission } = usePermissions();
const showModal = ref(false);
const showImportModal = ref(false);
const deleteId = ref<number | null>(null);
const editingEmployee = ref<Employee | null>(null);

const importForm = useForm<{ file: File | null }>({
    file: null,
});

const form = useForm({
    employee_number: '',
    name: '',
    last_name: '',
    second_last_name: '',
    email: '',
    phone: '',
    status: 'activo' as Employee['status'],
    client_id: '__none__',
    service_point_id: '__none__',
    shift_id: '__none__',
});

// Al cambiar de empresa, el punto de servicio seleccionado ya no aplica.
watch(
    () => form.client_id,
    (newClientId, oldClientId) => {
        if (newClientId === oldClientId) {
return;
}

        form.service_point_id = '__none__';
    },
);

const openCreate = () => {
    editingEmployee.value = null;
    form.reset();
    form.clearErrors();
    form.status = 'activo';
    form.client_id = '__none__';
    form.service_point_id = '__none__';
    form.shift_id = '__none__';
    showModal.value = true;
};

const openEdit = (emp: Employee) => {
    editingEmployee.value = emp;
    form.clearErrors();
    form.employee_number = emp.employee_number;
    form.name = emp.name;
    form.last_name = emp.last_name;
    form.second_last_name = emp.second_last_name ?? '';
    form.email = emp.email ?? '';
    form.phone = emp.phone ?? '';
    form.status = emp.status;
    form.client_id = emp.client_id ? String(emp.client_id) : '__none__';
    form.service_point_id = emp.service_point_id ? String(emp.service_point_id) : '__none__';
    form.shift_id = emp.shift_id ? String(emp.shift_id) : '__none__';
    showModal.value = true;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        client_id: data.client_id === '__none__' ? null : data.client_id,
        service_point_id: data.service_point_id === '__none__' ? null : data.service_point_id,
        shift_id: data.shift_id === '__none__' ? null : data.shift_id,
    }));

    if (editingEmployee.value) {
        form.put(`/colaboradores/${editingEmployee.value.id}`, {
            onSuccess: () => {
 showModal.value = false; 
},
        });
    } else {
        form.post('/colaboradores', {
            onSuccess: () => {
 showModal.value = false; form.reset(); form.status = 'activo'; 
},
        });
    }
};

const confirmDelete = () => {
    if (!deleteId.value) {
return;
}

    router.delete(`/colaboradores/${deleteId.value}`, { onFinish: () => {
 deleteId.value = null; 
} });
};

const openImport = () => {
    importForm.reset();
    importForm.clearErrors();
    showImportModal.value = true;
};

const onFileChange = (e: Event) => {
    importForm.file = (e.target as HTMLInputElement).files?.[0] ?? null;
};

const submitImport = () => {
    importForm.post('/colaboradores/importar', {
        forceFormData: true,
        onSuccess: () => {
            // El toast/alerta de éxito o error ya lo dispara el handler global de flash.
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const onSearch = (q: string) => router.get('/colaboradores', { ...props.filters, search: q }, { preserveState: true, replace: true });
const onPage = (p: number) => router.get('/colaboradores', { ...props.filters, page: p }, { preserveState: true });

const filteredSPs = (clientId: string) => props.servicePoints.filter((sp) => clientId === '__none__' || sp.client_id === Number(clientId));

const clientOptions = computed(() => props.clients.map((c) => ({ value: c.id, label: c.name })));
const shiftOptions = computed(() => props.shifts.map((s) => ({ value: s.id, label: s.name })));
const formServicePointOptions = computed(() => filteredSPs(form.client_id).map((sp) => ({ value: sp.id, label: sp.name })));

const clientIdModel = computed({
    get: () => (form.client_id === '__none__' ? null : form.client_id),
    set: (v: string | number | null) => {
        form.client_id = v == null ? '__none__' : String(v);
    },
});
const servicePointIdModel = computed({
    get: () => (form.service_point_id === '__none__' ? null : form.service_point_id),
    set: (v: string | number | null) => {
        form.service_point_id = v == null ? '__none__' : String(v);
    },
});
const shiftIdModel = computed({
    get: () => (form.shift_id === '__none__' ? null : form.shift_id),
    set: (v: string | number | null) => {
        form.shift_id = v == null ? '__none__' : String(v);
    },
});

const makeFilter = (key: string) => computed({
    get: () => props.filters[key] ?? null,
    set: (v: string | number | null) => {
        router.get('/colaboradores', { ...props.filters, [key]: v == null ? undefined : String(v) }, { preserveState: true, replace: true });
    },
});

const filterClientId = makeFilter('client_id');
const filterServicePointId = makeFilter('service_point_id');
const filterShiftId = makeFilter('shift_id');
const filterStatus = makeFilter('status');
const filterSupervisorId = makeFilter('supervisor_id');
const filterHasUser = makeFilter('has_user');
const filterHasRecentAttendance = makeFilter('has_recent_attendance');

const statusFilterOptions = [
    { value: 'activo', label: 'Activo' },
    { value: 'inactivo', label: 'Inactivo' },
    { value: 'baja', label: 'Baja' },
];
const linkedUserOptions = [
    { value: 'con', label: 'Con usuario vinculado' },
    { value: 'sin', label: 'Sin usuario vinculado' },
];
const recentAttendanceOptions = [
    { value: 'con', label: 'Con asistencias recientes' },
    { value: 'sin', label: 'Sin asistencias recientes' },
];
const servicePointFilterOptions = computed(() =>
    props.servicePoints
        .filter((sp) => !filterClientId.value || String(sp.client_id) === String(filterClientId.value))
        .map((sp) => ({ value: sp.id, label: sp.name })),
);
const supervisorFilterOptions = computed(() => props.supervisors.map((s) => ({ value: s.id, label: s.name })));

const hasActiveFilters = computed(() => !!(
    props.filters.search || props.filters.client_id || props.filters.service_point_id ||
    props.filters.shift_id || props.filters.status || props.filters.supervisor_id ||
    props.filters.has_user || props.filters.has_recent_attendance
));
const clearFilters = () => router.get('/colaboradores', {}, { preserveState: true, replace: true });

const columns: ColumnDef<Employee>[] = [
    { accessorKey: 'employee_number', header: 'No. Emp' },
    { accessorKey: 'name', header: 'Nombre' },
    { accessorKey: 'client', header: 'Empresa', cell: ({ row }) => row.original.client?.name ?? '—' },
    { accessorKey: 'service_point', header: 'Punto Servicio', cell: ({ row }) => row.original.service_point?.name ?? '—' },
    { accessorKey: 'shift', header: 'Turno', cell: ({ row }) => row.original.shift?.name ?? '—' },
    { accessorKey: 'status', header: 'Estado' },
];
</script>

<template>
    <div class="p-6">
        <PageHeader title="Colaboradores" description="Administra el personal operativo: datos, empresa, punto y turno asignado.">
            <template #actions>
                <Button v-if="hasPermission('Importar colaboradores')" variant="outline" @click="openImport">
                    <Upload class="h-4 w-4 mr-2" /> Importar colaboradores
                </Button>
                <Button v-if="hasPermission('Crear colaboradores')" @click="openCreate">
                    <Plus class="h-4 w-4 mr-2" /> Nuevo Colaborador
                </Button>
            </template>
        </PageHeader>

        <!-- Conteos por empresa/punto -->
        <div v-if="countsByClient.length" class="mb-2 flex flex-wrap gap-2">
            <button
                v-for="c in countsByClient"
                :key="c.client_id"
                type="button"
                class="rounded-full border bg-muted/30 px-3 py-1 text-xs font-medium transition-colors hover:bg-muted"
                @click="filterClientId = String(c.client_id)"
            >
                {{ c.client_name }} <span class="text-muted-foreground">({{ c.total }})</span>
            </button>
        </div>
        <div v-if="countsByServicePoint.length" class="mb-4 flex flex-wrap gap-2">
            <button
                v-for="c in countsByServicePoint"
                :key="c.service_point_id"
                type="button"
                class="rounded-full border border-dashed bg-muted/10 px-3 py-1 text-xs font-normal text-muted-foreground transition-colors hover:bg-muted"
                @click="filterServicePointId = String(c.service_point_id)"
            >
                {{ c.service_point_name }} <span>({{ c.total }})</span>
            </button>
        </div>

        <AppDataTable
            :columns="columns"
            :data="employees.data"
            :pagination="employees"
            search-placeholder="Buscar por nombre o número..."
            empty-title="Sin colaboradores"
            empty-description="No hay colaboradores con los filtros aplicados."
            :empty-icon="HardHat"
            @search="onSearch"
            @page-change="onPage"
        >
            <template #filters>
                <SearchableSelect v-model="filterClientId" :options="clientOptions" placeholder="Empresa..." class="w-44" />
                <SearchableSelect v-model="filterServicePointId" :options="servicePointFilterOptions" placeholder="Punto de servicio..." class="w-48" />
                <SearchableSelect v-model="filterShiftId" :options="shiftOptions" placeholder="Turno..." class="w-40" />
                <SearchableSelect v-model="filterSupervisorId" :options="supervisorFilterOptions" placeholder="Supervisor..." class="w-44" />
                <SearchableSelect v-model="filterStatus" :options="statusFilterOptions" placeholder="Estado..." class="w-36" />
                <SearchableSelect v-model="filterHasUser" :options="linkedUserOptions" placeholder="Usuario vinculado..." class="w-56" />
                <SearchableSelect v-model="filterHasRecentAttendance" :options="recentAttendanceOptions" placeholder="Asistencias recientes..." class="w-56" />
                <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearFilters">
                    <X class="h-3.5 w-3.5 mr-1" /> Limpiar filtros
                </Button>
            </template>
            <template #cell-name="{ item }">
                <div class="font-medium">{{ item.name }} {{ item.last_name }}</div>
                <div v-if="item.second_last_name" class="text-xs text-muted-foreground">{{ item.second_last_name }}</div>
            </template>
            <template #cell-status="{ item }">
                <StatusBadge :status="item.status" />
            </template>
            <template #actions="{ item }">
                <Button v-if="hasPermission('Editar colaboradores')" variant="ghost" size="sm" @click="openEdit(item)">
                    <Pencil class="h-4 w-4" />
                </Button>
                <Button v-if="hasPermission('Eliminar colaboradores')" variant="ghost" size="sm" class="text-destructive" @click="deleteId = item.id">
                    <Trash2 class="h-4 w-4" />
                </Button>
            </template>
        </AppDataTable>

        <!-- Modal -->
        <Dialog :open="showModal" @update:open="showModal = $event">
            <FormDialogContent class="max-w-lg max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{ editingEmployee ? 'Editar Colaborador' : 'Nuevo Colaborador' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submit" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <FormInput class="col-span-2" v-model="form.employee_number" label="No. Empleado" required placeholder="EMP-001" :error="form.errors.employee_number" />
                        <FormInput v-model="form.name" label="Nombre" required placeholder="Nombre" :error="form.errors.name" />
                        <FormInput v-model="form.last_name" label="Apellido Paterno" required placeholder="Apellido" :error="form.errors.last_name" />
                        <FormInput class="col-span-2" v-model="form.second_last_name" label="Apellido Materno" placeholder="Apellido materno (opcional)" :error="form.errors.second_last_name" />
                        <FormInput v-model="form.email" type="email" label="Email" :error="form.errors.email" />
                        <FormInput v-model="form.phone" label="Teléfono" :error="form.errors.phone" />
                        <FormSelect v-model="form.status" label="Estado" required :error="form.errors.status">
                            <SelectItem value="activo">Activo</SelectItem>
                            <SelectItem value="inactivo">Inactivo</SelectItem>
                            <SelectItem value="baja">Baja</SelectItem>
                        </FormSelect>
                        <SearchableSelect
                            v-model="clientIdModel"
                            :options="clientOptions"
                            label="Empresa"
                            placeholder="Sin asignar"
                            :error="form.errors.client_id"
                        />
                        <SearchableSelect
                            v-model="servicePointIdModel"
                            :options="formServicePointOptions"
                            label="Punto de Servicio"
                            placeholder="Sin asignar"
                            :disabled="form.client_id === '__none__'"
                            :hint="form.client_id === '__none__' ? 'Selecciona una empresa primero.' : undefined"
                            :error="form.errors.service_point_id"
                        />
                        <SearchableSelect
                            v-model="shiftIdModel"
                            :options="shiftOptions"
                            label="Turno"
                            placeholder="Sin turno"
                            :error="form.errors.shift_id"
                        />
                    </div>
                    <FormActions :processing="form.processing" @cancel="showModal = false" />
                </form>
            </FormDialogContent>
        </Dialog>

        <DeleteDialog
            :open="!!deleteId"
            title="¿Eliminar colaborador?"
            description="El colaborador será eliminado del sistema (soft delete). Sus registros de asistencia se conservan."
            @update:open="deleteId = null"
            @confirm="confirmDelete"
        />

        <!-- Import modal -->
        <Dialog :open="showImportModal" @update:open="showImportModal = $event">
            <FormDialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Importar colaboradores</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submitImport" class="space-y-3">
                    <p class="text-sm text-muted-foreground">
                        Sube un archivo Excel (.xlsx, .xls o .csv) con las columnas:
                        <code class="text-xs">employee_number, name, last_name, second_last_name, email, phone, status, client_id, service_point_id, shift_id</code>.
                    </p>
                    <FormField label="Archivo" required :error="importForm.errors.file">
                        <Input type="file" accept=".xlsx,.xls,.csv" @change="onFileChange" />
                    </FormField>
                    <FormActions submit-label="Importar" processing-label="Importando..." :processing="importForm.processing" :disabled="!importForm.file" @cancel="showImportModal = false" />
                </form>
            </FormDialogContent>
        </Dialog>
    </div>
</template>
