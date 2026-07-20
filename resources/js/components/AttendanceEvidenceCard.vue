<script setup lang="ts">
import { Camera, MapPin, User } from '@lucide/vue';
import { computed } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { formatShortDateMx, formatTimeMx } from '@/lib/formatters';
import type { AttendancePhoto } from '@/types/models';

const props = defineProps<{
    photo: AttendancePhoto;
}>();

const emit = defineEmits<{
    click: [];
}>();

const CAPTURE_TYPE_LABEL: Record<string, string> = {
    entrada: 'Entrada',
    salida: 'Salida',
    incidencia: 'Incidencia',
    manual: 'Manual',
};

const CAPTURE_ORIGIN_LABEL: Record<string, string> = {
    colaborador: 'Colaborador',
    supervisor: 'Supervisor',
    admin: 'Admin',
    rh: 'RH',
};

const hasLocation = computed(() => props.photo.latitude !== null && props.photo.longitude !== null);
</script>

<template>
    <button
        type="button"
        class="group flex flex-col overflow-hidden rounded-xl border bg-card text-left shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md hover:border-primary/30"
        @click="emit('click')"
    >
        <div class="relative aspect-square w-full overflow-hidden bg-muted">
            <img
                :src="`/evidencias-asistencia/${photo.id}/miniatura`"
                :alt="`Evidencia de ${photo.employee?.name ?? 'colaborador'}`"
                loading="lazy"
                class="h-full w-full object-cover transition-transform group-hover:scale-105"
            />
            <div class="absolute left-2 top-2 flex gap-1">
                <Badge variant="secondary" class="text-xs shadow">{{ CAPTURE_TYPE_LABEL[photo.capture_type] }}</Badge>
            </div>
            <div v-if="hasLocation" class="absolute right-2 top-2">
                <Badge variant="secondary" class="gap-1 text-xs shadow">
                    <MapPin class="h-3 w-3" />
                </Badge>
            </div>
        </div>

        <div class="space-y-1.5 p-3">
            <div class="flex items-center justify-between gap-2">
                <p class="truncate text-sm font-medium">{{ photo.employee?.name }} {{ photo.employee?.last_name }}</p>
                <StatusBadge v-if="photo.attendance?.status" :status="photo.attendance.status" />
            </div>
            <p class="font-mono text-xs text-muted-foreground">{{ photo.employee?.employee_number }}</p>
            <p class="truncate text-xs text-muted-foreground">
                {{ photo.client?.name }} · {{ photo.service_point?.name }}
            </p>
            <div class="flex items-center justify-between pt-1 text-xs text-muted-foreground">
                <span>{{ formatShortDateMx(photo.captured_at) }} {{ formatTimeMx(photo.captured_at) }}</span>
                <Badge variant="outline" class="text-xs font-normal">{{ CAPTURE_ORIGIN_LABEL[photo.capture_origin] }}</Badge>
            </div>
            <div class="flex items-center gap-1 pt-1 text-xs text-muted-foreground">
                <User class="h-3 w-3" />
                <span class="truncate">{{ photo.captured_by?.name ?? '—' }}</span>
                <Camera class="ml-auto h-3 w-3" />
            </div>
        </div>
    </button>
</template>
