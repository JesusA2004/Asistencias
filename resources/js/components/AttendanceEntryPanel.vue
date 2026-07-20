<script setup lang="ts">
import AttendanceEmployeeCard from '@/components/AttendanceEmployeeCard.vue';
import AttendancePhotoField from '@/components/AttendancePhotoField.vue';
import FormField from '@/components/FormField.vue';
import { Input } from '@/components/ui/input';
import { statusVisual } from '@/lib/status';
import { cn } from '@/lib/utils';
import type { AttendanceStatus } from '@/types/models';

export interface EntryRecord {
    entry_time: string;
    status: AttendanceStatus;
    notes: string;
}

export interface EntryEmployee {
    id: number;
    name: string;
    employeeNumber: string;
    shiftName?: string | null;
}

withDefaults(
    defineProps<{
        employees: EntryEmployee[];
        records: Record<number, EntryRecord>;
        photosRequired?: boolean;
        photos?: Record<number, File | null>;
    }>(),
    {
        photosRequired: false,
        photos: () => ({}),
    },
);

const emit = defineEmits<{
    update: [employeeId: number, patch: Partial<EntryRecord>];
    'photo-update': [employeeId: number, file: File | null];
}>();

const ENTRY_STATUSES: AttendanceStatus[] = ['presente', 'retardo'];
</script>

<template>
    <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
        <AttendanceEmployeeCard
            v-for="emp in employees"
            :key="emp.id"
            :name="emp.name"
            :employee-number="emp.employeeNumber"
            :shift-name="emp.shiftName"
        >
            <div class="flex flex-wrap items-end gap-3">
                <FormField label="Hora de entrada" class="text-xs">
                    <Input
                        type="time"
                        :model-value="records[emp.id]?.entry_time ?? ''"
                        class="h-8 text-sm"
                        @update:model-value="emit('update', emp.id, { entry_time: String($event) })"
                    />
                </FormField>
                <div class="flex items-center gap-1.5 pb-1.5">
                    <span class="text-xs text-muted-foreground">Estado:</span>
                    <button
                        v-for="s in ENTRY_STATUSES"
                        :key="s"
                        type="button"
                        :class="cn(
                            'rounded-full border px-2.5 py-1 text-xs font-medium transition-colors',
                            records[emp.id]?.status === s ? statusVisual(s).activePillClass : statusVisual(s).pillClass,
                        )"
                        @click="emit('update', emp.id, { status: s })"
                    >
                        {{ statusVisual(s).label }}
                    </button>
                </div>
            </div>
            <FormField label="Notas" class="mt-3 text-xs">
                <Input
                    :model-value="records[emp.id]?.notes ?? ''"
                    placeholder="Opcional..."
                    class="h-8 text-sm"
                    @update:model-value="emit('update', emp.id, { notes: String($event) })"
                />
            </FormField>
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
</template>
