<script setup lang="ts">
import { CheckCircle2, ChevronLeft, ChevronRight, PartyPopper, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CameraCapture from '@/components/CameraCapture.vue';
import FormDialogContent from '@/components/FormDialogContent.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { notify } from '@/lib/notify';
import { cn } from '@/lib/utils';

export interface PhotoQueueEmployee {
    id: number;
    name: string;
}

const props = withDefaults(
    defineProps<{
        employees: PhotoQueueEmployee[];
        photos: Record<number, File | null>;
        required?: boolean;
        open: boolean;
    }>(),
    {
        required: true,
    },
);

const emit = defineEmits<{
    'update:photo': [employeeId: number, file: File];
    close: [];
}>();

// Orden de la cola: colaboradores sin foto primero (los que faltan), luego los
// que ya tienen — así el flujo guiado siempre arranca en el siguiente pendiente.
const order = ref<number[]>([]);
const currentIndex = ref(0);

const buildOrder = () => {
    const pending = props.employees.filter((e) => !props.photos[e.id]).map((e) => e.id);
    const done = props.employees.filter((e) => props.photos[e.id]).map((e) => e.id);
    order.value = [...pending, ...done];
    currentIndex.value = 0;
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            buildOrder();
        }
    },
    { immediate: true },
);

const employeesById = computed(() => new Map(props.employees.map((e) => [e.id, e])));
const total = computed(() => props.employees.length);
const capturedCount = computed(() => props.employees.filter((e) => !!props.photos[e.id]).length);
const allCaptured = computed(() => total.value > 0 && capturedCount.value === total.value);

const currentEmployeeId = computed(() => order.value[currentIndex.value] ?? null);
const currentEmployee = computed(() => (currentEmployeeId.value !== null ? employeesById.value.get(currentEmployeeId.value) ?? null : null));

const showSummary = computed(() => allCaptured.value);

const canGoPrev = computed(() => currentIndex.value > 0);
const canGoNext = computed(() => currentIndex.value < order.value.length - 1);

const goPrev = () => {
    if (canGoPrev.value) {
        currentIndex.value -= 1;
    }
};

const goNext = () => {
    if (canGoNext.value) {
        currentIndex.value += 1;
    }
};

const advanceToNextPending = () => {
    const nextPendingOffset = order.value.findIndex((id, i) => i > currentIndex.value && !props.photos[id]);

    if (nextPendingOffset !== -1) {
        currentIndex.value = nextPendingOffset;

        return;
    }

    const anyPendingOffset = order.value.findIndex((id) => !props.photos[id]);

    if (anyPendingOffset !== -1) {
        currentIndex.value = anyPendingOffset;
    }
};

const onCaptured = (file: File) => {
    if (currentEmployeeId.value === null) {
        return;
    }

    emit('update:photo', currentEmployeeId.value, file);
    notify.success('Foto capturada correctamente.');
    advanceToNextPending();
};

const requestClose = async () => {
    if (!allCaptured.value && props.required) {
        const missing = total.value - capturedCount.value;
        const confirmed = await notify.confirm({
            icon: 'warning',
            title: 'Fotos pendientes',
            text: `Faltan ${missing} de ${total.value} fotografías por capturar. ¿Deseas salir de todos modos?`,
            confirmButtonText: 'Salir sin terminar',
            cancelButtonText: 'Seguir capturando',
        });

        if (!confirmed) {
            return;
        }
    }

    emit('close');
};

const finish = () => {
    emit('close');
};
</script>

<template>
    <Dialog :open="open" @update:open="(v) => { if (!v) requestClose(); }">
        <FormDialogContent class="max-w-md">
            <DialogHeader class="flex-row items-center justify-between space-y-0">
                <DialogTitle v-if="!showSummary && currentEmployee">
                    Foto {{ currentIndex + 1 }} de {{ total }} — {{ currentEmployee.name }}
                </DialogTitle>
                <DialogTitle v-else>Fotografías completadas</DialogTitle>
                <Button type="button" size="icon" variant="ghost" class="h-7 w-7" @click="requestClose">
                    <X class="h-4 w-4" />
                </Button>
            </DialogHeader>

            <div class="mb-1 flex items-center gap-2">
                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-primary transition-all"
                        :style="{ width: total ? `${(capturedCount / total) * 100}%` : '0%' }"
                    />
                </div>
                <Badge variant="outline" class="shrink-0 text-xs">{{ capturedCount }} de {{ total }}</Badge>
            </div>

            <template v-if="!showSummary && currentEmployee">
                <CameraCapture :key="currentEmployee.id" :face-hint="`Asegúrate de que el rostro de ${currentEmployee.name} sea claramente visible.`" @captured="onCaptured" @cancel="requestClose" />

                <div class="flex items-center justify-between pt-1">
                    <Button type="button" variant="outline" size="sm" :disabled="!canGoPrev" @click="goPrev">
                        <ChevronLeft class="mr-1 h-3.5 w-3.5" /> Anterior
                    </Button>
                    <div class="flex items-center gap-1">
                        <span
                            v-for="(id, i) in order"
                            :key="id"
                            :class="cn(
                                'h-1.5 w-1.5 rounded-full',
                                photos[id] ? 'bg-primary' : 'bg-muted-foreground/30',
                                i === currentIndex ? 'ring-2 ring-primary/40' : '',
                            )"
                        />
                    </div>
                    <Button type="button" variant="outline" size="sm" :disabled="!canGoNext" @click="goNext">
                        Siguiente <ChevronRight class="ml-1 h-3.5 w-3.5" />
                    </Button>
                </div>
            </template>

            <template v-else>
                <div class="flex flex-col items-center gap-3 py-8 text-center">
                    <PartyPopper class="h-10 w-10 text-primary" />
                    <p class="text-sm font-medium">Se capturaron las {{ total }} fotografías.</p>
                    <p class="text-xs text-muted-foreground">Ya puedes regresar y guardar la asistencia.</p>
                    <Button type="button" @click="finish">
                        <CheckCircle2 class="mr-2 h-4 w-4" /> Finalizar
                    </Button>
                </div>
            </template>
        </FormDialogContent>
    </Dialog>
</template>
