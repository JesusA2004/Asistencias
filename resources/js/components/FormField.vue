<script setup lang="ts">
import { Label } from '@/components/ui/label';

const props = withDefaults(
    defineProps<{
        label?: string;
        required?: boolean;
        error?: string;
        hint?: string;
        class?: string;
    }>(),
    {
        label: undefined,
        required: false,
        error: undefined,
        hint: undefined,
        class: '',
    },
);
</script>

<template>
    <div :class="['space-y-1.5', props.class]">
        <Label v-if="label">
            {{ label }}
            <span v-if="required" class="text-destructive">*</span>
        </Label>
        <slot />
        <p v-if="error" class="text-destructive text-xs">{{ error }}</p>
        <p v-else-if="hint" class="text-muted-foreground text-xs">{{ hint }}</p>
    </div>
</template>
