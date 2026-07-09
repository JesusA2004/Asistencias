<script setup lang="ts">
import DatePicker from '@/components/DatePicker.vue';
import FormField from '@/components/FormField.vue';

const props = withDefaults(
    defineProps<{
        modelValue?: string | null;
        label?: string;
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        error?: string;
        hint?: string;
        class?: string;
        minValue?: string | null;
        maxValue?: string | null;
    }>(),
    {
        modelValue: null,
        label: undefined,
        placeholder: undefined,
        required: false,
        disabled: false,
        error: undefined,
        hint: undefined,
        class: '',
        minValue: null,
        maxValue: null,
    },
);

defineEmits<{
    'update:modelValue': [value: string | null];
}>();
</script>

<template>
    <FormField :label="label" :required="required" :error="error" :hint="hint" :class="props.class">
        <DatePicker
            :model-value="modelValue"
            @update:model-value="$emit('update:modelValue', $event)"
            :placeholder="placeholder"
            :disabled="disabled"
            :min-value="minValue"
            :max-value="maxValue"
        />
    </FormField>
</template>
