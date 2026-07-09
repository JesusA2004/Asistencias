<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Building2, Plus, Pencil, Trash2 } from '@lucide/vue';
import type {ColumnDef} from '@tanstack/vue-table';
import { ref } from 'vue';
import AppDataTable from '@/components/AppDataTable.vue';
import DeleteDialog from '@/components/DeleteDialog.vue';
import FormActions from '@/components/FormActions.vue';
import FormDialogContent from '@/components/FormDialogContent.vue';
import FormInput from '@/components/FormInput.vue';
import FormSelect from '@/components/FormSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { SelectItem } from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import type { Client, PaginatedData } from '@/types/models';

const props = defineProps<{
    clients: PaginatedData<Client>;
    filters: { search?: string; status?: string };
}>();

const { hasPermission } = usePermissions();
const showModal = ref(false);
const deleteId = ref<number | null>(null);
const editingClient = ref<Client | null>(null);

const form = useForm({
    name: '',
    business_name: '',
    rfc: '',
    status: 'activo' as 'activo' | 'inactivo',
});

const openCreate = () => {
    editingClient.value = null;
    form.reset();
    form.clearErrors();
    form.status = 'activo';
    showModal.value = true;
};

const openEdit = (client: Client) => {
    editingClient.value = client;
    form.clearErrors();
    form.name = client.name;
    form.business_name = client.business_name ?? '';
    form.rfc = client.rfc ?? '';
    form.status = client.status;
    showModal.value = true;
};

const submit = () => {
    if (editingClient.value) {
        form.put(`/empresas/${editingClient.value.id}`, {
            onSuccess: () => {
 showModal.value = false; form.reset(); 
},
        });
    } else {
        form.post('/empresas', {
            onSuccess: () => {
 showModal.value = false; form.reset(); 
},
        });
    }
};

const confirmDelete = () => {
    if (!deleteId.value) {
return;
}

    router.delete(`/empresas/${deleteId.value}`, {
        onFinish: () => {
 deleteId.value = null; 
},
    });
};

const onSearch = (q: string) => router.get('/empresas', { ...props.filters, search: q }, { preserveState: true, replace: true });
const onPage = (p: number) => router.get('/empresas', { ...props.filters, page: p }, { preserveState: true });

const columns: ColumnDef<Client>[] = [
    { accessorKey: 'name', header: 'Empresa' },
    { accessorKey: 'business_name', header: 'Razón Social', cell: ({ getValue }) => getValue() || '—' },
    { accessorKey: 'rfc', header: 'RFC', cell: ({ getValue }) => getValue() || '—' },
    { accessorKey: 'employees_count', header: 'Colaboradores' },
    { accessorKey: 'service_points_count', header: 'Puntos Servicio' },
    { accessorKey: 'status', header: 'Estado' },
];
</script>

<template>
    <div class="p-6">
        <PageHeader title="Empresas / Clientes" description="Gestión de clientes y empresas de seguridad">
            <template #actions>
                <Button v-if="hasPermission('Crear empresas')" @click="openCreate">
                    <Plus class="h-4 w-4 mr-2" /> Nueva Empresa
                </Button>
            </template>
        </PageHeader>

        <AppDataTable
            :columns="columns"
            :data="clients.data"
            :pagination="clients"
            search-placeholder="Buscar empresa, RFC..."
            empty-title="Sin empresas"
            empty-description="Crea la primera empresa para comenzar."
            :empty-icon="Building2"
            @search="onSearch"
            @page-change="onPage"
        >
            <template #cell-employees_count="{ item }">
                <Badge variant="secondary">{{ item.employees_count ?? 0 }}</Badge>
            </template>
            <template #cell-status="{ item }">
                <StatusBadge :status="item.status" />
            </template>
            <template #actions="{ item }">
                <Button v-if="hasPermission('Editar empresas')" variant="ghost" size="sm" @click="openEdit(item)">
                    <Pencil class="h-4 w-4" />
                </Button>
                <Button
                    v-if="hasPermission('Eliminar empresas')"
                    variant="ghost"
                    size="sm"
                    class="text-destructive hover:text-destructive"
                    @click="deleteId = item.id"
                >
                    <Trash2 class="h-4 w-4" />
                </Button>
            </template>
        </AppDataTable>

        <!-- Modal -->
        <Dialog :open="showModal" @update:open="showModal = $event">
            <FormDialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editingClient ? 'Editar Empresa' : 'Nueva Empresa' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submit" class="space-y-4">
                    <FormInput
                        v-model="form.name"
                        label="Nombre"
                        required
                        placeholder="Nombre de la empresa"
                        :error="form.errors.name"
                    />
                    <FormInput
                        v-model="form.business_name"
                        label="Razón Social"
                        placeholder="Razón social (opcional)"
                        :error="form.errors.business_name"
                    />
                    <FormInput
                        v-model="form.rfc"
                        label="RFC"
                        placeholder="RFC (opcional)"
                        :error="form.errors.rfc"
                    />
                    <FormSelect v-model="form.status" label="Estado" required :error="form.errors.status">
                        <SelectItem value="activo">Activo</SelectItem>
                        <SelectItem value="inactivo">Inactivo</SelectItem>
                    </FormSelect>
                    <FormActions :processing="form.processing" @cancel="showModal = false" />
                </form>
            </FormDialogContent>
        </Dialog>

        <!-- Delete confirm -->
        <DeleteDialog
            :open="!!deleteId"
            title="¿Eliminar empresa?"
            description="Se eliminará la empresa. Esta acción no se puede deshacer."
            @update:open="deleteId = null"
            @confirm="confirmDelete"
        />
    </div>
</template>
