<script setup lang="ts">
import AttendanceEmployeeCard from '@/components/AttendanceEmployeeCard.vue';
import AttendancePhotoField from '@/components/AttendancePhotoField.vue';
import FormField from '@/components/FormField.vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { ATTENDANCE_STATUS_OPTIONS, statusVisual } from '@/lib/status';
import { cn } from '@/lib/utils';
import type { AttendanceStatus } from '@/types/models';

export interface ManualRecord {
    status: AttendanceStatus;
    entry_time: string;
    exit_time: string;
    notes: string;
}

export interface ManualEmployee {
    id: number;
    name: string;
    employeeNumber: string;
    shiftName?: string | null;
    hasExisting: boolean;
}

withDefaults(
    defineProps<{
        employees: ManualEmployee[];
        records: Record<number, ManualRecord>;
        reason: string;
        anyExisting: boolean;
        photosRequired?: boolean;
        photos?: Record<number, File | null>;
    }>(),
    {
        photosRequired: false,
        photos: () => ({}),
    },
);

const emit = defineEmits<{
    update: [employeeId: number, patch: Partial<ManualRecord>];
    'update:reason': [value: string];
    'photo-update': [employeeId: number, file: File | null];
}>();
</script>

<template>
    <div class="space-y-3">
        <div v-if="anyExisting" class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/40">
            <label class="mb-1.5 block text-sm font-medium text-amber-800 dark:text-amber-400">
                Motivo de corrección <span class="text-destructive">*</span>
            </label>
            <Textarea
                :model-value="reason"
                placeholder="Explica por qué se corrige el registro existente (mínimo 10 caracteres)..."
                @update:model-value="emit('update:reason', String($event))"
            />
        </div>

        <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
            <AttendanceEmployeeCard
                v-for="emp in employees"
                :key="emp.id"
                :name="emp.name"
                :employee-number="emp.employeeNumber"
                :shift-name="emp.shiftName"
                :state-label="emp.hasExisting ? 'Ya tiene registro' : undefined"
                state-badge-class="bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950 dark:text-amber-400"
            >
                <div class="mb-3 flex flex-wrap gap-1.5">
                    <button
                        v-for="s in ATTENDANCE_STATUS_OPTIONS"
                        :key="s.value"
                        type="button"
                        :class="cn(
                            'rounded-full border px-2.5 py-1 text-xs font-medium transition-colors',
                            records[emp.id]?.status === s.value ? statusVisual(s.value).activePillClass : statusVisual(s.value).pillClass,
                        )"
                        @click="emit('update', emp.id, { status: s.value })"
                    >
                        {{ s.label }}
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                    <FormField label="Entrada" class="text-xs">
                        <Input
                            type="time"
                            :model-value="records[emp.id]?.entry_time ?? ''"
                            class="h-8 text-sm"
                            @update:model-value="emit('update', emp.id, { entry_time: String($event) })"
                        />
                    </FormField>
                    <FormField label="Salida" class="text-xs">
                        <Input
                            type="time"
                            :model-value="records[emp.id]?.exit_time ?? ''"
                            class="h-8 text-sm"
                            @update:model-value="emit('update', emp.id, { exit_time: String($event) })"
                        />
                    </FormField>
                    <FormField label="Notas" class="col-span-2 text-xs md:col-span-1">
                        <Input
                            :model-value="records[emp.id]?.notes ?? ''"
                            placeholder="Opcional..."
                            class="h-8 text-sm"
                            @update:model-value="emit('update', emp.id, { notes: String($event) })"
                        />
                    </FormField>
                </div>
                <div v-if="photosRequired" class="mt-3">
                    <AttendancePhotoField
                        :model-value="photos[emp.id] ?? null"
                        :required="true"
                        :employee-name="emp.name"
                        @update:model-value="emit('photo-update', emp.id, $event)"
                    />
                </div>
            </AttendanceEmployeeCard>
        </div>
    </div>
</template>
