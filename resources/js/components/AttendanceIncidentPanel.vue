<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { statusVisual } from '@/lib/status';
import { cn } from '@/lib/utils';
import type { AttendanceStatus } from '@/types/models';

const INCIDENT_STATUSES: AttendanceStatus[] = ['falta', 'descanso', 'permiso', 'incapacidad', 'retardo'];

defineProps<{
    employeeNames: string[];
    status: AttendanceStatus | null;
    notes: string;
}>();

const emit = defineEmits<{
    'update:status': [value: AttendanceStatus];
    'update:notes': [value: string];
}>();

const requiresNotes = (status: AttendanceStatus | null) => status === 'permiso' || status === 'incapacidad';
</script>

<template>
    <div class="space-y-4 rounded-xl border bg-card p-5 shadow-sm">
        <div>
            <p class="mb-2 text-sm font-medium">Aplicará a {{ employeeNames.length }} colaborador(es):</p>
            <div class="flex flex-wrap gap-1.5">
                <Badge v-for="n in employeeNames" :key="n" variant="secondary" class="text-xs font-normal">{{ n }}</Badge>
            </div>
        </div>
        <div>
            <p class="mb-2 text-sm font-medium">Tipo de incidencia</p>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="s in INCIDENT_STATUSES"
                    :key="s"
                    type="button"
                    :class="cn(
                        'rounded-lg border px-3 py-2 text-sm font-medium transition-all hover:shadow-md',
                        status === s ? statusVisual(s).activePillClass : statusVisual(s).pillClass,
                    )"
                    @click="emit('update:status', s)"
                >
                    {{ statusVisual(s).label }}
                </button>
            </div>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium">
                Motivo / nota
                <span v-if="requiresNotes(status)" class="text-destructive">*</span>
            </label>
            <Textarea
                :model-value="notes"
                placeholder="Describe el motivo..."
                @update:model-value="emit('update:notes', String($event))"
            />
            <p v-if="requiresNotes(status)" class="mt-1 text-xs text-muted-foreground">
                Obligatorio para {{ status === 'permiso' ? 'permiso' : 'incapacidad' }} (mínimo 5 caracteres).
            </p>
        </div>
    </div>
</template>
