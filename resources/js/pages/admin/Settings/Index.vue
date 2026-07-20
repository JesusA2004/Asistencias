<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Settings2 } from '@lucide/vue';
import { computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SystemSettingsForm from '@/components/SystemSettingsForm.vue';
import type { SettingsFormData } from '@/components/SystemSettingsForm.vue';
import { notify } from '@/lib/notify';
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

const form = useForm<SettingsFormData>({
    allow_employee_self_attendance: asBool('allow_employee_self_attendance'),
    employee_self_attendance_requires_photo: asBool('employee_self_attendance_requires_photo'),
    employee_self_attendance_allow_exit: asBool('employee_self_attendance_allow_exit'),
    employee_self_attendance_requires_location: asBool('employee_self_attendance_requires_location'),
    supervisor_capture_requires_photo: asBool('supervisor_capture_requires_photo'),
    supervisor_capture_photo_per_employee: asBool('supervisor_capture_photo_per_employee'),
    attendance_photo_review_enabled: asBool('attendance_photo_review_enabled'),
    attendance_photo_retention_days: asInt('attendance_photo_retention_days', 90),
    attendance_warning_text: raw('attendance_warning_text') ?? '',
    attendance_warning_version: asInt('attendance_warning_version', 1),
});

const onUpdate = (value: SettingsFormData) => {
    // El flujo correcto es una foto por colaborador: al encender "Exigir foto al
    // capturar asistencia" se activa también "Foto individual por colaborador" de
    // una vez, sin que el admin tenga que acordarse de prender los dos switches.
    const turningOnRequiresPhoto = value.supervisor_capture_requires_photo && !form.supervisor_capture_requires_photo;

    if (turningOnRequiresPhoto) {
        value.supervisor_capture_photo_per_employee = true;
        notify.warning(
            'Al activar esta opción, los supervisores deberán capturar una fotografía por cada colaborador seleccionado antes de poder guardar la asistencia.',
            'Evidencia fotográfica obligatoria',
        );
    }

    Object.assign(form, value);
};

const submit = () => {
    form.patch('/configuracion', {
        preserveScroll: true,
        // El toast de éxito ya lo dispara el handler global de flash (back()->with('success', ...)).
        // Los errores de validación (422) no pasan por flash, así que sí necesitan aviso explícito aquí.
        onError: () => notify.error('No se pudo guardar la configuración. Revisa los campos e intenta de nuevo.'),
    });
};
</script>

<template>
    <div class="w-full max-w-6xl p-6">
        <PageHeader
            title="Configuración"
            description="Parámetros operativos del sistema: asistencia propia, captura con evidencia y revisión de fotografías."
        />

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
            <span>Los cambios se aplican de inmediato en todo el sistema.</span>
        </div>
    </div>
</template>
