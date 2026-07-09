<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import { Select, SelectContent, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        label?: string;
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        loading?: boolean;
        error?: string;
        hint?: string;
        class?: string;
    }>(),
    {
        modelValue: undefined,
        label: undefined,
        placeholder: 'Selecciona...',
        required: false,
        disabled: false,
        loading: false,
        error: undefined,
        hint: undefined,
        class: '',
    },
);

defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <FormField :label="label" :required="required" :error="error" :hint="hint" :class="props.class">
        <Select
            :model-value="modelValue"
            @update:model-value="(v) => $emit('update:modelValue', String(v))"
            :disabled="disabled || loading"
        >
            <SelectTrigger class="w-full" :aria-invalid="!!error">
                <Spinner v-if="loading" class="h-4 w-4" />
                <SelectValue v-else :placeholder="placeholder" />
            </SelectTrigger>
            <SelectContent>
                <slot />
            </SelectContent>
        </Select>
    </FormField>
</template>
