<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { MapPin, Pencil, Plus, Trash2 } from '@lucide/vue';
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
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/composables/usePermissions';
import type { Client, PaginatedData, ServicePoint } from '@/types/models';

const props = defineProps<{
    servicePoints: PaginatedData<ServicePoint & { employees_count: number }>;
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
    form.status = 'activo';
    showModal.value = true;
};

const openEdit = (sp: ServicePoint) => {
    editingSP.value = sp;
    form.client_id = String(sp.client_id);
    form.name = sp.name;
    form.address = sp.address ?? '';
    form.status = sp.status;
    showModal.value = true;
};

const submit = () => {
    if (editingSP.value) {
        form.put(`/puntos-servicio/${editingSP.value.id}`, {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post('/puntos-servicio', {
            onSuccess: () => { showModal.value = false; form.reset(); form.status = 'activo'; },
        });
    }
};

const confirmDelete = () => {
    if (!deleteId.value) return;
    router.delete(`/puntos-servicio/${deleteId.value}`, { onFinish: () => { deleteId.value = null; } });
};

const onPage = (p: number) => router.get('/puntos-servicio', { ...props.filters, page: p }, { preserveState: true });
</script>

<template>
    <div class="p-6">
        <PageHeader title="Puntos de Servicio" description="Ubicaciones donde operan los colaboradores">
            <template #actions>
                <Button v-if="hasPermission('Crear puntos de servicio')" @click="openCreate">
                    <Plus class="h-4 w-4 mr-2" /> Nuevo Punto
                </Button>
            </template>
        </PageHeader>

        <!-- Client filter -->
        <div class="mb-4">
            <Select :model-value="filters.client_id ?? ''" @update:model-value="(v) => router.get('/puntos-servicio', { ...filters, client_id: v || undefined }, { preserveState: true })">
                <SelectTrigger class="w-64"><SelectValue placeholder="Filtrar por empresa..." /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Todas las empresas</SelectItem>
                    <SelectItem v-for="c in clients" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="rounded-lg border bg-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-muted/30">
                    <tr>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Nombre</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Empresa</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Dirección</th>
                        <th class="text-center p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Colaboradores</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Estado</th>
                        <th class="text-right p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!servicePoints.data.length">
                        <td colspan="6"><EmptyState :icon="MapPin" title="Sin puntos de servicio" description="Crea el primer punto de servicio." /></td>
                    </tr>
                    <tr v-for="sp in servicePoints.data" :key="sp.id" class="border-t hover:bg-muted/40 transition-colors">
                        <td class="p-3 font-medium">{{ sp.name }}</td>
                        <td class="p-3 text-sm">{{ sp.client?.name ?? '—' }}</td>
                        <td class="p-3 text-sm text-muted-foreground truncate max-w-[200px]">{{ sp.address ?? '—' }}</td>
                        <td class="p-3 text-center"><Badge variant="secondary">{{ (sp as any).employees_count ?? 0 }}</Badge></td>
                        <td class="p-3"><StatusBadge :status="sp.status" /></td>
                        <td class="p-3">
                            <div class="flex items-center justify-end gap-1">
                                <Button v-if="hasPermission('Editar puntos de servicio')" variant="ghost" size="sm" @click="openEdit(sp)">
                                    <Pencil class="h-4 w-4" />
                                </Button>
                                <Button v-if="hasPermission('Eliminar puntos de servicio')" variant="ghost" size="sm" class="text-destructive" @click="deleteId = sp.id">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="servicePoints.last_page > 1" class="flex justify-between items-center mt-4 text-sm">
            <span class="text-muted-foreground">{{ servicePoints.from }}–{{ servicePoints.to }} de {{ servicePoints.total }}</span>
            <div class="flex gap-1">
                <Button variant="outline" size="sm" :disabled="servicePoints.current_page <= 1" @click="onPage(servicePoints.current_page - 1)">Anterior</Button>
                <Button variant="outline" size="sm" :disabled="servicePoints.current_page >= servicePoints.last_page" @click="onPage(servicePoints.current_page + 1)">Siguiente</Button>
            </div>
        </div>

        <Dialog :open="showModal" @update:open="showModal = $event">
            <DialogContent class="max-w-md">
                <DialogHeader><DialogTitle>{{ editingSP ? 'Editar Punto' : 'Nuevo Punto de Servicio' }}</DialogTitle></DialogHeader>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label>Empresa *</Label>
                        <Select v-model="form.client_id">
                            <SelectTrigger class="mt-1"><SelectValue placeholder="Selecciona empresa..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="c in clients" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.client_id" class="text-destructive text-xs mt-1">{{ form.errors.client_id }}</p>
                    </div>
                    <div>
                        <Label>Nombre *</Label>
                        <Input v-model="form.name" placeholder="Ej. Planta Norte - Turno A" class="mt-1" />
                        <p v-if="form.errors.name" class="text-destructive text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <Label>Dirección</Label>
                        <Textarea v-model="form.address" placeholder="Dirección completa (opcional)" class="mt-1" rows="2" />
                    </div>
                    <div>
                        <Label>Estado</Label>
                        <Select v-model="form.status">
                            <SelectTrigger class="mt-1"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="activo">Activo</SelectItem>
                                <SelectItem value="inactivo">Inactivo</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showModal = false">Cancelar</Button>
                        <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Guardando...' : 'Guardar' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
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
