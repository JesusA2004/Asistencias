<script setup lang="ts">
import { Check, ChevronsUpDown, Loader2, Search, X } from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

export interface SearchableSelectOption {
    value: string | number;
    label: string;
    description?: string;
}

const props = withDefaults(
    defineProps<{
        modelValue?: string | number | null;
        options: SearchableSelectOption[];
        label?: string;
        placeholder?: string;
        searchPlaceholder?: string;
        emptyText?: string;
        required?: boolean;
        disabled?: boolean;
        loading?: boolean;
        clearable?: boolean;
        error?: string;
        hint?: string;
        class?: string;
    }>(),
    {
        modelValue: null,
        label: undefined,
        placeholder: 'Selecciona...',
        searchPlaceholder: 'Buscar...',
        emptyText: 'Sin resultados',
        required: false,
        disabled: false,
        loading: false,
        clearable: true,
        error: undefined,
        hint: undefined,
        class: '',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string | number | null];
}>();

const open = ref(false);
const query = ref('');
const searchInput = ref<InstanceType<typeof HTMLInputElement> | null>(null);

const selected = computed(() => props.options.find((o) => String(o.value) === String(props.modelValue)) ?? null);

const filteredOptions = computed(() => {
    const q = query.value.trim().toLowerCase();

    if (!q) {
        return props.options;
    }

    return props.options.filter((o) => {
        const haystack = `${o.label} ${o.description ?? ''}`.toLowerCase();

        return haystack.includes(q);
    });
});

watch(open, (isOpen) => {
    if (isOpen) {
        query.value = '';
        nextTick(() => searchInput.value?.focus());
    }
});

const select = (option: SearchableSelectOption) => {
    emit('update:modelValue', option.value);
    open.value = false;
};

const clear = (e: Event) => {
    e.stopPropagation();
    emit('update:modelValue', null);
    open.value = false;
};
</script>

<template>
    <FormField :label="label" :required="required" :error="error" :hint="hint" :class="props.class">
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    type="button"
                    variant="outline"
                    role="combobox"
                    :aria-expanded="open"
                    :aria-invalid="!!error"
                    :disabled="disabled || loading"
                    class="w-full justify-between font-normal"
                >
                    <span class="flex min-w-0 flex-1 items-center gap-2 text-left">
                        <Loader2 v-if="loading" class="h-4 w-4 shrink-0 animate-spin text-muted-foreground" />
                        <span v-if="selected" class="truncate">{{ selected.label }}</span>
                        <span v-else class="truncate text-muted-foreground">{{ placeholder }}</span>
                    </span>
                    <span class="flex items-center gap-1">
                        <X
                            v-if="clearable && selected && !disabled"
                            class="h-3.5 w-3.5 shrink-0 text-muted-foreground transition-colors hover:text-foreground"
                            @click="clear"
                        />
                        <ChevronsUpDown class="h-4 w-4 shrink-0 opacity-50" />
                    </span>
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
                <div class="max-h-64 overflow-y-auto p-1">
                    <button
                        v-for="option in filteredOptions"
                        :key="option.value"
                        type="button"
                        :class="cn(
                            'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm transition-colors hover:bg-accent hover:text-accent-foreground',
                            String(option.value) === String(modelValue) && 'bg-accent/60',
                        )"
                        @click="select(option)"
                    >
                        <Check
                            :class="cn(
                                'h-4 w-4 shrink-0',
                                String(option.value) === String(modelValue) ? 'opacity-100' : 'opacity-0',
                            )"
                        />
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
    </FormField>
</template>
