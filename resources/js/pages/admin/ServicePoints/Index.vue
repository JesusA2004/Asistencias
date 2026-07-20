<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { MapPin, Pencil, Plus, Trash2, X } from '@lucide/vue';
import type {ColumnDef} from '@tanstack/vue-table';
import { computed, ref } from 'vue';
import AppDataTable from '@/components/AppDataTable.vue';
import DeleteDialog from '@/components/DeleteDialog.vue';
import FormActions from '@/components/FormActions.vue';
import FormDialogContent from '@/components/FormDialogContent.vue';
import FormInput from '@/components/FormInput.vue';
import FormSelect from '@/components/FormSelect.vue';
import FormTextarea from '@/components/FormTextarea.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { SelectItem } from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import type { Client, PaginatedData, ServicePoint } from '@/types/models';

type ServicePointRow = ServicePoint & { employees_count: number };

const props = defineProps<{
    servicePoints: PaginatedData<ServicePointRow>;
    clients: Client[];
    filters: Record<string, string | undefined>;
}>();

const { hasPermission } = usePermissions();
const showModal = ref(false);
const deleteId = ref<number | null>(null);
const editingSP = ref<ServicePoint | null>(null);

const form = useForm({
    client_id: '',
    name: '',
    address: '',
    status: 'activo' as 'activo' | 'inactivo',
});

const openCreate = () => {
    editingSP.value = null;
    form.reset();
    form.clearErrors();
    form.status = 'activo';
    showModal.value = true;
};

const openEdit = (sp: ServicePoint) => {
    editingSP.value = sp;
    form.clearErrors();
    form.client_id = String(sp.client_id);
    form.name = sp.name;
    form.address = sp.address ?? '';
    form.status = sp.status;
    showModal.value = true;
};

const submit = () => {
    if (editingSP.value) {
        form.put(`/puntos-servicio/${editingSP.value.id}`, {
            onSuccess: () => {
 showModal.value = false; 
},
        });
    } else {
        form.post('/puntos-servicio', {
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

    router.delete(`/puntos-servicio/${deleteId.value}`, { onFinish: () => {
 deleteId.value = null; 
} });
};

const onSearch = (q: string) => router.get('/puntos-servicio', { ...props.filters, search: q }, { preserveState: true, replace: true });
const onPage = (p: number) => router.get('/puntos-servicio', { ...props.filters, page: p }, { preserveState: true });

const clientOptions = computed(() => props.clients.map((c) => ({ value: c.id, label: c.name })));
const clientIdModel = computed({
    get: () => (form.client_id ? form.client_id : null),
    set: (v: string | number | null) => {
        form.client_id = v == null ? '' : String(v);
    },
});
const filterClientId = computed({
    get: () => props.filters.client_id ?? null,
    set: (v: string | number | null) => {
        router.get('/puntos-servicio', { ...props.filters, client_id: v == null ? undefined : String(v) }, { preserveState: true, replace: true });
    },
});
const hasActiveFilters = computed(() => !!(props.filters.search || props.filters.client_id));
const clearFilters = () => router.get('/puntos-servicio', {}, { preserveState: true, replace: true });

const columns: ColumnDef<ServicePointRow>[] = [
    { accessorKey: 'name', header: 'Nombre' },
    { accessorKey: 'client', header: 'Empresa', cell: ({ row }) => row.original.client?.name ?? '—' },
    { accessorKey: 'address', header: 'Dirección', cell: ({ getValue }) => getValue() || '—' },
    { accessorKey: 'employees_count', header: 'Colaboradores' },
    { accessorKey: 'status', header: 'Estado' },
];
</script>

<template>
    <div class="p-6">
        <PageHeader title="Puntos de Servicio" description="Ubicaciones físicas de trabajo donde operan los colaboradores.">
            <template #actions>
                <Button v-if="hasPermission('Crear puntos de servicio')" @click="openCreate">
                    <Plus class="h-4 w-4 mr-2" /> Nuevo Punto
                </Button>
            </template>
        </PageHeader>

        <AppDataTable
            :columns="columns"
            :data="servicePoints.data"
            :pagination="servicePoints"
            search-placeholder="Buscar punto de servicio..."
            empty-title="Sin puntos de servicio"
            empty-description="Crea el primer punto de servicio."
            :empty-icon="MapPin"
            @search="onSearch"
            @page-change="onPage"
        >
            <template #filters>
                <SearchableSelect v-model="filterClientId" :options="clientOptions" placeholder="Filtrar por empresa..." class="w-56" />
                <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearFilters">
                    <X class="h-3.5 w-3.5 mr-1" /> Limpiar filtros
                </Button>
            </template>
            <template #cell-employees_count="{ item }">
                <Badge variant="secondary">{{ item.employees_count ?? 0 }}</Badge>
            </template>
            <template #cell-status="{ item }">
                <StatusBadge :status="item.status" />
            </template>
            <template #actions="{ item }">
                <Button v-if="hasPermission('Editar puntos de servicio')" variant="ghost" size="sm" @click="openEdit(item)">
                    <Pencil class="h-4 w-4" />
                </Button>
                <Button v-if="hasPermission('Eliminar puntos de servicio')" variant="ghost" size="sm" class="text-destructive" @click="deleteId = item.id">
                    <Trash2 class="h-4 w-4" />
                </Button>
            </template>
        </AppDataTable>

        <Dialog :open="showModal" @update:open="showModal = $event">
            <FormDialogContent class="max-w-md">
                <DialogHeader><DialogTitle>{{ editingSP ? 'Editar Punto' : 'Nuevo Punto de Servicio' }}</DialogTitle></DialogHeader>
                <form @submit.prevent="submit" class="space-y-4">
                    <SearchableSelect
                        v-model="clientIdModel"
                        :options="clientOptions"
                        label="Empresa"
                        required
                        placeholder="Selecciona empresa..."
                        :clearable="false"
                        :error="form.errors.client_id"
                    />
                    <FormInput v-model="form.name" label="Nombre" required placeholder="Ej. Planta Norte - Turno A" :error="form.errors.name" />
                    <FormTextarea v-model="form.address" label="Dirección" placeholder="Dirección completa (opcional)" :rows="2" :error="form.errors.address" />
                    <FormSelect v-model="form.status" label="Estado" required :error="form.errors.status">
                        <SelectItem value="activo">Activo</SelectItem>
                        <SelectItem value="inactivo">Inactivo</SelectItem>
                    </FormSelect>
                    <FormActions :processing="form.processing" @cancel="showModal = false" />
                </form>
            </FormDialogContent>
        </Dialog>

        <DeleteDialog
            :open="!!deleteId"
            title="¿Eliminar punto de servicio?"
            description="Se eliminará el punto de servicio. Los colaboradores asignados perderán esta referencia."
            @update:open="deleteId = null"
            @confirm="confirmDelete"
        />
    </div>
</template>
