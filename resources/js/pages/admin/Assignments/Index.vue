<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, UserCheck, X } from '@lucide/vue';
import type {ColumnDef} from '@tanstack/vue-table';
import { computed, ref, watch } from 'vue';
import AppDataTable from '@/components/AppDataTable.vue';
import DeleteDialog from '@/components/DeleteDialog.vue';
import FormActions from '@/components/FormActions.vue';
import FormDialogContent from '@/components/FormDialogContent.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { usePermissions } from '@/composables/usePermissions';
import { formatDateTimeMx } from '@/lib/formatters';
import type { AppUser, Client, PaginatedData, ServicePoint, SupervisorAssignment } from '@/types/models';

type SelectModel = string | number | null;

const props = defineProps<{
    assignments: PaginatedData<SupervisorAssignment>;
    supervisors: (AppUser & { email: string })[];
    clients: Client[];
    servicePoints: (ServicePoint & { client_id: number })[];
    filters: Record<string, string | undefined>;
}>();

const { hasPermission } = usePermissions();
const showModal = ref(false);
const deleteId = ref<number | null>(null);

const form = useForm({
    supervisor_user_id: '',
    client_id: '',
    service_point_id: '',
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    form.post('/asignaciones', {
        onSuccess: () => {
 showModal.value = false; form.reset();
},
    });
};

const confirmDelete = () => {
    if (!deleteId.value) {
return;
}

    router.delete(`/asignaciones/${deleteId.value}`, { onFinish: () => {
 deleteId.value = null;
} });
};

const filteredSPs = (clientId: string) => props.servicePoints.filter((sp) => !clientId || sp.client_id === Number(clientId));

const onPage = (p: number) => router.get('/asignaciones', { ...props.filters, page: p }, { preserveState: true });

const supervisorOptions = computed(() => props.supervisors.map((s) => ({ value: s.id, label: s.name, description: s.email })));
const clientOptions = computed(() => props.clients.map((c) => ({ value: c.id, label: c.name })));

const supervisorIdModel = computed({
    get: () => (form.supervisor_user_id ? form.supervisor_user_id : null),
    set: (v: SelectModel) => {
        form.supervisor_user_id = v == null ? '' : String(v);
    },
});
const clientIdModel = computed({
    get: () => (form.client_id ? form.client_id : null),
    set: (v: SelectModel) => {
        form.client_id = v == null ? '' : String(v);
        form.service_point_id = '';
    },
});
const servicePointIdModel = computed({
    get: () => (form.service_point_id ? form.service_point_id : null),
    set: (v: SelectModel) => {
        form.service_point_id = v == null ? '' : String(v);
    },
});
const formServicePointOptions = computed(() => filteredSPs(form.client_id).map((sp) => ({ value: sp.id, label: sp.name })));

// Filtros de la tabla
const filterSupervisor = ref<SelectModel>(props.filters.supervisor_id ?? '');
const filterClient = ref<SelectModel>(props.filters.client_id ?? '');
const filterSP = ref<SelectModel>(props.filters.service_point_id ?? '');
const filterServicePointOptions = computed(() =>
    props.servicePoints
        .filter((sp) => !filterClient.value || String(sp.client_id) === String(filterClient.value))
        .map((sp) => ({ value: sp.id, label: sp.name })),
);
const hasActiveFilters = computed(() => !!(filterSupervisor.value || filterClient.value || filterSP.value));

watch([filterSupervisor, filterClient, filterSP], () => {
    router.get('/asignaciones', {
        supervisor_id: filterSupervisor.value || undefined,
        client_id: filterClient.value || undefined,
        service_point_id: filterSP.value || undefined,
    }, { preserveState: true, replace: true });
});

const clearFilters = () => {
    filterSupervisor.value = '';
    filterClient.value = '';
    filterSP.value = '';
    router.get('/asignaciones', {}, { preserveState: true, replace: true });
};

const columns: ColumnDef<SupervisorAssignment>[] = [
    { accessorKey: 'supervisor', header: 'Supervisor', cell: ({ row }) => row.original.supervisor?.name },
    { accessorKey: 'email', header: 'Email', cell: ({ row }) => row.original.supervisor?.email },
    { accessorKey: 'client', header: 'Empresa', cell: ({ row }) => row.original.client?.name },
    { accessorKey: 'service_point', header: 'Punto de Servicio' },
    { accessorKey: 'created_at', header: 'Asignado' },
];
</script>

<template>
    <div class="p-6">
        <PageHeader title="Asignación de Supervisores" description="Controla qué supervisores pueden capturar en qué empresas y puntos">
            <template #actions>
                <Button v-if="hasPermission('Crear asignaciones')" @click="openCreate">
                    <Plus class="h-4 w-4 mr-2" /> Nueva Asignación
                </Button>
            </template>
        </PageHeader>

        <AppDataTable
            :columns="columns"
            :data="assignments.data"
            :pagination="assignments"
            :searchable="false"
            empty-title="Sin asignaciones"
            empty-description="Asigna supervisores a empresas y puntos de servicio."
            :empty-icon="UserCheck"
            @page-change="onPage"
        >
            <template #filters>
                <SearchableSelect v-model="filterSupervisor" :options="supervisorOptions" placeholder="Supervisor..." class="w-52" />
                <SearchableSelect v-model="filterClient" :options="clientOptions" placeholder="Empresa..." class="w-48" />
                <SearchableSelect v-model="filterSP" :options="filterServicePointOptions" placeholder="Punto de servicio..." class="w-52" />
                <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearFilters">
                    <X class="h-3.5 w-3.5 mr-1" /> Limpiar filtros
                </Button>
            </template>
            <template #cell-service_point="{ item }">
                <span v-if="item.service_point?.name">{{ item.service_point.name }}</span>
                <span v-else class="text-muted-foreground italic">Todas las ubicaciones</span>
            </template>
            <template #cell-created_at="{ value }">
                <span class="text-xs text-muted-foreground">{{ formatDateTimeMx(value as string) }}</span>
            </template>
            <template #actions="{ item }">
                <Button
                    v-if="hasPermission('Eliminar asignaciones')"
                    variant="ghost" size="sm"
                    class="text-destructive"
                    @click="deleteId = item.id"
                >
                    <Trash2 class="h-4 w-4" />
                </Button>
            </template>
        </AppDataTable>

        <Dialog :open="showModal" @update:open="showModal = $event">
            <FormDialogContent class="max-w-md">
                <DialogHeader><DialogTitle>Nueva Asignación</DialogTitle></DialogHeader>
                <form @submit.prevent="submit" class="space-y-4">
                    <SearchableSelect
                        v-model="supervisorIdModel"
                        :options="supervisorOptions"
                        label="Supervisor"
                        required
                        placeholder="Selecciona supervisor..."
                        :clearable="false"
                        :error="form.errors.supervisor_user_id"
                    />
                    <SearchableSelect
                        v-model="clientIdModel"
                        :options="clientOptions"
                        label="Empresa"
                        required
                        placeholder="Selecciona empresa..."
                        :clearable="false"
                        :error="form.errors.client_id"
                    />
                    <SearchableSelect
                        v-model="servicePointIdModel"
                        :options="formServicePointOptions"
                        label="Punto de Servicio (opcional)"
                        :disabled="!form.client_id"
                        placeholder="Todos los puntos (sin especificar)"
                        :hint="!form.client_id ? 'Selecciona una empresa primero.' : undefined"
                    />
                    <FormActions submit-label="Asignar" processing-label="Asignando..." :processing="form.processing" @cancel="showModal = false" />
                </form>
            </FormDialogContent>
        </Dialog>

        <DeleteDialog
            :open="!!deleteId"
            title="¿Eliminar asignación?"
            description="El supervisor perderá acceso a esta empresa/ubicación."
            @update:open="deleteId = null"
            @confirm="confirmDelete"
        />
    </div>
</template>
