<script setup lang="ts">
import { Switch } from '@/components/ui/switch';

withDefaults(
    defineProps<{
        modelValue: boolean;
        title: string;
        description?: string | null;
        disabled?: boolean;
    }>(),
    {
        description: null,
        disabled: false,
    },
);

defineEmits<{
    'update:modelValue': [value: boolean];
}>();
</script>

<template>
    <div class="flex items-start justify-between gap-4 rounded-lg border p-4">
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
