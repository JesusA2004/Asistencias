<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        title: string;
        description?: string | null;
        disabled?: boolean;
        tooltip?: string;
        /** Microcopy que solo aparece cuando el switch está activo (consecuencia concreta). */
        activeHint?: string;
    }>(),
    {
        description: null,
        disabled: false,
        tooltip: undefined,
        activeHint: undefined,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const statusLabel = computed(() => {
    if (props.disabled) {
        return 'No aplica';
    }

    return props.modelValue ? 'Activado' : 'Desactivado';
});

// El Switch es quien manda: reenviamos el valor que ÉL calculó (respeta su propio
// contrato de componente controlado) en vez de negar modelValue nosotros mismos —
// eso era lo que dejaba el badge y el thumb visual desincronizados entre sí.
const onCheckedChange = (checked: boolean) => {
    if (props.disabled) {
        return;
    }

    emit('update:modelValue', checked);
};

// Clic en cualquier parte de la card fuera del switch también alterna (el switch
// detiene la propagación del clic para no disparar esto además de su propio evento).
const onCardClick = () => {
    if (props.disabled) {
        return;
    }

    emit('update:modelValue', !props.modelValue);
};
</script>

<template>
    <div
        :title="disabled ? tooltip : undefined"
        class="rounded-xl border p-4 transition-all"
        :class="[
            disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer hover:shadow-md hover:border-primary/30',
            !disabled && modelValue ? 'border-primary/40 bg-primary/[0.04]' : '',
        ]"
        @click="onCardClick"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-sm font-semibold leading-none">{{ title }}</p>
                    <Badge :variant="modelValue && !disabled ? 'default' : 'outline'" class="text-[10px] font-semibold uppercase tracking-wide">
                        {{ statusLabel }}
                    </Badge>
                </div>

                <p v-if="description" class="text-xs text-muted-foreground">{{ description }}</p>

                <p v-if="disabled && tooltip" class="text-xs italic text-muted-foreground">{{ tooltip }}</p>
                <p v-else-if="modelValue && activeHint" class="text-xs font-medium text-primary">{{ activeHint }}</p>
            </div>

            <Switch :model-value="modelValue" :disabled="disabled" @click.stop @update:model-value="onCheckedChange" />
        </div>
    </div>
</template>
