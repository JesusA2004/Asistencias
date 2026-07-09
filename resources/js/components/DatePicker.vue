<script setup lang="ts">
import { getLocalTimeZone, parseDate } from '@internationalized/date';
import { Calendar as CalendarIcon } from '@lucide/vue';
import type { DateValue } from 'reka-ui';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue?: string | null;
        placeholder?: string;
        disabled?: boolean;
        minValue?: string | null;
        maxValue?: string | null;
        class?: string;
    }>(),
    {
        modelValue: null,
        placeholder: 'Selecciona una fecha',
        disabled: false,
        minValue: null,
        maxValue: null,
        class: '',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
}>();

const formatter = new Intl.DateTimeFormat('es-MX', { dateStyle: 'long' });

const safeParse = (value: string | null | undefined): DateValue | undefined => {
    if (!value) {
return undefined;
}

    try {
        return parseDate(value);
    } catch {
        return undefined;
    }
};

const parsedValue = computed(() => safeParse(props.modelValue));
const minDate = computed(() => safeParse(props.minValue));
const maxDate = computed(() => safeParse(props.maxValue));

const displayLabel = computed(() => {
    if (!parsedValue.value) {
return props.placeholder;
}

    return formatter.format(parsedValue.value.toDate(getLocalTimeZone()));
});

const open = ref(false);

const onSelect = (value: DateValue | undefined) => {
    emit('update:modelValue', value ? value.toString() : null);
    open.value = false;
};
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                type="button"
                variant="outline"
                :disabled="disabled"
                :class="cn('w-full justify-start text-left font-normal', !parsedValue && 'text-muted-foreground', props.class)"
            >
                <CalendarIcon class="mr-2 h-4 w-4 shrink-0" />
                <span class="truncate">{{ displayLabel }}</span>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="start">
            <Calendar :model-value="parsedValue" :min-value="minDate" :max-value="maxDate" @update:model-value="onSelect" />
        </PopoverContent>
    </Popover>
</template>
