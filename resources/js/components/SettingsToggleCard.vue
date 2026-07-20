<script setup lang="ts">
import { Switch } from '@/components/ui/switch';

withDefaults(
    defineProps<{
        modelValue: boolean;
        title: string;
        description?: string | null;
        disabled?: boolean;
        tooltip?: string;
    }>(),
    {
        description: null,
        disabled: false,
        tooltip: undefined,
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
            'flex items-start justify-between gap-4 rounded-lg border p-4 transition-opacity',
            disabled ? 'opacity-60' : '',
        ]"
    >
        <div class="space-y-0.5">
            <p class="text-sm font-medium leading-none">{{ title }}</p>
            <p v-if="description" class="text-xs text-muted-foreground">{{ description }}</p>
        </div>
        <Switch
            :checked="modelValue"
            :disabled="disabled"
            @update:checked="$emit('update:modelValue', $event)"
        />
    </div>
</template>
