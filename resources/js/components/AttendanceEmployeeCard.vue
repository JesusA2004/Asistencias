<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { ATTENDANCE_STATUS_OPTIONS, statusVisual } from '@/lib/status';
import { cn } from '@/lib/utils';
import type { AttendanceStatus } from '@/types/models';

const props = defineProps<{
    name: string;
    employeeNumber: string;
    shiftName?: string | null;
    status: AttendanceStatus;
    entryTime: string;
    exitTime: string;
    notes: string;
}>();

const emit = defineEmits<{
    'update:status': [value: AttendanceStatus];
    'update:entryTime': [value: string];
    'update:exitTime': [value: string];
    'update:notes': [value: string];
}>();
</script>

<template>
    <div class="rounded-xl border bg-card p-4 shadow-sm transition-all hover:shadow-md">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="font-medium">{{ props.name }}</span>
                <span class="font-mono text-xs text-muted-foreground">{{ props.employeeNumber }}</span>
                <Badge v-if="props.shiftName" variant="outline" class="text-xs font-normal">{{ props.shiftName }}</Badge>
            </div>
        </div>

        <div class="mb-3 flex flex-wrap gap-1.5">
            <button
                v-for="s in ATTENDANCE_STATUS_OPTIONS"
                :key="s.value"
                type="button"
                role="radio"
                :aria-checked="props.status === s.value"
                :class="cn(
                    'rounded-full border px-2.5 py-1 text-xs font-medium transition-colors',
                    props.status === s.value ? statusVisual(s.value).activePillClass : statusVisual(s.value).pillClass,
                )"
                @click="emit('update:status', s.value)"
            >
                {{ s.label }}
            </button>
        </div>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
            <FormField label="Entrada" class="text-xs">
                <Input
                    type="time"
                    :model-value="props.entryTime"
                    class="h-8 text-sm"
                    @update:model-value="emit('update:entryTime', String($event))"
                />
            </FormField>
            <FormField label="Salida" class="text-xs">
                <Input
                    type="time"
                    :model-value="props.exitTime"
                    class="h-8 text-sm"
                    @update:model-value="emit('update:exitTime', String($event))"
                />
            </FormField>
            <FormField label="Notas" class="col-span-2 text-xs md:col-span-1">
                <Input
                    :model-value="props.notes"
                    placeholder="Opcional..."
                    class="h-8 text-sm"
                    @update:model-value="emit('update:notes', String($event))"
                />
            </FormField>
        </div>
    </div>
</template>
