<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Clock, Plus, Pencil, Trash2 } from '@lucide/vue';
import type {ColumnDef} from '@tanstack/vue-table';
import { ref } from 'vue';
import AppDataTable from '@/components/AppDataTable.vue';
import DeleteDialog from '@/components/DeleteDialog.vue';
import FormActions from '@/components/FormActions.vue';
import FormDialogContent from '@/components/FormDialogContent.vue';
import FormField from '@/components/FormField.vue';
import FormInput from '@/components/FormInput.vue';
import FormSelect from '@/components/FormSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SelectItem } from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import type { PaginatedData, Shift } from '@/types/models';

type ShiftRow = Shift & { employees_count: number };

const props = defineProps<{
    shifts: PaginatedData<ShiftRow>;
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

    if (idx >= 0) {
form.work_days.splice(idx, 1);
} else {
form.work_days.push(day);
}
};

const openCreate = () => {
    editingShift.value = null;
    form.reset();
    form.clearErrors();
    form.work_days = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    form.start_time = '08:00';
    form.end_time = '17:00';
    form.tolerance_minutes = 10;
    form.status = 'activo';
    showModal.value = true;
};

const openEdit = (shift: Shift) => {
    editingShift.value = shift;
    form.clearErrors();
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
            onSuccess: () => {
 showModal.value = false; 
},
        });
    } else {
        form.post('/turnos', {
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

    router.delete(`/turnos/${deleteId.value}`, { onFinish: () => {
 deleteId.value = null; 
} });
};

const onSearch = (q: string) => router.get('/turnos', { ...props.filters, search: q }, { preserveState: true, replace: true });
const onPage = (p: number) => router.get('/turnos', { ...props.filters, page: p }, { preserveState: true });

const columns: ColumnDef<ShiftRow>[] = [
    { accessorKey: 'name', header: 'Nombre' },
    { accessorKey: 'schedule', header: 'Horario', cell: ({ row }) => `${row.original.start_time} – ${row.original.end_time}` },
    { accessorKey: 'work_days', header: 'Días' },
    { accessorKey: 'tolerance_minutes', header: 'Tolerancia' },
    { accessorKey: 'employees_count', header: 'Colaboradores' },
    { accessorKey: 'status', header: 'Estado' },
];
</script>

<template>
    <div class="p-6">
        <PageHeader title="Turnos" description="Horarios de trabajo y tolerancias de entrada por turno.">
            <template #actions>
                <Button v-if="hasPermission('Crear turnos')" @click="openCreate">
                    <Plus class="h-4 w-4 mr-2" /> Nuevo Turno
                </Button>
            </template>
        </PageHeader>

        <AppDataTable
            :columns="columns"
            :data="shifts.data"
            :pagination="shifts"
            search-placeholder="Buscar turno..."
            empty-title="Sin turnos"
            empty-description="Crea el primer turno para comenzar."
            :empty-icon="Clock"
            @search="onSearch"
            @page-change="onPage"
        >
            <template #cell-schedule="{ value }">
                <span class="font-mono text-sm">{{ value }}</span>
            </template>
            <template #cell-work_days="{ item }">
                <div class="flex gap-1">
                    <Badge
                        v-for="day in DAYS"
                        :key="day.value"
                        :variant="item.work_days?.includes(day.value) ? 'default' : 'outline'"
                        class="text-xs px-1.5 py-0"
                    >
                        {{ day.label }}
                    </Badge>
                </div>
            </template>
            <template #cell-tolerance_minutes="{ value }">
                {{ value }} min
            </template>
            <template #cell-employees_count="{ item }">
                <Badge variant="secondary">{{ item.employees_count ?? 0 }}</Badge>
            </template>
            <template #cell-status="{ item }">
                <StatusBadge :status="item.status" />
            </template>
            <template #actions="{ item }">
                <Button v-if="hasPermission('Editar turnos')" variant="ghost" size="sm" @click="openEdit(item)">
                    <Pencil class="h-4 w-4" />
                </Button>
                <Button v-if="hasPermission('Eliminar turnos')" variant="ghost" size="sm" class="text-destructive hover:text-destructive" @click="deleteId = item.id">
                    <Trash2 class="h-4 w-4" />
                </Button>
            </template>
        </AppDataTable>

        <!-- Modal -->
        <Dialog :open="showModal" @update:open="showModal = $event">
            <FormDialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editingShift ? 'Editar Turno' : 'Nuevo Turno' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submit" class="space-y-4">
                    <FormInput v-model="form.name" label="Nombre" required placeholder="Ej. Turno Matutino" :error="form.errors.name" />
                    <div class="grid grid-cols-2 gap-3">
                        <FormField label="Entrada" required>
                            <Input type="time" v-model="form.start_time" />
                        </FormField>
                        <FormField label="Salida" required>
                            <Input type="time" v-model="form.end_time" />
                        </FormField>
                    </div>
                    <FormField label="Días laborables">
                        <div class="flex gap-2 mt-1 flex-wrap">
                            <Label
                                v-for="day in DAYS"
                                :key="day.value"
                                class="flex items-center gap-1.5 cursor-pointer rounded-md border px-2.5 py-1.5 has-[button[data-state=checked]]:border-primary has-[button[data-state=checked]]:bg-primary/5"
                            >
                                <Checkbox
                                    :checked="form.work_days.includes(day.value)"
                                    @update:checked="toggleDay(day.value)"
                                />
                                <span class="text-sm">{{ day.label }}</span>
                            </Label>
                        </div>
                    </FormField>
                    <FormField label="Tolerancia (minutos)" required :error="form.errors.tolerance_minutes">
                        <Input type="number" v-model.number="form.tolerance_minutes" min="0" max="120" />
                    </FormField>
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
            title="¿Eliminar turno?"
            description="Se eliminará el turno. Los colaboradores asignados perderán esta referencia."
            @update:open="deleteId = null"
            @confirm="confirmDelete"
        />
    </div>
</template>
