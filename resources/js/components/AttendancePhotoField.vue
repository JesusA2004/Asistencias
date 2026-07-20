<script setup lang="ts">
import { Camera, CheckCircle2, RotateCcw } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import CameraCapture from '@/components/CameraCapture.vue';
import FormDialogContent from '@/components/FormDialogContent.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const props = withDefaults(
    defineProps<{
        modelValue: File | null;
        required?: boolean;
        label?: string;
        employeeName?: string;
    }>(),
    {
        required: false,
        label: 'Fotografía de evidencia',
        employeeName: undefined,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: File | null];
}>();

const open = ref(false);
const previewUrl = ref<string | null>(null);

watch(
    () => props.modelValue,
    (file) => {
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
            previewUrl.value = null;
        }

        if (file) {
            previewUrl.value = URL.createObjectURL(file);
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});

const state = computed<'capturada' | 'requerida' | 'pendiente'>(() => {
    if (props.modelValue) {
        return 'capturada';
    }

    return props.required ? 'requerida' : 'pendiente';
});

const onCaptured = (file: File) => {
    emit('update:modelValue', file);
    open.value = false;
};
</script>

<template>
    <div class="flex items-center justify-between gap-3 rounded-lg border p-3">
        <div class="flex items-center gap-3 min-w-0">
            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-md bg-muted">
                <img v-if="previewUrl" :src="previewUrl" alt="Miniatura de evidencia" class="h-full w-full object-cover" />
                <div v-else class="flex h-full w-full items-center justify-center text-muted-foreground">
                    <Camera class="h-5 w-5" />
                </div>
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-medium">{{ employeeName ?? label }}</p>
                <Badge
                    :variant="state === 'capturada' ? 'default' : state === 'requerida' ? 'destructive' : 'outline'"
                    class="mt-0.5 text-xs"
                >
                    <CheckCircle2 v-if="state === 'capturada'" class="mr-1 h-3 w-3" />
                    {{ state === 'capturada' ? 'Foto capturada' : state === 'requerida' ? 'Foto requerida' : 'Foto pendiente' }}
                </Badge>
            </div>
        </div>

        <Button type="button" size="sm" :variant="modelValue ? 'outline' : 'default'" @click="open = true">
            <RotateCcw v-if="modelValue" class="mr-2 h-3.5 w-3.5" />
            <Camera v-else class="mr-2 h-3.5 w-3.5" />
            {{ modelValue ? 'Cambiar' : 'Capturar' }}
        </Button>
    </div>

    <Dialog :open="open" @update:open="open = $event">
        <FormDialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ employeeName ?? label }}</DialogTitle>
            </DialogHeader>
            <CameraCapture @captured="onCaptured" @cancel="open = false" />
        </FormDialogContent>
    </Dialog>
</template>
