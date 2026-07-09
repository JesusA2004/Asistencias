<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { CheckCircle2, ClipboardList, Save } from '@lucide/vue';
import { ref, computed } from 'vue';
import DatePicker from '@/components/DatePicker.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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

const STATUSES: { value: AttendanceStatus; label: string; color: string; activeColor: string }[] = [
    { value: 'presente', label: 'Presente', color: 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100 dark:bg-green-950/40 dark:text-green-400 dark:border-green-900', activeColor: 'bg-green-600 text-white border-green-600 hover:bg-green-600' },
    { value: 'falta', label: 'Falta', color: 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-400 dark:border-red-900', activeColor: 'bg-red-600 text-white border-red-600 hover:bg-red-600' },
    { value: 'retardo', label: 'Retardo', color: 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-900', activeColor: 'bg-purple-600 text-white border-purple-600 hover:bg-purple-600' },
    { value: 'descanso', label: 'Descanso', color: 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900', activeColor: 'bg-blue-600 text-white border-blue-600 hover:bg-blue-600' },
    { value: 'permiso', label: 'Permiso', color: 'bg-yellow-50 text-yellow-700 border-yellow-200 hover:bg-yellow-100 dark:bg-yellow-950/40 dark:text-yellow-400 dark:border-yellow-900', activeColor: 'bg-yellow-600 text-white border-yellow-600 hover:bg-yellow-600' },
    { value: 'incapacidad', label: 'Incapacidad', color: 'bg-orange-50 text-orange-700 border-orange-200 hover:bg-orange-100 dark:bg-orange-950/40 dark:text-orange-400 dark:border-orange-900', activeColor: 'bg-orange-600 text-white border-orange-600 hover:bg-orange-600' },
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
    records.value.forEach((r) => {
 r.status = status; 
});
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
            <FormField label="Empresa" required>
                <Select v-model="selectedClient" @update:model-value="selectedSP = ''; loadEmployees()">
                    <SelectTrigger class="w-full"><SelectValue placeholder="Selecciona empresa..." /></SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="c in clients" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
            <FormField label="Punto de Servicio" required>
                <Select v-model="selectedSP" :disabled="!servicePoints.length" @update:model-value="loadEmployees()">
                    <SelectTrigger class="w-full"><SelectValue placeholder="Selecciona punto..." /></SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="sp in servicePoints" :key="sp.id" :value="String(sp.id)">{{ sp.name }}</SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
            <FormField label="Fecha" required>
                <DatePicker v-model="selectedDate" :max-value="today" @update:model-value="loadEmployees()" />
            </FormField>
            <div class="flex items-end">
                <Button variant="outline" class="w-full" @click="loadEmployees">
                    <ClipboardList class="h-4 w-4 mr-2" /> Cargar
                </Button>
            </div>
        </div>

        <!-- Already saved warning -->
        <Alert v-if="alreadySaved" class="mb-4 bg-amber-50 border-amber-200 dark:bg-amber-950/40 dark:border-amber-900">
            <CheckCircle2 class="h-4 w-4 text-amber-600" />
            <AlertDescription class="text-amber-700 dark:text-amber-400">
                Ya registraste asistencias para esta fecha y punto de servicio. Solo el administrador puede hacer correcciones.
            </AlertDescription>
        </Alert>

        <!-- Quick action -->
        <div v-if="employees.length && !alreadySaved" class="flex gap-2 mb-4 flex-wrap items-center">
            <span class="text-sm text-muted-foreground mr-2">Aplicar a todos:</span>
            <button
                v-for="s in STATUSES"
                :key="s.value"
                type="button"
                @click="applyToAll(s.value)"
                :class="['px-3 py-1.5 text-xs font-medium rounded-full border transition-colors', s.color]"
            >
                {{ s.label }}
            </button>
        </div>

        <!-- Employee list -->
        <div v-if="employees.length" class="space-y-3">
            <div
                v-for="(record, i) in records"
                :key="record.employee_id"
                class="bg-card border rounded-lg p-4 shadow-sm"
                :class="{ 'opacity-70': isAlreadySaved(record.employee_id) }"
            >
                <div class="flex items-center gap-2 mb-3">
                    <span class="font-medium">{{ employees[i]?.name }} {{ employees[i]?.last_name }}</span>
                    <span class="text-xs text-muted-foreground font-mono">{{ employees[i]?.employee_number }}</span>
                    <StatusBadge v-if="isAlreadySaved(record.employee_id)" :status="savedStatus(record.employee_id)!" />
                </div>
                <template v-if="!isAlreadySaved(record.employee_id)">
                    <div class="flex gap-1.5 flex-wrap mb-3">
                        <button
                            v-for="s in STATUSES"
                            :key="s.value"
                            type="button"
                            @click="record.status = s.value"
                            :class="['px-2.5 py-1 text-xs font-medium rounded-full border transition-colors', record.status === s.value ? s.activeColor : s.color]"
                        >
                            {{ s.label }}
                        </button>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <FormField label="Entrada" class="text-xs">
                            <Input type="time" v-model="record.entry_time" class="h-8 text-sm" />
                        </FormField>
                        <FormField label="Salida" class="text-xs">
                            <Input type="time" v-model="record.exit_time" class="h-8 text-sm" />
                        </FormField>
                        <FormField label="Notas" class="text-xs col-span-2 md:col-span-1">
                            <Input v-model="record.notes" placeholder="Opcional..." class="h-8 text-sm" />
                        </FormField>
                    </div>
                </template>
                <div v-else class="text-sm text-muted-foreground">
                    Ya registrado como: <StatusBadge :status="savedStatus(record.employee_id)!" />
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
