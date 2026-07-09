<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { CheckCircle2, ClipboardList, Save } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Attendance, AttendanceStatus, Client, Employee, ServicePoint } from '@/types/models';

const props = defineProps<{
    clients: Client[];
    servicePoints: ServicePoint[];
    employees: Employee[];
    existingAttendances: Record<number, Attendance>;
    filters: { client_id?: string; service_point_id?: string; date?: string };
    alreadySaved: boolean;
}>();

const today = new Date().toISOString().split('T')[0];
const selectedClient = ref(props.filters.client_id ?? '');
const selectedSP = ref(props.filters.service_point_id ?? '');
const selectedDate = ref(props.filters.date ?? today);

const STATUSES: { value: AttendanceStatus; label: string; color: string }[] = [
    { value: 'presente', label: 'Presente', color: 'bg-green-100 text-green-700 border-green-200 hover:bg-green-200' },
    { value: 'falta', label: 'Falta', color: 'bg-red-100 text-red-700 border-red-200 hover:bg-red-200' },
    { value: 'retardo', label: 'Retardo', color: 'bg-purple-100 text-purple-700 border-purple-200 hover:bg-purple-200' },
    { value: 'descanso', label: 'Descanso', color: 'bg-blue-100 text-blue-700 border-blue-200 hover:bg-blue-200' },
    { value: 'permiso', label: 'Permiso', color: 'bg-yellow-100 text-yellow-700 border-yellow-200 hover:bg-yellow-200' },
    { value: 'incapacidad', label: 'Incapacidad', color: 'bg-orange-100 text-orange-700 border-orange-200 hover:bg-orange-200' },
];

interface RecordEntry {
    employee_id: number;
    status: AttendanceStatus;
    entry_time: string;
    exit_time: string;
    notes: string;
}

const records = ref<RecordEntry[]>(
    props.employees.map((e) => ({
        employee_id: e.id,
        status: 'presente' as AttendanceStatus,
        entry_time: '',
        exit_time: '',
        notes: '',
    }))
);

const applyToAll = (status: AttendanceStatus) => {
    records.value.forEach((r) => { r.status = status; });
};

const form = useForm({
    client_id: selectedClient.value,
    service_point_id: selectedSP.value,
    attendance_date: selectedDate.value,
    records: [] as RecordEntry[],
});

const loadEmployees = () => {
    router.get('/asistencias/capturar', {
        client_id: selectedClient.value,
        service_point_id: selectedSP.value,
        date: selectedDate.value,
    }, { preserveState: false });
};

const submit = () => {
    form.client_id = selectedClient.value;
    form.service_point_id = selectedSP.value;
    form.attendance_date = selectedDate.value;
    form.records = records.value;
    form.post('/asistencias/capturar');
};

const isAlreadySaved = (employeeId: number) => !!props.existingAttendances[employeeId];
const savedStatus = (employeeId: number) => props.existingAttendances[employeeId]?.status;

const newCount = computed(() => records.value.filter((r) => !isAlreadySaved(r.employee_id)).length);
</script>

<template>
    <div class="p-6 max-w-5xl mx-auto">
        <PageHeader title="Capturar Asistencia" description="Registra la asistencia diaria de tus colaboradores">
            <template #actions>
                <Button v-if="employees.length && !alreadySaved" @click="submit" :disabled="form.processing">
                    <Save class="h-4 w-4 mr-2" />
                    {{ form.processing ? 'Guardando...' : `Guardar ${newCount} registros` }}
                </Button>
            </template>
        </PageHeader>

        <!-- Filtros -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 bg-muted/30 p-4 rounded-lg border">
            <div>
                <Label>Empresa *</Label>
                <Select v-model="selectedClient" @update:model-value="selectedSP = ''; loadEmployees()">
                    <SelectTrigger class="mt-1"><SelectValue placeholder="Selecciona empresa..." /></SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="c in clients" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div>
                <Label>Punto de Servicio *</Label>
                <Select v-model="selectedSP" :disabled="!servicePoints.length" @update:model-value="loadEmployees()">
                    <SelectTrigger class="mt-1"><SelectValue placeholder="Selecciona punto..." /></SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="sp in servicePoints" :key="sp.id" :value="String(sp.id)">{{ sp.name }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div>
                <Label>Fecha *</Label>
                <Input type="date" v-model="selectedDate" :max="today" class="mt-1" @change="loadEmployees()" />
            </div>
            <div class="flex items-end">
                <Button variant="outline" class="w-full" @click="loadEmployees">
                    <ClipboardList class="h-4 w-4 mr-2" /> Cargar
                </Button>
            </div>
        </div>

        <!-- Already saved warning -->
        <Alert v-if="alreadySaved" class="mb-4 bg-amber-50 border-amber-200">
            <CheckCircle2 class="h-4 w-4 text-amber-600" />
            <AlertDescription class="text-amber-700">
                Ya registraste asistencias para esta fecha y punto de servicio. Solo el administrador puede hacer correcciones.
            </AlertDescription>
        </Alert>

        <!-- Quick action -->
        <div v-if="employees.length && !alreadySaved" class="flex gap-2 mb-4 flex-wrap">
            <span class="text-sm text-muted-foreground self-center mr-2">Aplicar a todos:</span>
            <button
                v-for="s in STATUSES"
                :key="s.value"
                type="button"
                @click="applyToAll(s.value)"
                :class="['px-3 py-1 text-xs font-medium rounded-full border transition-colors', s.color]"
            >
                {{ s.label }}
            </button>
        </div>

        <!-- Employee list -->
        <div v-if="employees.length" class="space-y-3">
            <div
                v-for="(record, i) in records"
                :key="record.employee_id"
                class="bg-card border rounded-lg p-4"
                :class="{ 'opacity-70': isAlreadySaved(record.employee_id) }"
            >
                <div class="flex items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="font-medium">{{ employees[i]?.name }} {{ employees[i]?.last_name }}</span>
                            <span class="text-xs text-muted-foreground font-mono">{{ employees[i]?.employee_number }}</span>
                            <StatusBadge v-if="isAlreadySaved(record.employee_id)" :status="savedStatus(record.employee_id)!" />
                        </div>
                        <div v-if="!isAlreadySaved(record.employee_id)" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="col-span-2 md:col-span-1">
                                <Label class="text-xs">Estado *</Label>
                                <Select v-model="record.status">
                                    <SelectTrigger class="mt-1 h-8 text-sm"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="s in STATUSES" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label class="text-xs">Entrada</Label>
                                <Input type="time" v-model="record.entry_time" class="mt-1 h-8 text-sm" />
                            </div>
                            <div>
                                <Label class="text-xs">Salida</Label>
                                <Input type="time" v-model="record.exit_time" class="mt-1 h-8 text-sm" />
                            </div>
                            <div>
                                <Label class="text-xs">Notas</Label>
                                <Input v-model="record.notes" placeholder="Opcional..." class="mt-1 h-8 text-sm" />
                            </div>
                        </div>
                        <div v-else class="text-sm text-muted-foreground">Ya registrado como: <StatusBadge :status="savedStatus(record.employee_id)!" /></div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="selectedClient && selectedSP" class="text-center py-12 text-muted-foreground">
            No hay colaboradores activos en este punto de servicio.
        </div>

        <div v-else class="text-center py-12 text-muted-foreground">
            Selecciona una empresa, punto de servicio y fecha para cargar colaboradores.
        </div>
    </div>
</template>
