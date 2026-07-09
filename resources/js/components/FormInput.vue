<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import { Input } from '@/components/ui/input';

const props = withDefaults(
    defineProps<{
        modelValue?: string | number | null;
        label?: string;
        type?: string;
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        error?: string;
        hint?: string;
        class?: string;
        min?: string | number;
        max?: string | number;
        step?: string | number;
    }>(),
    {
        modelValue: '',
        label: undefined,
        type: 'text',
        placeholder: undefined,
        required: false,
        disabled: false,
        error: undefined,
        hint: undefined,
        class: '',
        min: undefined,
        max: undefined,
        step: undefined,
    },
);

defineEmits<{
    'update:modelValue': [value: string | number];
}>();
</script>

<template>
    <FormField :label="label" :required="required" :error="error" :hint="hint" :class="props.class">
        <Input
            :type="type"
            :model-value="modelValue ?? ''"
            @update:model-value="$emit('update:modelValue', $event)"
            :placeholder="placeholder"
            :disabled="disabled"
            :min="min"
            :max="max"
            :step="step"
            :aria-invalid="!!error"
        />
    </FormField>
</template>
