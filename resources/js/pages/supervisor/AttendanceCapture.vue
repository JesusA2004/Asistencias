<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { AlertTriangle, Camera, ClipboardList, ShieldCheck, Users } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AttendanceActionSelector from '@/components/AttendanceActionSelector.vue';
import type {CaptureAction} from '@/components/AttendanceActionSelector.vue';
import AttendanceContextToolbar from '@/components/AttendanceContextToolbar.vue';
import AttendanceEmployeePicker from '@/components/AttendanceEmployeePicker.vue';
import AttendanceEntryPanel from '@/components/AttendanceEntryPanel.vue';
import type {EntryRecord} from '@/components/AttendanceEntryPanel.vue';
import AttendanceExitPanel from '@/components/AttendanceExitPanel.vue';
import type {ExitRecord} from '@/components/AttendanceExitPanel.vue';
import AttendanceGuidedEmptyState from '@/components/AttendanceGuidedEmptyState.vue';
import AttendanceIncidentPanel from '@/components/AttendanceIncidentPanel.vue';
import AttendanceManualPanel from '@/components/AttendanceManualPanel.vue';
import type {ManualRecord} from '@/components/AttendanceManualPanel.vue';
import AttendanceProgressSummary from '@/components/AttendanceProgressSummary.vue';
import AttendanceSummaryBar from '@/components/AttendanceSummaryBar.vue';
import AttendanceWarningDialog from '@/components/AttendanceWarningDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useInertiaLoading } from '@/composables/useInertiaLoading';
import { deriveCaptureState, nowTime, suggestEntryStatus } from '@/lib/attendance';
import { notify } from '@/lib/notify';
import type { Attendance, AttendanceStatus, Client, Employee, ServicePoint, Shift } from '@/types/models';

type EmployeeWithShift = Employee & { shift?: Pick<Shift, 'id' | 'name' | 'start_time' | 'end_time' | 'tolerance_minutes'> | null };

const props = defineProps<{
    clients: Client[];
    servicePoints: (ServicePoint & { client_id: number })[];
    employees: EmployeeWithShift[];
    existingAttendances: Record<number, Attendance>;
    filters: { client_id?: string; service_point_id?: string; date?: string };
    hasAssignments: boolean;
    canUseManualCapture: boolean;
    settings: {
        requires_photo: boolean;
        photo_per_employee: boolean;
        warning_text: string;
        warning_version: number;
        needs_warning_acceptance: boolean;
    };
}>();

const { isLoading } = useInertiaLoading();

const today = new Date().toISOString().split('T')[0];
const selectedClient = ref(props.filters.client_id ?? '');
const selectedSP = ref(props.filters.service_point_id ?? '');
const selectedDate = ref(props.filters.date ?? today);
const shiftFilter = ref<string | number | null>('');

const action = ref<CaptureAction | null>(null);
const selectedEmployeeIds = ref<number[]>([]);
const entryRecords = ref<Record<number, EntryRecord>>({});
const exitRecords = ref<Record<number, ExitRecord>>({});
const manualRecords = ref<Record<number, ManualRecord>>({});
const incidentStatus = ref<AttendanceStatus | null>(null);
const incidentNotes = ref('');
const manualReason = ref('');
const saving = ref(false);
const photos = ref<Record<number, File | null>>({});
const warningAccepted = ref(false);

const photosRequired = computed(() => props.settings.requires_photo);

const updatePhoto = (employeeId: number, file: File | null) => {
    photos.value = { ...photos.value, [employeeId]: file };
};

const filteredServicePoints = computed(() =>
    props.servicePoints.filter((sp) => !selectedClient.value || String(sp.client_id) === String(selectedClient.value)),
);

const clientOptions = computed(() => props.clients.map((c) => ({ value: c.id, label: c.name })));
const servicePointOptions = computed(() => filteredServicePoints.value.map((sp) => ({ value: sp.id, label: sp.name })));
const shiftOptions = computed(() => {
    const seen = new Map<number, string>();

    for (const e of props.employees) {
        if (e.shift) {
            seen.set(e.shift.id, e.shift.name);
        }
    }

    return Array.from(seen, ([value, label]) => ({ value, label }));
});

const employeesById = computed(() => new Map(props.employees.map((e) => [e.id, e])));

const employeesByShift = computed(() =>
    props.employees.filter((e) => !shiftFilter.value || String(e.shift_id) === String(shiftFilter.value)),
);

const stateOf = (employeeId: number) => deriveCaptureState(props.existingAttendances[employeeId]);

const entradaEligible = computed(() => employeesByShift.value.filter((e) => stateOf(e.id) === 'sin_registro'));
const salidaEligible = computed(() => employeesByShift.value.filter((e) => stateOf(e.id) === 'pendiente_salida'));
const incidenciaEligible = computed(() => employeesByShift.value.filter((e) => stateOf(e.id) === 'sin_registro'));
const manualEligible = computed(() => employeesByShift.value);

const actionCounts = computed(() => ({
    entrada: entradaEligible.value.length,
    salida: salidaEligible.value.length,
    incidencia: incidenciaEligible.value.length,
    manual: manualEligible.value.length,
}));

const currentEligible = computed<EmployeeWithShift[]>(() => {
    switch (action.value) {
        case 'entrada': return entradaEligible.value;
        case 'salida': return salidaEligible.value;
        case 'incidencia': return incidenciaEligible.value;
        case 'manual': return manualEligible.value;
        default: return [];
    }
});

const pickerOptions = computed(() =>
    currentEligible.value.map((e) => ({ value: e.id, label: `${e.name} ${e.last_name}`, description: e.employee_number })),
);

const contextMessages = computed(() => {
    if (!selectedClient.value || !selectedSP.value) {
        return [];
    }

    const total = employeesByShift.value.length;

    if (!total) {
        return [];
    }

    const withRecord = employeesByShift.value.filter((e) => stateOf(e.id) !== 'sin_registro').length;
    const complete = employeesByShift.value.filter((e) => stateOf(e.id) === 'completo').length;
    const msgs = [`Hay ${total} colaborador(es) activo(s) en este punto.`];

    if (withRecord) {
        msgs.push(`${withRecord} ya tienen algún registro para esta fecha.`);
    }

    if (complete) {
        msgs.push(`${complete} tienen asistencia completa (entrada y salida).`);
    }

    return msgs;
});

const currentStep = computed<1 | 2 | 3 | 4>(() => {
    if (!selectedClient.value || !selectedSP.value) {
        return 1;
    }

    if (!action.value) {
        return 2;
    }

    if (!selectedEmployeeIds.value.length) {
        return 3;
    }

    return 4;
});

// Cambiar de acción reinicia la selección y el estado capturado de las demás acciones.
watch(action, () => {
    selectedEmployeeIds.value = [];
    entryRecords.value = {};
    exitRecords.value = {};
    manualRecords.value = {};
    incidentStatus.value = null;
    incidentNotes.value = '';
    manualReason.value = '';
    photos.value = {};
});

watch(selectedEmployeeIds, (ids) => {
    if (action.value === 'entrada') {
        const next: Record<number, EntryRecord> = {};

        for (const id of ids) {
            const emp = employeesById.value.get(id);
            const time = entryRecords.value[id]?.entry_time ?? nowTime();
            next[id] = entryRecords.value[id] ?? {
                entry_time: time,
                status: suggestEntryStatus(time, emp?.shift ?? undefined),
                notes: '',
            };
        }

        entryRecords.value = next;
    } else if (action.value === 'salida') {
        const next: Record<number, ExitRecord> = {};

        for (const id of ids) {
            next[id] = exitRecords.value[id] ?? { exit_time: nowTime(), notes: '' };
        }

        exitRecords.value = next;
    } else if (action.value === 'manual') {
        const next: Record<number, ManualRecord> = {};

        for (const id of ids) {
            const existing = props.existingAttendances[id];
            next[id] = manualRecords.value[id] ?? {
                status: existing?.status ?? 'presente',
                entry_time: existing?.entry_time ?? '',
                exit_time: existing?.exit_time ?? '',
                notes: existing?.notes ?? '',
            };
        }

        manualRecords.value = next;
    }
});

const updateEntryRecord = (id: number, patch: Partial<EntryRecord>) => {
    entryRecords.value = { ...entryRecords.value, [id]: { ...entryRecords.value[id], ...patch } };
};
const updateExitRecord = (id: number, patch: Partial<ExitRecord>) => {
    exitRecords.value = { ...exitRecords.value, [id]: { ...exitRecords.value[id], ...patch } };
};
const updateManualRecord = (id: number, patch: Partial<ManualRecord>) => {
    manualRecords.value = { ...manualRecords.value, [id]: { ...manualRecords.value[id], ...patch } };
};

const selectedEntryEmployees = computed(() => selectedEmployeeIds.value.map((id) => {
    const e = employeesById.value.get(id)!;

    return { id: e.id, name: `${e.name} ${e.last_name}`, employeeNumber: e.employee_number, shiftName: e.shift?.name };
}));

const selectedExitEmployees = computed(() => selectedEmployeeIds.value.map((id) => {
    const e = employeesById.value.get(id)!;

    return {
        id: e.id, name: `${e.name} ${e.last_name}`, employeeNumber: e.employee_number, shiftName: e.shift?.name,
        entryTime: props.existingAttendances[id]?.entry_time ?? null,
    };
}));

const selectedManualEmployees = computed(() => selectedEmployeeIds.value.map((id) => {
    const e = employeesById.value.get(id)!;

    return {
        id: e.id, name: `${e.name} ${e.last_name}`, employeeNumber: e.employee_number, shiftName: e.shift?.name,
        hasExisting: !!props.existingAttendances[id],
    };
}));

const selectedIncidentEmployees = computed(() => selectedEmployeeIds.value.map((id) => {
    const e = employeesById.value.get(id)!;

    return { id: e.id, name: `${e.name} ${e.last_name}` };
}));

const manualAnyExisting = computed(() => selectedEmployeeIds.value.some((id) => !!props.existingAttendances[id]));

const incidentRequiresNotes = computed(() => incidentStatus.value === 'permiso' || incidentStatus.value === 'incapacidad');

const photosCapturedCount = computed(() => selectedEmployeeIds.value.filter((id) => !!photos.value[id]).length);

const missingRequiredPhotos = computed(() => {
    if (!photosRequired.value) {
        return false;
    }

    if (props.settings.photo_per_employee) {
        return photosCapturedCount.value < selectedEmployeeIds.value.length;
    }

    return photosCapturedCount.value < 1;
});

const canSubmit = computed(() => {
    if (!selectedEmployeeIds.value.length) {
        return false;
    }

    if (missingRequiredPhotos.value) {
        return false;
    }

    if (action.value === 'incidencia') {
        if (!incidentStatus.value) {
            return false;
        }

        if (incidentRequiresNotes.value && incidentNotes.value.trim().length < 5) {
            return false;
        }

        return true;
    }

    if (action.value === 'manual' && manualAnyExisting.value && manualReason.value.trim().length < 10) {
        return false;
    }

    return true;
});

const disabledReason = computed(() => {
    if (missingRequiredPhotos.value) {
        return props.settings.photo_per_employee
            ? 'Faltan fotografías por capturar: toma una foto por cada colaborador seleccionado.'
            : 'Faltan fotografías por capturar: captura al menos una fotografía de evidencia.';
    }

    if (action.value === 'incidencia') {
        if (!incidentStatus.value) {
            return 'Selecciona el tipo de incidencia.';
        }

        if (incidentRequiresNotes.value && incidentNotes.value.trim().length < 5) {
            return 'Escribe el motivo de la incidencia (mínimo 5 caracteres).';
        }
    }

    if (action.value === 'manual' && manualAnyExisting.value && manualReason.value.trim().length < 10) {
        return 'Indica el motivo de la corrección (mínimo 10 caracteres).';
    }

    return undefined;
});

const needsWarning = computed(() => photosRequired.value && props.settings.needs_warning_acceptance && !warningAccepted.value);

const acceptWarning = () => {
    router.post(
        '/asistencias/aceptar-aviso',
        { context: 'supervisor' },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                warningAccepted.value = true;
            },
        },
    );
};

const summaryLabel = computed(() => ({
    entrada: 'listos para registrar entrada',
    salida: 'listos para registrar salida',
    incidencia: 'seleccionados para la incidencia',
    manual: 'seleccionados para captura manual',
}[action.value ?? 'entrada']));

const submitLabel = computed(() => ({
    entrada: 'Registrar entrada',
    salida: 'Registrar salida',
    incidencia: 'Guardar incidencia',
    manual: 'Guardar captura manual',
}[action.value ?? 'entrada']));

const loadEmployees = (notifyChange = false) => {
    const hadSelection = selectedEmployeeIds.value.length > 0;
    selectedEmployeeIds.value = [];
    action.value = null;
    router.get('/asistencias/capturar', {
        client_id: selectedClient.value || undefined,
        service_point_id: selectedSP.value || undefined,
        date: selectedDate.value,
    }, {
        preserveState: true,
        replace: true,
        onSuccess: () => {
            if (notifyChange && hadSelection) {
                notify.info('Se limpió la selección de colaboradores al cambiar de contexto.');
            }
        },
    });
};

const onClientChange = (value: string | number | null) => {
    selectedClient.value = value == null ? '' : String(value);
    selectedSP.value = '';
    loadEmployees(true);
};

const onServicePointChange = (value: string | number | null) => {
    selectedSP.value = value == null ? '' : String(value);
    loadEmployees(true);
};

const onDateChange = (value: string | null) => {
    selectedDate.value = value ?? today;
    loadEmployees(false);
};

const submit = () => {
    if (!canSubmit.value || !action.value) {
        return;
    }

    saving.value = true;

    const base = {
        client_id: selectedClient.value,
        service_point_id: selectedSP.value,
        attendance_date: selectedDate.value,
    };

    const onFinish = () => {
 saving.value = false;
};
    const onSuccess = () => {
        // El toast/alerta de éxito o error ya lo dispara el handler global de flash.
        selectedEmployeeIds.value = [];
        photos.value = {};
    };

    const photosPayload = photosRequired.value
        ? Object.fromEntries(selectedEmployeeIds.value.filter((id) => photos.value[id]).map((id) => [id, photos.value[id]]))
        : undefined;

    if (action.value === 'entrada') {
        router.post('/asistencias/capturar/entrada', {
            ...base,
            entries: selectedEmployeeIds.value.map((id) => ({ employee_id: id, ...entryRecords.value[id] })),
            photos: photosPayload,
        }, { preserveScroll: true, onSuccess, onFinish });
    } else if (action.value === 'salida') {
        router.post('/asistencias/capturar/salida', {
            ...base,
            exits: selectedEmployeeIds.value.map((id) => ({ employee_id: id, ...exitRecords.value[id] })),
            photos: photosPayload,
        }, { preserveScroll: true, onSuccess, onFinish });
    } else if (action.value === 'incidencia') {
        router.post('/asistencias/capturar/incidencia', {
            ...base,
            status: incidentStatus.value,
            notes: incidentNotes.value || undefined,
            employee_ids: selectedEmployeeIds.value,
            photos: photosPayload,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                onSuccess();
                incidentStatus.value = null;
                incidentNotes.value = '';
            },
            onFinish,
        });
    } else if (action.value === 'manual') {
        router.post('/asistencias/capturar/manual', {
            ...base,
            reason: manualReason.value || undefined,
            records: selectedEmployeeIds.value.map((id) => ({ employee_id: id, ...manualRecords.value[id] })),
            photos: photosPayload,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                onSuccess();
                manualReason.value = '';
            },
            onFinish,
        });
    }
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

        <Alert v-if="photosRequired" class="mb-6 border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/40">
            <Camera class="h-4 w-4 text-amber-700 dark:text-amber-400" />
            <AlertTitle class="text-amber-800 dark:text-amber-300">Evidencia fotográfica obligatoria</AlertTitle>
            <AlertDescription class="text-amber-800/90 dark:text-amber-300/80">
                Deberás tomar una fotografía por cada colaborador seleccionado antes de guardar.
            </AlertDescription>
        </Alert>

        <AttendanceGuidedEmptyState
            v-if="!clients.length"
            title="Sin empresas disponibles"
            :description="noClientsMessage"
            :icon="AlertTriangle"
        />

        <template v-else>
            <AttendanceProgressSummary :current-step="currentStep" />

            <AttendanceContextToolbar
                class="mb-6"
                :client-id="selectedClient"
                :service-point-id="selectedSP"
                :date="selectedDate"
                :shift-filter="shiftFilter"
                :client-options="clientOptions"
                :service-point-options="servicePointOptions"
                :shift-options="shiftOptions"
                :max-date="today"
                :loading="isLoading"
                :messages="contextMessages"
                @update:client-id="onClientChange"
                @update:service-point-id="onServicePointChange"
                @update:date="onDateChange"
                @update:shift-filter="shiftFilter = $event"
            />

            <template v-if="!selectedClient || !selectedSP">
                <AttendanceGuidedEmptyState
                    title="¿Dónde vas a capturar?"
                    description="Selecciona empresa y punto para cargar colaboradores."
                    :icon="ClipboardList"
                />
            </template>
            <template v-else-if="!employeesByShift.length">
                <AttendanceGuidedEmptyState
                    title="No hay colaboradores activos"
                    description="Este punto de servicio no tiene colaboradores activos disponibles para capturar."
                    :icon="Users"
                />
            </template>
            <template v-else>
                <div class="mb-6">
                    <AttendanceActionSelector
                        v-model="action"
                        :counts="actionCounts"
                        :can-use-manual="canUseManualCapture"
                    />
                </div>

                <template v-if="action">
                    <template v-if="!currentEligible.length">
                        <AttendanceGuidedEmptyState
                            title="No hay colaboradores elegibles para esta acción"
                            description="Prueba con otra acción o cambia el filtro de turno arriba."
                            next-step-hint="Elige otra acción en el paso 2."
                            :icon="Users"
                        />
                    </template>
                    <template v-else>
                        <div class="mb-6">
                            <AttendanceEmployeePicker v-model="selectedEmployeeIds" :options="pickerOptions" />
                        </div>

                        <div v-if="photosRequired && selectedEmployeeIds.length" class="mb-4 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300">
                            <Camera class="h-4 w-4" />
                            <span>{{ photosCapturedCount }} de {{ selectedEmployeeIds.length }} fotografías capturadas</span>
                        </div>

                        <template v-if="!selectedEmployeeIds.length">
                            <AttendanceGuidedEmptyState
                                title="Selecciona colaboradores para continuar"
                                description="Busca y selecciona uno o varios colaboradores arriba para capturar su información."
                                next-step-hint="Usa el buscador o 'Seleccionar todos los disponibles'."
                                :icon="ClipboardList"
                            />
                        </template>
                        <template v-else>
                            <AttendanceEntryPanel
                                v-if="action === 'entrada'"
                                :employees="selectedEntryEmployees"
                                :records="entryRecords"
                                :photos-required="photosRequired"
                                :photos="photos"
                                @update="updateEntryRecord"
                                @photo-update="updatePhoto"
                            />
                            <AttendanceExitPanel
                                v-else-if="action === 'salida'"
                                :employees="selectedExitEmployees"
                                :records="exitRecords"
                                :photos-required="photosRequired"
                                :photos="photos"
                                @update="updateExitRecord"
                                @photo-update="updatePhoto"
                            />
                            <AttendanceIncidentPanel
                                v-else-if="action === 'incidencia'"
                                :employees="selectedIncidentEmployees"
                                :status="incidentStatus"
                                :notes="incidentNotes"
                                :photos-required="photosRequired"
                                :photos="photos"
                                @update:status="incidentStatus = $event"
                                @update:notes="incidentNotes = $event"
                                @photo-update="updatePhoto"
                            />
                            <AttendanceManualPanel
                                v-else-if="action === 'manual'"
                                :employees="selectedManualEmployees"
                                :records="manualRecords"
                                :reason="manualReason"
                                :any-existing="manualAnyExisting"
                                :photos-required="photosRequired"
                                :photos="photos"
                                @update="updateManualRecord"
                                @update:reason="manualReason = $event"
                                @photo-update="updatePhoto"
                            />

                            <AttendanceSummaryBar
                                :count="selectedEmployeeIds.length"
                                :count-label="summaryLabel"
                                :submit-label="submitLabel"
                                :saving="saving"
                                :disabled="!canSubmit"
                                :disabled-reason="disabledReason"
                                :photos-captured="photosRequired ? photosCapturedCount : undefined"
                                :photos-total="photosRequired ? selectedEmployeeIds.length : undefined"
                                @save="submit"
                            />

                            <p v-if="photosRequired" class="mt-3 flex items-center gap-1.5 text-xs text-muted-foreground">
                                <ShieldCheck class="h-3.5 w-3.5" />
                                Las evidencias fotográficas solo son visibles para personal autorizado.
                            </p>
                        </template>
                    </template>
                </template>
            </template>
        </template>

        <AttendanceWarningDialog
            :open="needsWarning"
            :warning-text="settings.warning_text"
            @accept="acceptWarning"
            @cancel="action = null"
        />
    </div>
</template>
