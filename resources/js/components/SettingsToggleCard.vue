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

const label = computed(() => {
    if (props.disabled) {
        return 'No aplica';
    }

    return props.modelValue ? 'Activado' : 'Desactivado';
});

// Todo el row es un <button> que niega modelValue directamente al hacer clic —
// no depende de la semántica interna de click/checked del componente Switch, que
// es lo que dejaba el badge desincronizado del estado real.
const toggle = () => {
    if (props.disabled) {
        return;
    }

    emit('update:modelValue', !props.modelValue);
};
</script>

<template>
    <button
        type="button"
        :disabled="disabled"
        :title="disabled ? tooltip : undefined"
        class="w-full rounded-lg border p-4 text-left transition-all hover:shadow-sm"
        :class="[
            disabled ? 'cursor-not-allowed opacity-60' : 'hover:border-primary/30',
            !disabled && modelValue ? 'border-primary/40 bg-primary/[0.03]' : '',
        ]"
        @click="toggle"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-sm font-medium leading-none">{{ title }}</p>
                    <Badge :variant="modelValue && !disabled ? 'default' : 'outline'" class="text-[10px] font-semibold uppercase tracking-wide">
                        {{ label }}
                    </Badge>
                </div>

                <p v-if="description" class="text-xs text-muted-foreground">{{ description }}</p>

                <p v-if="disabled && tooltip" class="text-xs italic text-muted-foreground">{{ tooltip }}</p>
                <p v-else-if="modelValue && activeHint" class="text-xs font-medium text-primary">{{ activeHint }}</p>
            </div>

            <Switch :checked="modelValue" :disabled="disabled" @click.stop="toggle" />
        </div>
    </button>
</template>
