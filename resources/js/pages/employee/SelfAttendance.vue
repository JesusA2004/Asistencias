<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Camera, CheckCircle2, LogIn, LogOut, MapPin, ShieldAlert, UserX } from '@lucide/vue';
import { computed, ref } from 'vue';
import AttendanceWarningDialog from '@/components/AttendanceWarningDialog.vue';
import CameraCapture from '@/components/CameraCapture.vue';
import EmployeeAttendanceStatusCard from '@/components/EmployeeAttendanceStatusCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import FormDialogContent from '@/components/FormDialogContent.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { deriveCaptureState } from '@/lib/attendance';
import type { Attendance, Employee } from '@/types/models';

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
    } | null;
    needsWarningAcceptance: boolean;
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
</script>

<template>
    <div class="mx-auto w-full max-w-2xl p-6">
        <PageHeader title="Mi Asistencia" description="Registra tu entrada y salida del día" />

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

        <template v-else>
            <EmployeeAttendanceStatusCard :employee="employee" :attendance="attendance" class="mb-6" />

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

            <p class="mt-4 text-center text-xs text-muted-foreground">
                Las evidencias fotográficas solo son visibles para personal autorizado.
            </p>
        </template>

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
