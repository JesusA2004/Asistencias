<script setup lang="ts">
import { ChevronLeft, ChevronRight, MapPin, X } from '@lucide/vue';
import { computed } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import { formatDateTimeMx } from '@/lib/formatters';
import type { AttendancePhoto } from '@/types/models';

const props = defineProps<{
    photos: AttendancePhoto[];
    currentIndex: number | null;
}>();

const emit = defineEmits<{
    'update:currentIndex': [index: number | null];
    close: [];
}>();

const CAPTURE_TYPE_LABEL: Record<string, string> = {
    entrada: 'Entrada', salida: 'Salida', incidencia: 'Incidencia', manual: 'Manual',
};
const CAPTURE_ORIGIN_LABEL: Record<string, string> = {
    colaborador: 'Colaborador', supervisor: 'Supervisor', admin: 'Administrador', rh: 'RH',
};

const open = computed(() => props.currentIndex !== null);
const photo = computed<AttendancePhoto | null>(() =>
    props.currentIndex !== null ? props.photos[props.currentIndex] ?? null : null,
);

const goPrev = () => {
    if (props.currentIndex !== null && props.currentIndex > 0) {
        emit('update:currentIndex', props.currentIndex - 1);
    }
};

const goNext = () => {
    if (props.currentIndex !== null && props.currentIndex < props.photos.length - 1) {
        emit('update:currentIndex', props.currentIndex + 1);
    }
};

const onKeydown = (e: KeyboardEvent) => {
    if (e.key === 'ArrowLeft') {
        goPrev();
    } else if (e.key === 'ArrowRight') {
        goNext();
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="(v) => !v && emit('close')">
        <DialogContent class="max-w-4xl gap-0 p-0" @keydown="onKeydown">
            <DialogTitle class="sr-only">Evidencia fotográfica</DialogTitle>
            <div v-if="photo" class="grid grid-cols-1 md:grid-cols-[1.4fr_1fr]">
                <div class="relative flex items-center justify-center bg-black">
                    <img
                        :src="`/evidencias-asistencia/${photo.id}/foto`"
                        :alt="`Evidencia de ${photo.employee?.name}`"
                        class="max-h-[75vh] w-full object-contain"
                    />

                    <Button
                        v-if="currentIndex !== null && currentIndex > 0"
                        variant="secondary"
                        size="icon"
                        class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full opacity-90"
                        @click="goPrev"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </Button>
                    <Button
                        v-if="currentIndex !== null && currentIndex < photos.length - 1"
                        variant="secondary"
                        size="icon"
                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full opacity-90"
                        @click="goNext"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                </div>

                <div class="space-y-4 p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold">{{ photo.employee?.name }} {{ photo.employee?.last_name }}</p>
                            <p class="font-mono text-xs text-muted-foreground">{{ photo.employee?.employee_number }}</p>
                        </div>
                        <Button variant="ghost" size="icon" @click="emit('close')">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        <Badge variant="secondary">{{ CAPTURE_TYPE_LABEL[photo.capture_type] }}</Badge>
                        <Badge variant="outline">{{ CAPTURE_ORIGIN_LABEL[photo.capture_origin] }}</Badge>
                        <StatusBadge v-if="photo.attendance?.status" :status="photo.attendance.status" />
                        <Badge v-if="photo.latitude !== null" variant="outline" class="gap-1">
                            <MapPin class="h-3 w-3" /> Con ubicación
                        </Badge>
                    </div>

                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-xs text-muted-foreground">Empresa</dt>
                            <dd>{{ photo.client?.name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">Punto de servicio</dt>
                            <dd>{{ photo.service_point?.name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">Capturado</dt>
                            <dd>{{ formatDateTimeMx(photo.captured_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">Capturado por</dt>
                            <dd>{{ photo.captured_by?.name ?? '—' }}</dd>
                        </div>
                    </dl>

                    <p class="border-t pt-3 text-xs text-muted-foreground">
                        Las evidencias fotográficas solo son visibles para personal autorizado.
                    </p>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
