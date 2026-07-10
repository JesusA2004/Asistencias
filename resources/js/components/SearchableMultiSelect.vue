<script setup lang="ts">
import { ChevronsUpDown, Search, X } from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import FormField from '@/components/FormField.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

export interface SearchableMultiSelectOption {
    value: string | number;
    label: string;
    description?: string;
}

const props = withDefaults(
    defineProps<{
        modelValue?: (string | number)[];
        options: SearchableMultiSelectOption[];
        label?: string;
        placeholder?: string;
        searchPlaceholder?: string;
        emptyText?: string;
        disabled?: boolean;
        loading?: boolean;
        error?: string;
        hint?: string;
        class?: string;
    }>(),
    {
        modelValue: () => [],
        label: undefined,
        placeholder: 'Selecciona uno o varios...',
        searchPlaceholder: 'Buscar por nombre, no. de empleado...',
        emptyText: 'Sin resultados',
        disabled: false,
        loading: false,
        error: undefined,
        hint: undefined,
        class: '',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: (string | number)[]];
}>();

const open = ref(false);
const query = ref('');
const searchInput = ref<InstanceType<typeof HTMLInputElement> | null>(null);

const selectedSet = computed(() => new Set(props.modelValue.map(String)));

const selectedOptions = computed(() => props.options.filter((o) => selectedSet.value.has(String(o.value))));

const filteredOptions = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) {
        return props.options;
    }
    return props.options.filter((o) => `${o.label} ${o.description ?? ''}`.toLowerCase().includes(q));
});

watch(open, (isOpen) => {
    if (isOpen) {
        query.value = '';
        nextTick(() => searchInput.value?.focus());
    }
});

const isSelected = (value: string | number) => selectedSet.value.has(String(value));

const toggle = (option: SearchableMultiSelectOption) => {
    if (isSelected(option.value)) {
        emit('update:modelValue', props.modelValue.filter((v) => String(v) !== String(option.value)));
    } else {
        emit('update:modelValue', [...props.modelValue, option.value]);
    }
};

const removeOne = (value: string | number) => {
    emit('update:modelValue', props.modelValue.filter((v) => String(v) !== String(value)));
};

const addAllVisible = () => {
    const visibleValues = filteredOptions.value.map((o) => o.value);
    const merged = new Set([...props.modelValue.map(String), ...visibleValues.map(String)]);
    emit('update:modelValue', props.options.filter((o) => merged.has(String(o.value))).map((o) => o.value));
};

const clearAll = () => emit('update:modelValue', []);
</script>

<template>
    <FormField :label="label" :error="error" :hint="hint" :class="props.class">
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    type="button"
                    variant="outline"
                    role="combobox"
                    :aria-expanded="open"
                    :aria-invalid="!!error"
                    :disabled="disabled || loading"
                    class="h-auto min-h-9 w-full justify-between font-normal"
                >
                    <span class="flex min-w-0 flex-1 flex-wrap items-center gap-1 py-0.5 text-left">
                        <template v-if="selectedOptions.length">
                            <Badge v-for="o in selectedOptions.slice(0, 4)" :key="o.value" variant="secondary" class="text-xs font-normal">
                                {{ o.label }}
                            </Badge>
                            <span v-if="selectedOptions.length > 4" class="text-xs text-muted-foreground">
                                +{{ selectedOptions.length - 4 }} más
                            </span>
                        </template>
                        <span v-else class="truncate text-muted-foreground">{{ placeholder }}</span>
                    </span>
                    <ChevronsUpDown class="h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-(--reka-popover-trigger-width) p-0" align="start">
                <div class="flex items-center gap-2 border-b px-3 py-2">
                    <Search class="h-4 w-4 shrink-0 text-muted-foreground" />
                    <input
                        ref="searchInput"
                        v-model="query"
                        type="text"
                        :placeholder="searchPlaceholder"
                        class="h-7 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                        @keydown.escape="open = false"
                    >
                </div>
                <div class="flex items-center justify-between gap-2 border-b px-2 py-1.5">
                    <button type="button" class="text-xs text-primary hover:underline" @click="addAllVisible">
                        Agregar todos
                    </button>
                    <button type="button" class="text-xs text-muted-foreground hover:underline" @click="clearAll">
                        Limpiar selección
                    </button>
                </div>
                <div class="max-h-64 overflow-y-auto p-1">
                    <button
                        v-for="option in filteredOptions"
                        :key="option.value"
                        type="button"
                        :class="cn(
                            'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm transition-colors hover:bg-accent hover:text-accent-foreground',
                            isSelected(option.value) && 'bg-accent/60',
                        )"
                        @click="toggle(option)"
                    >
                        <span
                            :class="cn(
                                'flex h-4 w-4 shrink-0 items-center justify-center rounded-sm border',
                                isSelected(option.value) ? 'border-primary bg-primary text-primary-foreground' : 'border-input',
                            )"
                        >
                            <svg v-if="isSelected(option.value)" viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate">{{ option.label }}</span>
                            <span v-if="option.description" class="block truncate text-xs text-muted-foreground">{{ option.description }}</span>
                        </span>
                    </button>
                    <p v-if="!filteredOptions.length" class="px-2 py-6 text-center text-sm text-muted-foreground">
                        {{ emptyText }}
                    </p>
                </div>
            </PopoverContent>
        </Popover>

        <div v-if="selectedOptions.length" class="mt-2 flex flex-wrap gap-1.5">
            <Badge v-for="o in selectedOptions" :key="o.value" variant="outline" class="gap-1 pr-1 text-xs font-normal">
                {{ o.label }}
                <button type="button" class="rounded-full p-0.5 hover:bg-muted" @click="removeOne(o.value)">
                    <X class="h-3 w-3" />
                </button>
            </Badge>
        </div>
    </FormField>
</template>
