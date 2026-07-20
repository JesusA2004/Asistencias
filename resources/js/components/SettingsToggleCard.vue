<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';

withDefaults(
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

defineEmits<{
    'update:modelValue': [value: boolean];
}>();
</script>

<template>
    <div
        :title="disabled ? tooltip : undefined"
        :class="[
            'flex items-start justify-between gap-4 rounded-lg border p-4 transition-colors',
            disabled ? 'opacity-60' : '',
            !disabled && modelValue ? 'border-primary/30 bg-primary/[0.03]' : '',
        ]"
    >
        <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-medium leading-none">{{ title }}</p>
                <Badge
                    :variant="disabled ? 'outline' : modelValue ? 'default' : 'outline'"
                    :class="['text-[10px] font-semibold uppercase tracking-wide', disabled ? 'text-muted-foreground' : '']"
                >
                    {{ disabled ? 'No aplica' : modelValue ? 'Activado' : 'Desactivado' }}
                </Badge>
            </div>
            <p v-if="description" class="text-xs text-muted-foreground">{{ description }}</p>
            <p v-if="disabled && tooltip" class="text-xs text-muted-foreground italic">{{ tooltip }}</p>
            <p v-else-if="modelValue && activeHint" class="text-xs font-medium text-primary">{{ activeHint }}</p>
        </div>
        <Switch
            :checked="modelValue"
            :disabled="disabled"
            @update:checked="$emit('update:modelValue', $event)"
        />
    </div>
</template>
