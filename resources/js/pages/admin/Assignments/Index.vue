<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, UserCheck } from '@lucide/vue';
import DeleteDialog from '@/components/DeleteDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
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

const submit = () => {
    form.post('/asignaciones', {
        onSuccess: () => { showModal.value = false; form.reset(); },
    });
};

const confirmDelete = () => {
    if (!deleteId.value) return;
    router.delete(`/asignaciones/${deleteId.value}`, { onFinish: () => { deleteId.value = null; } });
};

const filteredSPs = (clientId: string) => props.servicePoints.filter((sp) => !clientId || sp.client_id === Number(clientId));

const onPage = (p: number) => router.get('/asignaciones', { ...props.filters, page: p }, { preserveState: true });
</script>

<template>
    <div class="p-6">
        <PageHeader title="Asignación de Supervisores" description="Controla qué supervisores pueden capturar en qué empresas y puntos">
            <template #actions>
                <Button v-if="hasPermission('Crear asignaciones')" @click="showModal = true">
                    <Plus class="h-4 w-4 mr-2" /> Nueva Asignación
                </Button>
            </template>
        </PageHeader>

        <div class="rounded-lg border bg-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-muted/30">
                    <tr>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Supervisor</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Email</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Empresa</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Punto de Servicio</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Asignado</th>
                        <th class="text-right p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!assignments.data.length">
                        <td colspan="6"><EmptyState :icon="UserCheck" title="Sin asignaciones" description="Asigna supervisores a empresas y puntos de servicio." /></td>
                    </tr>
                    <tr v-for="a in assignments.data" :key="a.id" class="border-t hover:bg-muted/40 transition-colors">
                        <td class="p-3 font-medium">{{ a.supervisor?.name }}</td>
                        <td class="p-3 text-sm text-muted-foreground">{{ a.supervisor?.email }}</td>
                        <td class="p-3 text-sm">{{ a.client?.name }}</td>
                        <td class="p-3 text-sm">
                            <span v-if="a.service_point?.name">{{ a.service_point.name }}</span>
                            <span v-else class="text-muted-foreground italic">Todas las ubicaciones</span>
                        </td>
                        <td class="p-3 text-xs text-muted-foreground">{{ a.created_at }}</td>
                        <td class="p-3">
                            <Button
                                v-if="hasPermission('Eliminar asignaciones')"
                                variant="ghost" size="sm"
                                class="text-destructive"
                                @click="deleteId = a.id"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="assignments.last_page > 1" class="flex justify-between items-center mt-4 text-sm">
            <span class="text-muted-foreground">{{ assignments.from }}–{{ assignments.to }} de {{ assignments.total }}</span>
            <div class="flex gap-1">
                <Button variant="outline" size="sm" :disabled="assignments.current_page <= 1" @click="onPage(assignments.current_page - 1)">Anterior</Button>
                <Button variant="outline" size="sm" :disabled="assignments.current_page >= assignments.last_page" @click="onPage(assignments.current_page + 1)">Siguiente</Button>
            </div>
        </div>

        <Dialog :open="showModal" @update:open="showModal = $event">
            <DialogContent class="max-w-md">
                <DialogHeader><DialogTitle>Nueva Asignación</DialogTitle></DialogHeader>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label>Supervisor *</Label>
                        <Select v-model="form.supervisor_user_id">
                            <SelectTrigger class="mt-1"><SelectValue placeholder="Selecciona supervisor..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="s in supervisors" :key="s.id" :value="String(s.id)">{{ s.name }} ({{ s.email }})</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.supervisor_user_id" class="text-destructive text-xs mt-1">{{ form.errors.supervisor_user_id }}</p>
                    </div>
                    <div>
                        <Label>Empresa *</Label>
                        <Select v-model="form.client_id" @update:model-value="form.service_point_id = ''">
                            <SelectTrigger class="mt-1"><SelectValue placeholder="Selecciona empresa..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="c in clients" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.client_id" class="text-destructive text-xs mt-1">{{ form.errors.client_id }}</p>
                    </div>
                    <div>
                        <Label>Punto de Servicio (opcional)</Label>
                        <Select v-model="form.service_point_id" :disabled="!form.client_id">
                            <SelectTrigger class="mt-1"><SelectValue placeholder="Todos los puntos (sin especificar)" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Todos los puntos</SelectItem>
                                <SelectItem v-for="sp in filteredSPs(form.client_id)" :key="sp.id" :value="String(sp.id)">{{ sp.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showModal = false">Cancelar</Button>
                        <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Guardando...' : 'Asignar' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
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
