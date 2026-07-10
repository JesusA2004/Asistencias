<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { CheckCircle2, ClipboardList, Save, Users, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DatePicker from '@/components/DatePicker.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchableMultiSelect from '@/components/SearchableMultiSelect.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatDateMx } from '@/lib/formatters';
import type { Attendance, AttendanceStatus, Client, Employee, ServicePoint } from '@/types/models';

const props = defineProps<{
    clients: Client[];
    servicePoints: (ServicePoint & { client_id: number })[];
    employees: Employee[];
    existingAttendances: Record<number, Attendance>;
    filters: { client_id?: string; service_point_id?: string; date?: string };
}>();

const today = new Date().toISOString().split('T')[0];
const selectedClient = ref(props.filters.client_id ?? '');
const selectedSP = ref(props.filters.service_point_id ?? '');
const selectedDate = ref(props.filters.date ?? today);

// Los puntos de servicio ya vienen todos cargados desde el backend; se filtran
// aquí por empresa sin recargar la página (instantáneo, no se siente lento).
const filteredServicePoints = computed(() =>
    props.servicePoints.filter((sp) => !selectedClient.value || String(sp.client_id) === String(selectedClient.value)),
);

const clientOptions = computed(() => props.clients.map((c) => ({ value: c.id, label: c.name })));
const servicePointOptions = computed(() => filteredServicePoints.value.map((sp) => ({ value: sp.id, label: sp.name })));

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

// Colaboradores que el supervisor eligió agregar a la lista de captura.
const selectedEmployeeIds = ref<number[]>([]);
const records = ref<Map<number, RecordEntry>>(new Map());

const isAlreadySaved = (employeeId: number) => !!props.existingAttendances[employeeId];
const savedStatus = (employeeId: number) => props.existingAttendances[employeeId]?.status;

const employeesById = computed(() => new Map(props.employees.map((e) => [e.id, e])));
const alreadySavedEmployees = computed(() => props.employees.filter((e) => isAlreadySaved(e.id)));

const employeeOptions = computed(() =>
    props.employees
        .filter((e) => !isAlreadySaved(e.id))
        .map((e) => ({
            value: e.id,
            label: `${e.name} ${e.last_name}`,
            description: e.employee_number,
        })),
);

// Mantiene `records` sincronizado con la selección de colaboradores, sin perder
// lo ya capturado para los que siguen seleccionados.
watch(selectedEmployeeIds, (ids) => {
    const next = new Map<number, RecordEntry>();
    for (const id of ids) {
        next.set(id, records.value.get(id) ?? {
            employee_id: id,
            status: 'presente',
            entry_time: '',
            exit_time: '',
            notes: '',
        });
    }
    records.value = next;
}, { deep: false });

const recordList = computed(() => selectedEmployeeIds.value.map((id) => records.value.get(id)!).filter(Boolean));

const addAllFromPoint = () => {
    const allIds = props.employees.filter((e) => !isAlreadySaved(e.id)).map((e) => e.id);
    selectedEmployeeIds.value = Array.from(new Set([...selectedEmployeeIds.value, ...allIds]));
};

const clearSelection = () => {
    selectedEmployeeIds.value = [];
};

const applyToSelected = (status: AttendanceStatus) => {
    recordList.value.forEach((r) => {
        r.status = status;
    });
};

const applyToAllInPoint = (status: AttendanceStatus) => {
    const allIds = props.employees.filter((e) => !isAlreadySaved(e.id)).map((e) => e.id);
    const next = new Map<number, RecordEntry>();
    for (const id of allIds) {
        const existing = records.value.get(id);
        next.set(id, existing ? { ...existing, status } : { employee_id: id, status, entry_time: '', exit_time: '', notes: '' });
    }
    records.value = next;
    selectedEmployeeIds.value = allIds;
};

const form = useForm({
    client_id: selectedClient.value,
    service_point_id: selectedSP.value,
    attendance_date: selectedDate.value,
    records: [] as RecordEntry[],
});

const loadEmployees = () => {
    selectedEmployeeIds.value = [];
    router.get('/asistencias/capturar', {
        client_id: selectedClient.value || undefined,
        service_point_id: selectedSP.value || undefined,
        date: selectedDate.value,
    }, { preserveState: true, replace: true });
};

const onClientChange = (value: string | number | null) => {
    selectedClient.value = value == null ? '' : String(value);
    selectedSP.value = '';
    loadEmployees();
};

const onServicePointChange = (value: string | number | null) => {
    selectedSP.value = value == null ? '' : String(value);
    loadEmployees();
};

const onDateChange = (value: string | null) => {
    selectedDate.value = value ?? today;
    loadEmployees();
};

const submit = () => {
    form.client_id = selectedClient.value;
    form.service_point_id = selectedSP.value;
    form.attendance_date = selectedDate.value;
    form.records = recordList.value;
    form.post('/asistencias/capturar', {
        onSuccess: () => {
            selectedEmployeeIds.value = [];
        },
    });
};
</script>

<template>
    <div class="p-6 max-w-5xl mx-auto">
        <PageHeader title="Capturar Asistencia" description="Registra la asistencia diaria de tus colaboradores">
            <template #actions>
                <Button v-if="recordList.length" @click="submit" :disabled="form.processing">
                    <Save class="h-4 w-4 mr-2" />
                    {{ form.processing ? 'Guardando...' : `Guardar ${recordList.length} registros` }}
                </Button>
            </template>
        </PageHeader>

        <!-- Paso 1: empresa, punto y fecha -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 bg-muted/30 p-4 rounded-lg border">
            <SearchableSelect
                :model-value="selectedClient"
                :options="clientOptions"
                label="Empresa"
                required
                placeholder="Selecciona empresa..."
                :clearable="false"
                @update:model-value="onClientChange"
            />
            <SearchableSelect
                :model-value="selectedSP"
                :options="servicePointOptions"
                label="Punto de Servicio"
                required
                placeholder="Selecciona punto..."
                :disabled="!selectedClient"
                :clearable="false"
                @update:model-value="onServicePointChange"
            />
            <FormField label="Fecha" required>
                <DatePicker :model-value="selectedDate" :max-value="today" @update:model-value="onDateChange" />
            </FormField>
        </div>

        <template v-if="selectedClient && selectedSP">
            <!-- Paso 2: elegir colaboradores -->
            <div class="mb-6 space-y-2">
                <SearchableMultiSelect
                    v-model="selectedEmployeeIds"
                    :options="employeeOptions"
                    label="Colaboradores a capturar"
                    search-placeholder="Buscar por nombre, apellido o número de empleado..."
                    empty-text="No hay colaboradores disponibles"
                />
                <div class="flex flex-wrap gap-2">
                    <Button type="button" variant="outline" size="sm" @click="addAllFromPoint">
                        <Users class="h-3.5 w-3.5 mr-1.5" /> Agregar todos los colaboradores del punto
                    </Button>
                    <Button v-if="selectedEmployeeIds.length" type="button" variant="ghost" size="sm" @click="clearSelection">
                        <X class="h-3.5 w-3.5 mr-1.5" /> Limpiar selección
                    </Button>
                </div>
            </div>

            <!-- Ya registrados -->
            <Alert v-if="alreadySavedEmployees.length" class="mb-6 bg-amber-50 border-amber-200 dark:bg-amber-950/40 dark:border-amber-900">
                <CheckCircle2 class="h-4 w-4 text-amber-600" />
                <AlertDescription class="text-amber-700 dark:text-amber-400">
                    <p class="mb-2">
                        {{ alreadySavedEmployees.length }} colaborador(es) ya tienen asistencia registrada el {{ formatDateMx(selectedDate) }}. No se pueden volver a capturar.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="e in alreadySavedEmployees"
                            :key="e.id"
                            class="flex items-center gap-1.5 bg-background/60 rounded-full pl-2 pr-1 py-0.5 border border-amber-200 dark:border-amber-900"
                        >
                            <span class="text-xs">{{ e.name }} {{ e.last_name }}</span>
                            <StatusBadge :status="savedStatus(e.id)!" />
                        </div>
                    </div>
                </AlertDescription>
            </Alert>

            <!-- Acciones rápidas -->
            <div v-if="recordList.length" class="flex gap-2 mb-4 flex-wrap items-center">
                <span class="text-sm text-muted-foreground mr-2">Marcar seleccionados como:</span>
                <button
                    v-for="s in STATUSES.slice(0, 2)"
                    :key="s.value"
                    type="button"
                    @click="applyToSelected(s.value)"
                    :class="['px-3 py-1.5 text-xs font-medium rounded-full border transition-colors', s.color]"
                >
                    {{ s.label }}
                </button>
                <span class="text-muted-foreground mx-1">|</span>
                <button
                    type="button"
                    @click="applyToAllInPoint('presente')"
                    class="px-3 py-1.5 text-xs font-medium rounded-full border transition-colors bg-green-600 text-white border-green-600 hover:bg-green-700"
                >
                    Marcar todos como presente
                </button>
            </div>

            <!-- Paso 3: lista de captura -->
            <div v-if="recordList.length" class="space-y-3">
                <Card
                    v-for="record in recordList"
                    :key="record.employee_id"
                    class="p-4 border shadow-sm hover:shadow-md transition-all"
                >
                    <div class="flex items-center gap-2 mb-3">
                        <span class="font-medium">
                            {{ employeesById.get(record.employee_id)?.name }} {{ employeesById.get(record.employee_id)?.last_name }}
                        </span>
                        <span class="text-xs text-muted-foreground font-mono">{{ employeesById.get(record.employee_id)?.employee_number }}</span>
                    </div>
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
                </Card>
            </div>

            <div v-else-if="!employeeOptions.length && !alreadySavedEmployees.length" class="text-center py-12 text-muted-foreground">
                No hay colaboradores activos en este punto de servicio.
            </div>

            <div v-else-if="!recordList.length" class="text-center py-12 text-muted-foreground border rounded-lg border-dashed">
                <ClipboardList class="h-8 w-8 mx-auto mb-2 opacity-50" />
                Busca y selecciona colaboradores arriba para comenzar a capturar.
            </div>
        </template>

        <div v-else class="text-center py-12 text-muted-foreground">
            Selecciona una empresa, punto de servicio y fecha para cargar colaboradores.
        </div>
    </div>
</template>
