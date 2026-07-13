<script setup lang="ts">
import AttendanceEmployeeCard from '@/components/AttendanceEmployeeCard.vue';
import FormField from '@/components/FormField.vue';
import { Input } from '@/components/ui/input';
import { formatTimeMx } from '@/lib/formatters';

export interface ExitRecord {
    exit_time: string;
    notes: string;
}

export interface ExitEmployee {
    id: number;
    name: string;
    employeeNumber: string;
    shiftName?: string | null;
    entryTime: string | null;
}

defineProps<{
    employees: ExitEmployee[];
    records: Record<number, ExitRecord>;
}>();

const emit = defineEmits<{
    update: [employeeId: number, patch: Partial<ExitRecord>];
}>();
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
            <p class="mb-3 text-xs text-muted-foreground">
                Entrada registrada: <span class="font-medium text-foreground">{{ formatTimeMx(emp.entryTime) }}</span>
            </p>
            <div class="grid grid-cols-2 gap-3">
                <FormField label="Hora de salida" class="text-xs">
                    <Input
                        type="time"
                        :model-value="records[emp.id]?.exit_time ?? ''"
                        class="h-8 text-sm"
                        @update:model-value="emit('update', emp.id, { exit_time: String($event) })"
                    />
                </FormField>
                <FormField label="Notas" class="text-xs">
                    <Input
                        :model-value="records[emp.id]?.notes ?? ''"
                        placeholder="Opcional..."
                        class="h-8 text-sm"
                        @update:model-value="emit('update', emp.id, { notes: String($event) })"
                    />
                </FormField>
            </div>
        </AttendanceEmployeeCard>
    </div>
</template>
