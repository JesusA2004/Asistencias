<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Clock, Plus, Pencil, Trash2 } from '@lucide/vue';
import DeleteDialog from '@/components/DeleteDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import type { PaginatedData, Shift } from '@/types/models';

const props = defineProps<{
    shifts: PaginatedData<Shift & { employees_count: number }>;
    filters: { search?: string; status?: string };
}>();

const { hasPermission } = usePermissions();
const showModal = ref(false);
const deleteId = ref<number | null>(null);
const editingShift = ref<Shift | null>(null);

const DAYS = [
    { value: 'lunes', label: 'Lun' },
    { value: 'martes', label: 'Mar' },
    { value: 'miercoles', label: 'Mié' },
    { value: 'jueves', label: 'Jue' },
    { value: 'viernes', label: 'Vie' },
    { value: 'sabado', label: 'Sáb' },
    { value: 'domingo', label: 'Dom' },
];

const form = useForm({
    name: '',
    start_time: '08:00',
    end_time: '17:00',
    work_days: ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'] as string[],
    tolerance_minutes: 10,
    status: 'activo' as 'activo' | 'inactivo',
});

const toggleDay = (day: string) => {
    const idx = form.work_days.indexOf(day);
    if (idx >= 0) form.work_days.splice(idx, 1);
    else form.work_days.push(day);
};

const openCreate = () => {
    editingShift.value = null;
    form.reset();
    form.work_days = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    form.start_time = '08:00';
    form.end_time = '17:00';
    form.tolerance_minutes = 10;
    form.status = 'activo';
    showModal.value = true;
};

const openEdit = (shift: Shift) => {
    editingShift.value = shift;
    form.name = shift.name;
    form.start_time = shift.start_time;
    form.end_time = shift.end_time;
    form.work_days = shift.work_days ?? [];
    form.tolerance_minutes = shift.tolerance_minutes;
    form.status = shift.status;
    showModal.value = true;
};

const submit = () => {
    if (editingShift.value) {
        form.put(`/turnos/${editingShift.value.id}`, {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post('/turnos', {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
};

const confirmDelete = () => {
    if (!deleteId.value) return;
    router.delete(`/turnos/${deleteId.value}`, { onFinish: () => { deleteId.value = null; } });
};
</script>

<template>
    <div class="p-6">
        <PageHeader title="Turnos" description="Configuración de horarios y días laborables">
            <template #actions>
                <Button v-if="hasPermission('Crear turnos')" @click="openCreate">
                    <Plus class="h-4 w-4 mr-2" /> Nuevo Turno
                </Button>
            </template>
        </PageHeader>

        <div class="rounded-lg border bg-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-muted/30">
                    <tr>
                        <th class="text-left p-3 font-semibold text-xs uppercase tracking-wider text-muted-foreground">Nombre</th>
                        <th class="text-left p-3 font-semibold text-xs uppercase tracking-wider text-muted-foreground">Horario</th>
                        <th class="text-left p-3 font-semibold text-xs uppercase tracking-wider text-muted-foreground">Días</th>
                        <th class="text-center p-3 font-semibold text-xs uppercase tracking-wider text-muted-foreground">Tolerancia</th>
                        <th class="text-center p-3 font-semibold text-xs uppercase tracking-wider text-muted-foreground">Colaboradores</th>
                        <th class="text-left p-3 font-semibold text-xs uppercase tracking-wider text-muted-foreground">Estado</th>
                        <th class="text-right p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!shifts.data.length">
                        <td colspan="7" class="p-0">
                            <EmptyState :icon="Clock" title="Sin turnos" description="Crea el primer turno para comenzar." />
                        </td>
                    </tr>
                    <tr
                        v-for="shift in shifts.data"
                        :key="shift.id"
                        class="border-t hover:bg-muted/40 transition-colors"
                    >
                        <td class="p-3 font-medium">{{ shift.name }}</td>
                        <td class="p-3 font-mono text-sm">{{ shift.start_time }} – {{ shift.end_time }}</td>
                        <td class="p-3">
                            <div class="flex gap-1">
                                <Badge
                                    v-for="day in DAYS"
                                    :key="day.value"
                                    :variant="shift.work_days?.includes(day.value) ? 'default' : 'outline'"
                                    class="text-xs px-1.5 py-0"
                                >
                                    {{ day.label }}
                                </Badge>
                            </div>
                        </td>
                        <td class="p-3 text-center">{{ shift.tolerance_minutes }} min</td>
                        <td class="p-3 text-center"><Badge variant="secondary">{{ (shift as any).employees_count ?? 0 }}</Badge></td>
                        <td class="p-3"><StatusBadge :status="shift.status" /></td>
                        <td class="p-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button v-if="hasPermission('Editar turnos')" variant="ghost" size="sm" @click="openEdit(shift)">
                                    <Pencil class="h-4 w-4" />
                                </Button>
                                <Button v-if="hasPermission('Eliminar turnos')" variant="ghost" size="sm" class="text-destructive hover:text-destructive" @click="deleteId = shift.id">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <Dialog :open="showModal" @update:open="showModal = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editingShift ? 'Editar Turno' : 'Nuevo Turno' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label>Nombre *</Label>
                        <Input v-model="form.name" placeholder="Ej. Turno Matutino" class="mt-1" />
                        <p v-if="form.errors.name" class="text-destructive text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label>Entrada *</Label>
                            <Input type="time" v-model="form.start_time" class="mt-1" />
                        </div>
                        <div>
                            <Label>Salida *</Label>
                            <Input type="time" v-model="form.end_time" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <Label>Días laborables</Label>
                        <div class="flex gap-2 mt-2 flex-wrap">
                            <label
                                v-for="day in DAYS"
                                :key="day.value"
                                class="flex items-center gap-1.5 cursor-pointer"
                            >
                                <Checkbox
                                    :checked="form.work_days.includes(day.value)"
                                    @update:checked="toggleDay(day.value)"
                                />
                                <span class="text-sm">{{ day.label }}</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <Label>Tolerancia (minutos)</Label>
                        <Input type="number" v-model.number="form.tolerance_minutes" min="0" max="120" class="mt-1" />
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
            title="¿Eliminar turno?"
            description="Se eliminará el turno. Los colaboradores asignados perderán esta referencia."
            @update:open="deleteId = null"
            @confirm="confirmDelete"
        />
    </div>
</template>
