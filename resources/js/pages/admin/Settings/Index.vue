<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { AlertCircle, Check, Loader2, Settings2 } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SystemSettingsForm from '@/components/SystemSettingsForm.vue';
import type { SettingsFormData } from '@/components/SystemSettingsForm.vue';
import { notify } from '@/lib/notify';
import { cn } from '@/lib/utils';
import type { Setting } from '@/types/models';

const props = defineProps<{
    settings: Record<string, Setting[]>;
}>();

const flatSettings = computed<Setting[]>(() => Object.values(props.settings).flat());

const settingsByKey = computed<Record<string, Setting>>(() =>
    Object.fromEntries(flatSettings.value.map((s) => [s.key, s])),
);

const raw = (key: string) => settingsByKey.value[key]?.value;
const asBool = (key: string) => raw(key) === '1';
const asInt = (key: string, fallback: number) => (raw(key) !== undefined && raw(key) !== null ? Number(raw(key)) : fallback);

// Los switches booleanos se guardan solos e inmediato, sin depender de que el
// usuario recuerde el botón "Guardar configuración" (ese botón se queda solo para
// el texto legal y los días de retención).
const BOOLEAN_KEYS: (keyof SettingsFormData)[] = [
    'allow_employee_self_attendance',
    'employee_self_attendance_requires_photo',
    'employee_self_attendance_allow_exit',
    'employee_self_attendance_requires_location',
    'supervisor_capture_requires_photo',
    'attendance_photo_review_enabled',
];

const form = useForm<SettingsFormData>({
    allow_employee_self_attendance: asBool('allow_employee_self_attendance'),
    employee_self_attendance_requires_photo: asBool('employee_self_attendance_requires_photo'),
    employee_self_attendance_allow_exit: asBool('employee_self_attendance_allow_exit'),
    employee_self_attendance_requires_location: asBool('employee_self_attendance_requires_location'),
    supervisor_capture_requires_photo: asBool('supervisor_capture_requires_photo'),
    attendance_photo_review_enabled: asBool('attendance_photo_review_enabled'),
    attendance_photo_retention_days: asInt('attendance_photo_retention_days', 90),
    attendance_warning_text: raw('attendance_warning_text') ?? '',
    attendance_warning_version: asInt('attendance_warning_version', 1),
});

type SaveStatus = 'idle' | 'saving' | 'saved' | 'error';
const saveStatus = ref<SaveStatus>('idle');
let idleTimer: ReturnType<typeof setTimeout> | null = null;
let resaveNeeded = false;

const persist = () => {
    if (form.processing) {
        resaveNeeded = true;

        return;
    }

    saveStatus.value = 'saving';

    form.patch('/configuracion', {
        preserveScroll: true,
        onSuccess: () => {
            saveStatus.value = 'saved';
            // El toast de éxito ya lo dispara el handler global de flash (back()->with('success', ...)).

            if (idleTimer) {
                clearTimeout(idleTimer);
            }

            idleTimer = setTimeout(() => {
                if (saveStatus.value === 'saved') {
                    saveStatus.value = 'idle';
                }
            }, 3000);

            if (resaveNeeded) {
                resaveNeeded = false;
                persist();
            }
        },
        onError: () => {
            saveStatus.value = 'error';
            notify.error('No se pudo guardar la configuración. Revisa los campos e intenta de nuevo.');
        },
    });
};

onBeforeUnmount(() => {
    if (idleTimer) {
        clearTimeout(idleTimer);
    }
});

const onUpdate = (value: SettingsFormData) => {
    const changedKey = (Object.keys(value) as (keyof SettingsFormData)[]).find((key) => value[key] !== form[key]);

    const turningOnRequiresPhoto = value.supervisor_capture_requires_photo && !form.supervisor_capture_requires_photo;

    if (turningOnRequiresPhoto) {
        notify.warning(
            'A partir de ahora, quien capture asistencia (supervisor, admin o RH) deberá tomar una fotografía por cada colaborador seleccionado antes de poder guardar entrada o salida. Sin excepciones.',
            'Evidencia fotográfica obligatoria',
        );
    }

    Object.assign(form, value);

    // Los switches se guardan solos e inmediato. Los campos de texto/número (aviso
    // legal, días de retención) se quedan para el botón "Guardar configuración" —
    // autosavearlos mientras el usuario todavía está escribiendo dispararía errores
    // de validación a medio teclear.
    if (changedKey && BOOLEAN_KEYS.includes(changedKey)) {
        persist();
    }
};

// Botón manual: para el texto legal y los días de retención, que no autosavean
// (evita disparar validación mientras el usuario todavía está escribiendo).
const submit = () => {
    persist();
};
</script>

<template>
    <div class="w-full max-w-6xl p-6">
        <PageHeader
            title="Configuración"
            description="Parámetros operativos del sistema: asistencia propia, captura con evidencia y revisión de fotografías."
        >
            <template #actions>
                <div
                    :class="cn(
                        'flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition-colors',
                        saveStatus === 'saving' ? 'bg-muted text-muted-foreground' : '',
                        saveStatus === 'saved' ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-400' : '',
                        saveStatus === 'error' ? 'bg-destructive/10 text-destructive' : '',
                    )"
                >
                    <template v-if="saveStatus === 'saving'">
                        <Loader2 class="h-3.5 w-3.5 animate-spin" /> Guardando...
                    </template>
                    <template v-else-if="saveStatus === 'saved'">
                        <Check class="h-3.5 w-3.5" /> Configuración guardada
                    </template>
                    <template v-else-if="saveStatus === 'error'">
                        <AlertCircle class="h-3.5 w-3.5" /> Error al guardar
                    </template>
                </div>
            </template>
        </PageHeader>

        <SystemSettingsForm
            :settings-by-key="settingsByKey"
            :model-value="form as unknown as SettingsFormData"
            :errors="form.errors"
            :processing="form.processing"
            @update:model-value="onUpdate"
            @submit="submit"
        />

        <div class="mt-4 flex items-center gap-2 text-xs text-muted-foreground">
            <Settings2 class="h-3.5 w-3.5" />
            <span>Los switches se guardan automáticamente al cambiarlos. El texto legal y los días de retención se guardan con el botón "Guardar configuración".</span>
        </div>
    </div>
</template>
