<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, ClipboardList, Users, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import AttendanceBulkActions from '@/components/AttendanceBulkActions.vue';
import AttendanceEmployeeCard from '@/components/AttendanceEmployeeCard.vue';
import AttendanceFiltersToolbar from '@/components/AttendanceFiltersToolbar.vue';
import AttendanceSummaryBar from '@/components/AttendanceSummaryBar.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchableMultiSelect from '@/components/SearchableMultiSelect.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useInertiaLoading } from '@/composables/useInertiaLoading';
import { formatDateMx } from '@/lib/formatters';
import type { Attendance, AttendanceStatus, Client, Employee, ServicePoint } from '@/types/models';

const props = defineProps<{
    clients: Client[];
    servicePoints: (ServicePoint & { client_id: number })[];
    employees: (Employee & { shift?: { id: number; name: string } | null })[];
    existingAttendances: Record<number, Attendance>;
    filters: { client_id?: string; service_point_id?: string; date?: string };
    hasAssignments: boolean;
}>();

const { isLoading } = useInertiaLoading();

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

interface RecordEntry {
    employee_id: number;
    status: AttendanceStatus;
    entry_time: string;
    exit_time: string;
    notes: string;
}

const selectedEmployeeIds = ref<number[]>([]);
const records = ref<Map<number, RecordEntry>>(new Map());

const isAlreadySaved = (employeeId: number) => !!props.existingAttendances[employeeId];
const savedStatus = (employeeId: number) => props.existingAttendances[employeeId]?.status;

const employeesById = computed(() => new Map(props.employees.map((e) => [e.id, e])));
const alreadySavedEmployees = computed(() => props.employees.filter((e) => isAlreadySaved(e.id)));
const availableEmployees = computed(() => props.employees.filter((e) => !isAlreadySaved(e.id)));

const employeeOptions = computed(() =>
    availableEmployees.value.map((e) => ({
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
    const allIds = availableEmployees.value.map((e) => e.id);
    selectedEmployeeIds.value = Array.from(new Set([...selectedEmployeeIds.value, ...allIds]));
};

const clearSelection = () => {
    selectedEmployeeIds.value = [];
};

const markSelected = (status: AttendanceStatus) => {
    recordList.value.forEach((r) => {
        r.status = status;
    });
};

const markAll = (status: AttendanceStatus) => {
    const allIds = availableEmployees.value.map((e) => e.id);
    const next = new Map<number, RecordEntry>();

    for (const id of allIds) {
        const existing = records.value.get(id);
        next.set(id, existing ? { ...existing, status } : { employee_id: id, status, entry_time: '', exit_time: '', notes: '' });
    }

    records.value = next;
    selectedEmployeeIds.value = allIds;
};

const clearStates = () => {
    recordList.value.forEach((r) => {
        r.status = 'presente';
        r.entry_time = '';
        r.exit_time = '';
        r.notes = '';
    });
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
        onSuccess: (page) => {
            selectedEmployeeIds.value = [];
            const flash = (page.props.flash ?? {}) as { success?: string | null; error?: string | null };

            if (flash.success) {
                toast.success(flash.success);
            }

            if (flash.error) {
                toast.error(flash.error);
            }
        },
    });
};

const noClientsMessage = computed(() =>
    props.hasAssignments
        ? 'No hay empresas activas registradas en el sistema.'
        : 'No tienes empresas asignadas. Solicita acceso a un administrador.',
);
</script>

<template>
    <div class="w-full p-6">
        <PageHeader title="Capturar Asistencia" description="Registra la asistencia diaria de tus colaboradores" />

        <EmptyState
            v-if="!clients.length"
            title="Sin empresas disponibles"
            :description="noClientsMessage"
            :icon="AlertTriangle"
        />

        <template v-else>
            <AttendanceFiltersToolbar
                class="mb-6"
                :client-id="selectedClient"
                :service-point-id="selectedSP"
                :date="selectedDate"
                :client-options="clientOptions"
                :service-point-options="servicePointOptions"
                :max-date="today"
                :loading="isLoading"
                @update:client-id="onClientChange"
                @update:service-point-id="onServicePointChange"
                @update:date="onDateChange"
            />

            <template v-if="selectedClient && selectedSP">
                <!-- Paso 2: elegir colaboradores -->
                <div class="mb-6 space-y-2 rounded-xl border bg-card p-5 shadow-sm">
                    <h2 class="mb-1 text-sm font-semibold text-foreground">Paso 2 · Selecciona colaboradores</h2>
                    <SearchableMultiSelect
                        v-model="selectedEmployeeIds"
                        :options="employeeOptions"
                        search-placeholder="Buscar por nombre, apellido o número de empleado..."
                        empty-text="No hay colaboradores disponibles"
                    />
                    <div class="flex flex-wrap gap-2 pt-1">
                        <Button type="button" variant="outline" size="sm" @click="addAllFromPoint">
                            <Users class="mr-1.5 h-3.5 w-3.5" /> Seleccionar todos los colaboradores del punto
                        </Button>
                        <Button v-if="selectedEmployeeIds.length" type="button" variant="ghost" size="sm" @click="clearSelection">
                            <X class="mr-1.5 h-3.5 w-3.5" /> Limpiar selección
                        </Button>
                    </div>
                </div>

                <!-- Ya registrados -->
                <Alert v-if="alreadySavedEmployees.length" class="mb-6 border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/40">
                    <CheckCircle2 class="h-4 w-4 text-amber-600" />
                    <AlertDescription class="text-amber-700 dark:text-amber-400">
                        <p class="mb-2">
                            {{ alreadySavedEmployees.length }} colaborador(es) ya tienen asistencia registrada el {{ formatDateMx(selectedDate) }}. No se pueden volver a capturar.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <div
                                v-for="e in alreadySavedEmployees"
                                :key="e.id"
                                class="flex items-center gap-1.5 rounded-full border border-amber-200 bg-background/60 py-0.5 pr-1 pl-2 dark:border-amber-900"
                            >
                                <span class="text-xs">{{ e.name }} {{ e.last_name }}</span>
                                <StatusBadge :status="savedStatus(e.id)!" />
                            </div>
                        </div>
                    </AlertDescription>
                </Alert>

                <template v-if="!availableEmployees.length">
                    <EmptyState
                        title="No hay colaboradores activos"
                        description="Este punto de servicio no tiene colaboradores activos disponibles para capturar."
                    />
                </template>
                <template v-else-if="!recordList.length">
                    <EmptyState
                        title="Selecciona colaboradores para comenzar"
                        description="Busca y selecciona uno o varios colaboradores arriba para agregarlos a la lista de captura."
                        :icon="ClipboardList"
                    />
                </template>
                <template v-else>
                    <!-- Paso 3: acciones masivas -->
                    <div class="mb-4">
                        <AttendanceBulkActions
                            :selected-count="selectedEmployeeIds.length"
                            :has-records="!!recordList.length"
                            @mark-selected="markSelected"
                            @mark-all="markAll"
                            @clear-states="clearStates"
                            @add-all="addAllFromPoint"
                        />
                    </div>

                    <!-- Paso 4: lista de captura -->
                    <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                        <AttendanceEmployeeCard
                            v-for="record in recordList"
                            :key="record.employee_id"
                            :name="`${employeesById.get(record.employee_id)?.name} ${employeesById.get(record.employee_id)?.last_name}`"
                            :employee-number="employeesById.get(record.employee_id)?.employee_number ?? ''"
                            :shift-name="employeesById.get(record.employee_id)?.shift?.name"
                            :status="record.status"
                            :entry-time="record.entry_time"
                            :exit-time="record.exit_time"
                            :notes="record.notes"
                            @update:status="record.status = $event"
                            @update:entry-time="record.entry_time = $event"
                            @update:exit-time="record.exit_time = $event"
                            @update:notes="record.notes = $event"
                        />
                    </div>

                    <AttendanceSummaryBar
                        :total-selected="selectedEmployeeIds.length"
                        :total-new="recordList.length"
                        :total-already-registered="alreadySavedEmployees.length"
                        :saving="form.processing"
                        @save="submit"
                    />
                </template>
            </template>

            <EmptyState
                v-else
                title="Selecciona empresa, punto y fecha para comenzar"
                description="Elige la empresa, el punto de servicio y la fecha arriba para cargar a los colaboradores."
                :icon="ClipboardList"
            />
        </template>
    </div>
</template>
