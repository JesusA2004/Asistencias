<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import { Textarea } from '@/components/ui/textarea';

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        label?: string;
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        error?: string;
        hint?: string;
        class?: string;
        rows?: number;
    }>(),
    {
        modelValue: '',
        label: undefined,
        placeholder: undefined,
        required: false,
        disabled: false,
        error: undefined,
        hint: undefined,
        class: '',
        rows: 3,
    },
);

defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <FormField :label="label" :required="required" :error="error" :hint="hint" :class="props.class">
        <Textarea
            :model-value="modelValue"
            @update:model-value="$emit('update:modelValue', String($event))"
            :placeholder="placeholder"
            :disabled="disabled"
            :rows="rows"
            :aria-invalid="!!error"
        />
    </FormField>
</template>
