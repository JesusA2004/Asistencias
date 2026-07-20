<script setup lang="ts">
import { AlertTriangle } from '@lucide/vue';
import FormField from '@/components/FormField.vue';
import SettingsToggleCard from '@/components/SettingsToggleCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import type { Setting } from '@/types/models';

export interface SettingsFormData {
    allow_employee_self_attendance: boolean;
    employee_self_attendance_requires_photo: boolean;
    employee_self_attendance_allow_exit: boolean;
    employee_self_attendance_requires_location: boolean;
    supervisor_capture_requires_photo: boolean;
    supervisor_capture_photo_per_employee: boolean;
    attendance_photo_review_enabled: boolean;
    attendance_photo_retention_days: number;
    attendance_warning_text: string;
    attendance_warning_version: number;
}

const props = defineProps<{
    settingsByKey: Record<string, Setting>;
    modelValue: SettingsFormData;
    errors: Partial<Record<keyof SettingsFormData, string>>;
    processing: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: SettingsFormData];
    submit: [];
}>();

const desc = (key: string) => props.settingsByKey[key]?.description ?? undefined;

const patch = <K extends keyof SettingsFormData>(key: K, value: SettingsFormData[K]) => {
    emit('update:modelValue', { ...props.modelValue, [key]: value });
};
</script>

<template>
    <form class="space-y-8" @submit.prevent="$emit('submit')">
        <section class="space-y-3">
            <h3 class="text-sm font-semibold text-foreground">Asistencia de colaboradores</h3>
            <div class="space-y-2">
                <SettingsToggleCard
                    title="Permitir asistencia propia del colaborador"
                    :description="desc('allow_employee_self_attendance')"
                    :model-value="modelValue.allow_employee_self_attendance"
                    @update:model-value="patch('allow_employee_self_attendance', $event)"
                />
                <SettingsToggleCard
                    title="Exigir foto obligatoria en asistencia propia"
                    :description="desc('employee_self_attendance_requires_photo')"
                    :model-value="modelValue.employee_self_attendance_requires_photo"
                    @update:model-value="patch('employee_self_attendance_requires_photo', $event)"
                />
                <SettingsToggleCard
                    title="Permitir registrar salida"
                    :description="desc('employee_self_attendance_allow_exit')"
                    :model-value="modelValue.employee_self_attendance_allow_exit"
                    @update:model-value="patch('employee_self_attendance_allow_exit', $event)"
                />
                <SettingsToggleCard
                    title="Solicitar ubicación del dispositivo"
                    :description="desc('employee_self_attendance_requires_location')"
                    :model-value="modelValue.employee_self_attendance_requires_location"
                    @update:model-value="patch('employee_self_attendance_requires_location', $event)"
                />
            </div>
        </section>

        <section class="space-y-3">
            <h3 class="text-sm font-semibold text-foreground">Captura por supervisor</h3>
            <div class="space-y-2">
                <SettingsToggleCard
                    title="Exigir foto al capturar asistencia"
                    :description="desc('supervisor_capture_requires_photo')"
                    :model-value="modelValue.supervisor_capture_requires_photo"
                    @update:model-value="patch('supervisor_capture_requires_photo', $event)"
                />
                <SettingsToggleCard
                    title="Foto individual por colaborador"
                    :description="desc('supervisor_capture_photo_per_employee')"
                    :model-value="modelValue.supervisor_capture_photo_per_employee"
                    :disabled="!modelValue.supervisor_capture_requires_photo"
                    @update:model-value="patch('supervisor_capture_photo_per_employee', $event)"
                />
            </div>
        </section>

        <section class="space-y-3">
            <h3 class="text-sm font-semibold text-foreground">Evidencia y revisión</h3>
            <div class="space-y-2">
                <SettingsToggleCard
                    title="Habilitar visualizador de evidencias"
                    :description="desc('attendance_photo_review_enabled')"
                    :model-value="modelValue.attendance_photo_review_enabled"
                    @update:model-value="patch('attendance_photo_review_enabled', $event)"
                />

                <FormField
                    label="Días de retención de fotografías"
                    required
                    :error="errors.attendance_photo_retention_days"
                    :hint="desc('attendance_photo_retention_days')"
                >
                    <Input
                        type="number"
                        min="1"
                        max="3650"
                        :model-value="modelValue.attendance_photo_retention_days"
                        @update:model-value="patch('attendance_photo_retention_days', Number($event))"
                    />
                </FormField>

                <FormField
                    label="Texto de advertencia"
                    required
                    :error="errors.attendance_warning_text"
                    :hint="desc('attendance_warning_text')"
                >
                    <Textarea
                        rows="5"
                        :model-value="modelValue.attendance_warning_text"
                        @update:model-value="patch('attendance_warning_text', String($event))"
                    />
                </FormField>

                <FormField
                    label="Versión del aviso"
                    required
                    :error="errors.attendance_warning_version"
                >
                    <Input
                        type="number"
                        min="1"
                        class="max-w-[10rem]"
                        :model-value="modelValue.attendance_warning_version"
                        @update:model-value="patch('attendance_warning_version', Number($event))"
                    />
                </FormField>

                <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300">
                    <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                    <span>Incrementar la versión del aviso forzará a todos los colaboradores y supervisores a aceptarlo nuevamente antes de capturar una fotografía.</span>
                </div>
            </div>
        </section>

        <div class="flex justify-end border-t pt-6">
            <Button type="submit" :disabled="processing">
                {{ processing ? 'Guardando...' : 'Guardar configuración' }}
            </Button>
        </div>
    </form>
