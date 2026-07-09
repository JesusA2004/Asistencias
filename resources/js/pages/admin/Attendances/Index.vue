<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import DeleteDialog from '@/components/DeleteDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/composables/usePermissions';
import type { Attendance, Client, PaginatedData, ServicePoint } from '@/types/models';

const props = defineProps<{
    attendances: PaginatedData<Attendance>;
    clients: Client[];
    servicePoints: ServicePoint[];
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
    correctForm.status = a.status;
    correctForm.entry_time = a.entry_time ?? '';
    correctForm.exit_time = a.exit_time ?? '';
    correctForm.notes = a.notes ?? '';
    correctForm.reason = '';
    showCorrectModal.value = true;
};

const submitCorrect = () => {
    if (!correctingAttendance.value) return;
    correctForm.patch(`/asistencias/${correctingAttendance.value.id}/corregir`, {
        onSuccess: () => { showCorrectModal.value = false; },
    });
};

const openDelete = (id: number) => {
    deleteId.value = id;
    deleteReason.value = '';
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!deleteId.value) return;
    router.delete(`/asistencias/${deleteId.value}`, {
        data: { reason: deleteReason.value },
        onSuccess: () => { showDeleteModal.value = false; deleteId.value = null; },
    });
};

const onPage = (p: number) => router.get('/asistencias', { ...props.filters, page: p }, { preserveState: true });

const filterDate = ref(props.filters.date ?? '');
const filterClient = ref(props.filters.client_id ?? '');
const filterSP = ref(props.filters.service_point_id ?? '');
const filterStatus = ref(props.filters.status ?? '');

const applyFilters = () => {
    router.get('/asistencias', {
        date: filterDate.value || undefined,
        client_id: filterClient.value || undefined,
        service_point_id: filterSP.value || undefined,
        status: filterStatus.value || undefined,
    }, { preserveState: true, replace: true });
};

const STATUSES = ['presente', 'falta', 'descanso', 'permiso', 'incapacidad', 'retardo'];
</script>

<template>
    <div class="p-6">
        <PageHeader title="Gestión de Asistencias" description="Consulta y corrección de registros de asistencia" />

        <!-- Filters -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6 bg-muted/30 p-4 rounded-lg border">
            <div>
                <Label class="text-xs">Fecha</Label>
                <Input type="date" v-model="filterDate" class="mt-1 h-8 text-sm" @change="applyFilters" />
            </div>
            <div>
                <Label class="text-xs">Empresa</Label>
                <Select :model-value="filterClient || '__all__'" @update:model-value="(v) => { filterClient = v === '__all__' ? '' : (v as string); applyFilters(); }">
                    <SelectTrigger class="mt-1 h-8 text-sm"><SelectValue placeholder="Todas" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="__all__">Todas</SelectItem>
                        <SelectItem v-for="c in clients" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div>
                <Label class="text-xs">Punto Servicio</Label>
                <Select :model-value="filterSP || '__all__'" @update:model-value="(v) => { filterSP = v === '__all__' ? '' : (v as string); applyFilters(); }">
                    <SelectTrigger class="mt-1 h-8 text-sm"><SelectValue placeholder="Todos" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="__all__">Todos</SelectItem>
                        <SelectItem v-for="sp in servicePoints" :key="sp.id" :value="String(sp.id)">{{ sp.name }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div>
                <Label class="text-xs">Estado</Label>
                <Select :model-value="filterStatus || '__all__'" @update:model-value="(v) => { filterStatus = v === '__all__' ? '' : (v as string); applyFilters(); }">
                    <SelectTrigger class="mt-1 h-8 text-sm"><SelectValue placeholder="Todos" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="__all__">Todos</SelectItem>
                        <SelectItem v-for="s in STATUSES" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="flex items-end">
                <Button variant="outline" size="sm" class="w-full" @click="applyFilters">Filtrar</Button>
            </div>
        </div>

        <!-- Table -->
        <div class="rounded-lg border bg-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-muted/30">
                    <tr>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Fecha</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Colaborador</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Empresa</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Punto</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Estado</th>
                        <th class="text-center p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Horario</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Supervisor</th>
                        <th class="text-right p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!attendances.data.length">
                        <td colspan="8"><EmptyState title="Sin registros" description="No hay asistencias con los filtros seleccionados." /></td>
                    </tr>
                    <tr
                        v-for="a in attendances.data"
                        :key="a.id"
                        class="border-t hover:bg-muted/40 transition-colors"
                    >
                        <td class="p-3 font-mono text-xs">{{ a.attendance_date }}</td>
                        <td class="p-3">
                            <div class="font-medium text-sm">{{ a.employee?.name }} {{ a.employee?.last_name }}</div>
                            <div class="text-xs text-muted-foreground font-mono">{{ a.employee?.employee_number }}</div>
                        </td>
                        <td class="p-3 text-sm">{{ a.client?.name }}</td>
                        <td class="p-3 text-sm">{{ a.service_point?.name }}</td>
                        <td class="p-3"><StatusBadge :status="a.status" /></td>
                        <td class="p-3 text-center text-xs font-mono">
                            {{ a.entry_time ?? '--:--' }} – {{ a.exit_time ?? '--:--' }}
                        </td>
                        <td class="p-3 text-sm">{{ a.supervisor?.name }}</td>
                        <td class="p-3">
                            <div class="flex items-center justify-end gap-1">
                                <Button v-if="hasPermission('Corregir asistencias')" variant="ghost" size="sm" @click="openCorrect(a)">
                                    <Pencil class="h-3.5 w-3.5" />
                                </Button>
                                <Button v-if="hasPermission('Eliminar asistencias')" variant="ghost" size="sm" class="text-destructive" @click="openDelete(a.id)">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="attendances.last_page > 1" class="flex justify-between items-center mt-4 text-sm">
            <span class="text-muted-foreground">{{ attendances.from }}–{{ attendances.to }} de {{ attendances.total }}</span>
            <div class="flex gap-1">
                <Button variant="outline" size="sm" :disabled="attendances.current_page <= 1" @click="onPage(attendances.current_page - 1)">Ant</Button>
                <Button variant="outline" size="sm" :disabled="attendances.current_page >= attendances.last_page" @click="onPage(attendances.current_page + 1)">Sig</Button>
            </div>
        </div>

        <!-- Correct modal -->
        <Dialog :open="showCorrectModal" @update:open="showCorrectModal = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Corregir Asistencia</DialogTitle>
                    <DialogDescription>
                        {{ correctingAttendance?.employee?.name }} {{ correctingAttendance?.employee?.last_name }} — {{ correctingAttendance?.attendance_date }}
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitCorrect" class="space-y-4">
                    <div>
                        <Label>Estado *</Label>
                        <Select v-model="correctForm.status">
                            <SelectTrigger class="mt-1"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="s in STATUSES" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label>Entrada</Label>
                            <Input type="time" v-model="correctForm.entry_time" class="mt-1" />
                        </div>
                        <div>
                            <Label>Salida</Label>
                            <Input type="time" v-model="correctForm.exit_time" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <Label>Notas</Label>
                        <Textarea v-model="correctForm.notes" class="mt-1" rows="2" />
                    </div>
                    <div>
                        <Label>Motivo de corrección *</Label>
                        <Textarea v-model="correctForm.reason" class="mt-1" rows="3" placeholder="Describe el motivo de la corrección (mínimo 10 caracteres)..." required />
                        <p v-if="correctForm.errors.reason" class="text-destructive text-xs mt-1">{{ correctForm.errors.reason }}</p>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showCorrectModal = false">Cancelar</Button>
                        <Button type="submit" :disabled="correctForm.processing">Guardar Corrección</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete modal with reason -->
        <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>¿Eliminar asistencia?</DialogTitle>
                    <DialogDescription>Esta acción registrará la eliminación en auditoría. Ingresa el motivo obligatorio.</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div>
                        <Label>Motivo de eliminación *</Label>
                        <Textarea v-model="deleteReason" class="mt-1" rows="3" placeholder="Describe el motivo..." />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteModal = false">Cancelar</Button>
                    <Button
                        variant="destructive"
                        :disabled="deleteReason.length < 10"
                        @click="confirmDelete"
                    >
                        Eliminar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
