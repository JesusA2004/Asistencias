<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DialogFooter } from '@/components/ui/dialog';

withDefaults(
    defineProps<{
        processing?: boolean;
        cancelLabel?: string;
        submitLabel?: string;
        processingLabel?: string;
        disabled?: boolean;
    }>(),
    {
        processing: false,
        cancelLabel: 'Cancelar',
        submitLabel: 'Guardar',
        processingLabel: 'Guardando...',
        disabled: false,
    },
);

defineEmits<{
    cancel: [];
}>();
</script>

<template>
    <DialogFooter>
        <Button type="button" variant="outline" :disabled="processing" @click="$emit('cancel')">
            {{ cancelLabel }}
        </Button>
        <Button type="submit" :disabled="processing || disabled">
            {{ processing ? processingLabel : submitLabel }}
        </Button>
    </DialogFooter>
</template>
