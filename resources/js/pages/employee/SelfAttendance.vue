<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Calendar, Camera, CheckCircle2, Clock, LogIn, LogOut, MapPin, ShieldAlert, ShieldCheck, UserX, XCircle } from '@lucide/vue';
import { computed, ref } from 'vue';
import AttendanceEvidenceCard from '@/components/AttendanceEvidenceCard.vue';
import AttendanceEvidenceViewer from '@/components/AttendanceEvidenceViewer.vue';
import AttendanceWarningDialog from '@/components/AttendanceWarningDialog.vue';
import CameraCapture from '@/components/CameraCapture.vue';
import DatePicker from '@/components/DatePicker.vue';
import EmployeeAttendanceStatusCard from '@/components/EmployeeAttendanceStatusCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import FormDialogContent from '@/components/FormDialogContent.vue';
import FormField from '@/components/FormField.vue';
import KPICard from '@/components/KPICard.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { deriveCaptureState } from '@/lib/attendance';
import { formatDateMx, formatTimeMx } from '@/lib/formatters';
import { ATTENDANCE_STATUS_OPTIONS } from '@/lib/status';
import type { Attendance, AttendancePhoto, Employee } from '@/types/models';

type ActionType = 'entrada' | 'salida';

const props = defineProps<{
    enabled: boolean;
    employee:
        | (Employee & {
              client?: { name: string } | null;
              service_point?: { name: string } | null;
              shift?: { name: string; start_time: string; end_time: string; tolerance_minutes: number } | null;
          })
        | null;
    attendance: Attendance | null;
    settings: {
        allow_exit: boolean;
        requires_photo: boolean;
        requires_location: boolean;
        warning_text: string;
        warning_version: number;
        photo_review_enabled: boolean;
    } | null;
    needsWarningAcceptance: boolean;
    history?: (Attendance & { service_point?: { name: string } })[];
    historyStats?: { present: number; absent: number; late: number; rest: number; total: number };
    historyFilters?: { date_from?: string; date_to?: string; status?: string };
    evidence?: AttendancePhoto[];
}>();

const state = computed(() => deriveCaptureState(props.attendance));

const canRegisterEntry = computed(() => state.value === 'sin_registro');
const canRegisterExit = computed(() => state.value === 'pendiente_salida' && props.settings?.allow_exit);
const isComplete = computed(() => state.value === 'completo');
const isTerminalOther = computed(
    () => !canRegisterEntry.value && !canRegisterExit.value && !isComplete.value,
);

const pendingAction = ref<ActionType | null>(null);
const showWarning = ref(false);
const showCamera = ref(false);
const warningAccepted = ref(false);
const submitting = ref(false);

type LocationStatus = 'idle' | 'loading' | 'ready' | 'error' | 'skipped';
const locationStatus = ref<LocationStatus>('idle');
const coords = ref<{ lat: number; lng: number } | null>(null);

const requestLocation = () => {
    if (!props.settings?.requires_location) {
        locationStatus.value = 'skipped';

        return;
    }

    if (!navigator.geolocation) {
        locationStatus.value = 'error';

        return;
    }

    locationStatus.value = 'loading';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            coords.value = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            locationStatus.value = 'ready';
        },
        () => {
            locationStatus.value = 'error';
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
};

const proceedToCapture = () => {
    requestLocation();

    if (props.settings?.requires_photo) {
        showCamera.value = true;
    } else {
        submit(null);
    }
};

const requestAction = (type: ActionType) => {
    pendingAction.value = type;

    if (props.settings?.requires_photo && props.needsWarningAcceptance && !warningAccepted.value) {
        showWarning.value = true;

        return;
    }

    proceedToCapture();
};

const onWarningAccept = () => {
    submitting.value = true;
    router.post(
        '/asistencias/aceptar-aviso',
        { context: 'colaborador' },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                warningAccepted.value = true;
                showWarning.value = false;
                submitting.value = false;
                proceedToCapture();
            },
            onError: () => {
                submitting.value = false;
            },
        },
    );
};

const onWarningCancel = () => {
    showWarning.value = false;
    pendingAction.value = null;
};

const onPhotoCaptured = (file: File) => {
    showCamera.value = false;
    submit(file);
};

const onCameraCancel = () => {
    showCamera.value = false;
    pendingAction.value = null;
};

const submit = (file: File | null) => {
    if (!pendingAction.value) {
        return;
    }

    if (props.settings?.requires_location && locationStatus.value !== 'ready') {
        // La ubicación puede tardar un poco más que la foto: esperamos un momento antes de fallar.
        setTimeout(() => submit(file), 500);

        return;
    }

    const formData = new FormData();

    if (file) {
        formData.append('photo', file);
    }

    if (coords.value) {
        formData.append('latitude', String(coords.value.lat));
        formData.append('longitude', String(coords.value.lng));
    }

    formData.append('device_time', new Date().toISOString());

    submitting.value = true;

    router.post(pendingAction.value === 'entrada' ? '/mi-asistencia/entrada' : '/mi-asistencia/salida', formData, {
        forceFormData: true,
        onFinish: () => {
            submitting.value = false;
            pendingAction.value = null;
            locationStatus.value = 'idle';
            coords.value = null;
        },
    });
};

// ── Historial ────────────────────────────────────────────────────────
const dateFrom = ref(props.historyFilters?.date_from ?? '');
const dateTo = ref(props.historyFilters?.date_to ?? '');
const historyStatus = ref<string | number | null>(props.historyFilters?.status ?? '');

const reloadHistory = () => {
    router.get(
        '/mi-asistencia',
        {
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            status: historyStatus.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

// ── Evidencias ───────────────────────────────────────────────────────
const viewerIndex = ref<number | null>(null);
</script>

<template>
    <div class="mx-auto w-full max-w-3xl p-6">
        <PageHeader title="Mi Asistencia" description="Registra tu entrada/salida y consulta tu historial" />

        <EmptyState
            v-if="!enabled"
            title="Registro deshabilitado"
            description="El autorregistro de asistencia no está habilitado actualmente. Contacta a tu administrador."
            :icon="ShieldAlert"
        />

        <EmptyState
            v-else-if="!employee"
            title="Cuenta no vinculada"
            description="Tu cuenta no está vinculada a un colaborador. Contacta al administrador del sistema."
            :icon="UserX"
        />

        <Tabs v-else default-value="hoy" class="w-full">
            <TabsList>
                <TabsTrigger value="hoy">Hoy</TabsTrigger>
                <TabsTrigger value="historial">Historial</TabsTrigger>
                <TabsTrigger v-if="settings?.photo_review_enabled" value="evidencias">Evidencias</TabsTrigger>
            </TabsList>

            <!-- ── Hoy ──────────────────────────────────────────────── -->
            <TabsContent value="hoy" class="space-y-6">
                <EmployeeAttendanceStatusCard :employee="employee" :attendance="attendance" />

                <Card class="border-0 shadow-sm">
                    <CardContent class="flex flex-col items-center gap-4 p-8 text-center">
                        <template v-if="canRegisterEntry">
                            <p class="text-sm text-muted-foreground">Aún no tienes entrada registrada hoy.</p>
                            <Button size="lg" class="h-14 w-full max-w-xs text-base" :disabled="submitting" @click="requestAction('entrada')">
                                <LogIn class="mr-2 h-5 w-5" /> Registrar entrada
                            </Button>
                        </template>

                        <template v-else-if="canRegisterExit">
                            <p class="text-sm text-muted-foreground">Tu entrada ya fue registrada. Cuando termines tu jornada, registra tu salida.</p>
                            <Button size="lg" class="h-14 w-full max-w-xs text-base" :disabled="submitting" @click="requestAction('salida')">
                                <LogOut class="mr-2 h-5 w-5" /> Registrar salida
                            </Button>
                        </template>

                        <template v-else-if="isComplete">
                            <CheckCircle2 class="h-10 w-10 text-green-600 dark:text-green-400" />
                            <p class="font-medium">Tu asistencia de hoy ya está completa.</p>
                        </template>

                        <template v-else-if="isTerminalOther">
                            <p class="font-medium">Ya tienes un registro de asistencia para hoy.</p>
                            <p class="text-sm text-muted-foreground">No es necesario registrar entrada.</p>
                        </template>

                        <p v-if="settings?.requires_photo" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Camera class="h-3.5 w-3.5" /> Se te pedirá tomar una fotografía con la cámara.
                        </p>
                        <p v-if="settings?.requires_location" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <MapPin class="h-3.5 w-3.5" /> Se solicitará tu ubicación actual.
                        </p>
                    </CardContent>
                </Card>

                <p class="text-center text-xs text-muted-foreground">
                    Las evidencias fotográficas solo son visibles para personal autorizado.
                </p>
            </TabsContent>

            <!-- ── Historial ────────────────────────────────────────── -->
            <TabsContent value="historial" class="space-y-6">
                <div v-if="historyStats" class="grid grid-cols-2 gap-3 md:grid-cols-5">
                    <KPICard title="Presentes" :value="historyStats.present" :icon="CheckCircle2" color="green" />
                    <KPICard title="Faltas" :value="historyStats.absent" :icon="XCircle" color="red" />
                    <KPICard title="Retardos" :value="historyStats.late" :icon="Clock" color="purple" />
                    <KPICard title="Descanso/Permiso" :value="historyStats.rest" :icon="Calendar" color="blue" />
                    <KPICard title="Total" :value="historyStats.total" :icon="Calendar" color="orange" />
                </div>

                <div class="grid grid-cols-2 gap-3 rounded-xl border bg-muted/30 p-4 md:grid-cols-3">
                    <FormField label="Desde">
                        <DatePicker v-model="dateFrom" placeholder="Fecha inicial" @update:model-value="reloadHistory" />
                    </FormField>
                    <FormField label="Hasta">
                        <DatePicker v-model="dateTo" placeholder="Fecha final" @update:model-value="reloadHistory" />
                    </FormField>
                    <SearchableSelect
                        v-model="historyStatus"
                        :options="ATTENDANCE_STATUS_OPTIONS"
                        label="Estado"
                        placeholder="Todos"
                        @update:model-value="reloadHistory"
                    />
                </div>

                <Card class="border-0 shadow-sm">
                    <CardContent class="p-0">
                        <EmptyState
                            v-if="!history?.length"
                            title="Sin registros"
                            description="No hay asistencias registradas para el rango de fechas y filtros seleccionados."
                            :icon="Calendar"
                        />
                        <div v-else class="divide-y">
                            <div
                                v-for="a in history"
                                :key="a.id"
                                class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <p class="text-sm font-medium">{{ formatDateMx(a.attendance_date) }}</p>
                                    <p class="text-xs text-muted-foreground">{{ a.service_point?.name ?? '—' }}</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-4">
                                    <p class="font-mono text-xs">{{ formatTimeMx(a.entry_time) }} – {{ formatTimeMx(a.exit_time) }}</p>
                                    <StatusBadge :status="a.status" />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- ── Evidencias ───────────────────────────────────────── -->
            <TabsContent v-if="settings?.photo_review_enabled" value="evidencias" class="space-y-4">
                <EmptyState
                    v-if="!evidence?.length"
                    title="Sin evidencias"
                    description="Aún no tienes fotografías de evidencia registradas."
                    :icon="Camera"
                />
                <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <AttendanceEvidenceCard
                        v-for="(photo, index) in evidence"
                        :key="photo.id"
                        :photo="photo"
                        @click="viewerIndex = index"
                    />
                </div>

                <AttendanceEvidenceViewer
                    :photos="evidence ?? []"
                    :current-index="viewerIndex"
                    @update:current-index="viewerIndex = $event"
                    @close="viewerIndex = null"
                />

                <p class="flex items-center gap-2 text-xs text-muted-foreground">
                    <ShieldCheck class="h-3.5 w-3.5" />
                    Las evidencias fotográficas solo son visibles para personal autorizado.
                </p>
            </TabsContent>
        </Tabs>

        <AttendanceWarningDialog
            v-if="settings"
            :open="showWarning"
            :warning-text="settings.warning_text"
            :processing="submitting"
            @accept="onWarningAccept"
            @cancel="onWarningCancel"
        />

        <Dialog :open="showCamera" @update:open="showCamera = $event">
            <FormDialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ pendingAction === 'entrada' ? 'Foto de entrada' : 'Foto de salida' }}</DialogTitle>
                </DialogHeader>
                <CameraCapture @captured="onPhotoCaptured" @cancel="onCameraCancel" />
            </FormDialogContent>
        </Dialog>
    </div>
</template>
