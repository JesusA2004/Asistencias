<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, UserCheck } from '@lucide/vue';
import type {ColumnDef} from '@tanstack/vue-table';
import { ref } from 'vue';
import AppDataTable from '@/components/AppDataTable.vue';
import DeleteDialog from '@/components/DeleteDialog.vue';
import FormActions from '@/components/FormActions.vue';
import FormDialogContent from '@/components/FormDialogContent.vue';
import FormSelect from '@/components/FormSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { SelectItem } from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import type { AppUser, Client, PaginatedData, ServicePoint, SupervisorAssignment } from '@/types/models';

const props = defineProps<{
    assignments: PaginatedData<SupervisorAssignment>;
    supervisors: AppUser[];
    clients: Client[];
    servicePoints: ServicePoint[];
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
            <template #cell-service_point="{ item }">
                <span v-if="item.service_point?.name">{{ item.service_point.name }}</span>
                <span v-else class="text-muted-foreground italic">Todas las ubicaciones</span>
            </template>
            <template #cell-created_at="{ value }">
                <span class="text-xs text-muted-foreground">{{ value }}</span>
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
                    <FormSelect v-model="form.supervisor_user_id" label="Supervisor" required placeholder="Selecciona supervisor..." :error="form.errors.supervisor_user_id">
                        <SelectItem v-for="s in supervisors" :key="s.id" :value="String(s.id)">{{ s.name }} ({{ s.email }})</SelectItem>
                    </FormSelect>
                    <FormSelect
                        v-model="form.client_id"
                        label="Empresa"
                        required
                        placeholder="Selecciona empresa..."
                        :error="form.errors.client_id"
                        @update:model-value="form.service_point_id = ''"
                    >
                        <SelectItem v-for="c in clients" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                    </FormSelect>
                    <FormSelect
                        :model-value="form.service_point_id || '__all__'"
                        label="Punto de Servicio (opcional)"
                        :disabled="!form.client_id"
                        placeholder="Todos los puntos (sin especificar)"
                        @update:model-value="(v) => form.service_point_id = v === '__all__' ? '' : v"
                    >
                        <SelectItem value="__all__">Todos los puntos</SelectItem>
                        <SelectItem v-for="sp in filteredSPs(form.client_id)" :key="sp.id" :value="String(sp.id)">{{ sp.name }}</SelectItem>
                    </FormSelect>
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
