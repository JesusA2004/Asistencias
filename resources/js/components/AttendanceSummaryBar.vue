<script setup lang="ts">
import { AlertCircle, Camera, Save } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const props = withDefaults(
    defineProps<{
        count: number;
        countLabel: string;
        submitLabel: string;
        saving: boolean;
        disabled?: boolean;
        disabledReason?: string;
        photosCaptured?: number;
        photosTotal?: number;
    }>(),
    {
        disabled: false,
        disabledReason: undefined,
        photosCaptured: undefined,
        photosTotal: undefined,
    },
);

const emit = defineEmits<{
    save: [];
}>();

const isBlocked = computed(() => props.disabled || !props.count || props.saving);

const reason = computed(() => {
    if (props.saving) {
        return undefined;
    }

    if (!props.count) {
        return 'Selecciona al menos un colaborador para continuar.';
    }

    if (props.disabled) {
        return props.disabledReason ?? 'Completa los datos requeridos para guardar.';
    }

    return undefined;
});
</script>

<template>
    <div class="sticky bottom-0 z-10 -mx-6 mt-6 border-t bg-background/95 px-6 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/80">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="text-sm">
                    <span class="font-semibold">{{ count }}</span> <span class="text-muted-foreground">{{ countLabel }}</span>
                </div>
                <Badge v-if="photosTotal !== undefined" :variant="photosCaptured === photosTotal ? 'default' : 'outline'" class="gap-1 text-xs">
                    <Camera class="h-3 w-3" />
                    {{ photosCaptured }} de {{ photosTotal }} fotos capturadas
                </Badge>
            </div>
            <div class="flex flex-col items-end gap-1">
                <Button :disabled="isBlocked" :title="reason" @click="emit('save')">
                    <Save class="mr-2 h-4 w-4" />
                    {{ saving ? 'Guardando...' : submitLabel }}
                </Button>
                <p v-if="reason" class="flex items-center gap-1 text-xs text-muted-foreground">
                    <AlertCircle class="h-3 w-3" /> {{ reason }}
                </p>
            </div>
        </div>
    </div>
</template>
