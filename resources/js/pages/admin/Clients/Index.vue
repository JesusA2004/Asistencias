<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Building2, Plus, Pencil, Trash2 } from '@lucide/vue';
import { type ColumnDef } from '@tanstack/vue-table';
import DataTable from '@/components/DataTable.vue';
import DeleteDialog from '@/components/DeleteDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
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
    form.status = 'activo';
    showModal.value = true;
};

const openEdit = (client: Client) => {
    editingClient.value = client;
    form.name = client.name;
    form.business_name = client.business_name ?? '';
    form.rfc = client.rfc ?? '';
    form.status = client.status;
    showModal.value = true;
};

const submit = () => {
    if (editingClient.value) {
        form.put(`/empresas/${editingClient.value.id}`, {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    } else {
        form.post('/empresas', {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
};

const confirmDelete = () => {
    if (!deleteId.value) return;
    router.delete(`/empresas/${deleteId.value}`, {
        onFinish: () => { deleteId.value = null; },
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
    {
        accessorKey: 'status',
        header: 'Estado',
        cell: ({ row }) => row.original.status,
    },
    {
        id: 'actions',
        header: 'Acciones',
    },
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

        <DataTable
            :columns="columns"
            :data="clients.data"
            :pagination="clients"
            search-placeholder="Buscar empresa, RFC..."
            @search="onSearch"
            @page-change="onPage"
        >
            <template #default="{ table }">
                <!-- This slot is used via column defs below -->
            </template>
        </DataTable>

        <!-- We render the table manually for action column support -->
        <div class="rounded-lg border bg-card overflow-hidden mt-4">
            <table class="w-full text-sm">
                <thead class="bg-muted/30">
                    <tr>
                        <th class="text-left p-3 font-semibold text-xs uppercase tracking-wider text-muted-foreground">Empresa</th>
                        <th class="text-left p-3 font-semibold text-xs uppercase tracking-wider text-muted-foreground">Razón Social</th>
                        <th class="text-left p-3 font-semibold text-xs uppercase tracking-wider text-muted-foreground">RFC</th>
                        <th class="text-center p-3 font-semibold text-xs uppercase tracking-wider text-muted-foreground">Colaboradores</th>
                        <th class="text-left p-3 font-semibold text-xs uppercase tracking-wider text-muted-foreground">Estado</th>
                        <th class="text-right p-3 font-semibold text-xs uppercase tracking-wider text-muted-foreground">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!clients.data.length">
                        <td colspan="6" class="p-0">
                            <EmptyState :icon="Building2" title="Sin empresas" description="Crea la primera empresa para comenzar." />
                        </td>
                    </tr>
                    <tr
                        v-for="client in clients.data"
                        :key="client.id"
                        class="border-t hover:bg-muted/40 transition-colors"
                    >
                        <td class="p-3 font-medium">{{ client.name }}</td>
                        <td class="p-3 text-muted-foreground">{{ client.business_name || '—' }}</td>
                        <td class="p-3 text-muted-foreground font-mono text-xs">{{ client.rfc || '—' }}</td>
                        <td class="p-3 text-center">
                            <Badge variant="secondary">{{ client.employees_count ?? 0 }}</Badge>
                        </td>
                        <td class="p-3"><StatusBadge :status="client.status" /></td>
                        <td class="p-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    v-if="hasPermission('Editar empresas')"
                                    variant="ghost" size="sm"
                                    @click="openEdit(client)"
                                >
                                    <Pencil class="h-4 w-4" />
                                </Button>
                                <Button
                                    v-if="hasPermission('Eliminar empresas')"
                                    variant="ghost" size="sm"
                                    class="text-destructive hover:text-destructive"
                                    @click="deleteId = client.id"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="clients.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
            <span class="text-muted-foreground">Mostrando {{ clients.from }}–{{ clients.to }} de {{ clients.total }}</span>
            <div class="flex gap-1">
                <Button variant="outline" size="sm" :disabled="clients.current_page <= 1" @click="onPage(clients.current_page - 1)">Anterior</Button>
                <Button variant="outline" size="sm" :disabled="clients.current_page >= clients.last_page" @click="onPage(clients.current_page + 1)">Siguiente</Button>
            </div>
        </div>

        <!-- Modal -->
        <Dialog :open="showModal" @update:open="showModal = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editingClient ? 'Editar Empresa' : 'Nueva Empresa' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label for="name">Nombre *</Label>
                        <Input id="name" v-model="form.name" placeholder="Nombre de la empresa" class="mt-1" />
                        <p v-if="form.errors.name" class="text-destructive text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <Label for="business_name">Razón Social</Label>
                        <Input id="business_name" v-model="form.business_name" placeholder="Razón social (opcional)" class="mt-1" />
                    </div>
                    <div>
                        <Label for="rfc">RFC</Label>
                        <Input id="rfc" v-model="form.rfc" placeholder="RFC (opcional)" class="mt-1" />
                    </div>
                    <div>
                        <Label for="status">Estado *</Label>
                        <Select v-model="form.status">
                            <SelectTrigger class="mt-1">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="activo">Activo</SelectItem>
                                <SelectItem value="inactivo">Inactivo</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showModal = false">Cancelar</Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Guardando...' : 'Guardar' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
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
