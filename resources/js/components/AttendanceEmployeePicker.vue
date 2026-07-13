<script setup lang="ts">
import { Users, X } from '@lucide/vue';
import SearchableMultiSelect from '@/components/SearchableMultiSelect.vue';
import type {SearchableMultiSelectOption} from '@/components/SearchableMultiSelect.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    modelValue: (string | number)[];
    options: SearchableMultiSelectOption[];
    emptyText?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: (string | number)[]];
}>();

const selectAllAvailable = () => {
    emit('update:modelValue', props.options.map((o) => o.value));
};

const clearSelection = () => {
    emit('update:modelValue', []);
};
</script>

<template>
    <div class="space-y-2 rounded-xl border bg-card p-5 shadow-sm">
        <h2 class="mb-1 text-sm font-semibold text-foreground">Paso 3 · Selecciona colaboradores</h2>
        <SearchableMultiSelect
            :model-value="modelValue"
            :options="options"
            search-placeholder="Buscar por nombre, apellido o número de empleado..."
            :empty-text="emptyText ?? 'No hay colaboradores disponibles'"
            @update:model-value="emit('update:modelValue', $event)"
        />
        <div class="flex flex-wrap gap-2 pt-1">
            <Button type="button" variant="outline" size="sm" :disabled="!options.length" @click="selectAllAvailable">
                <Users class="mr-1.5 h-3.5 w-3.5" /> Seleccionar todos los disponibles
            </Button>
            <Button v-if="modelValue.length" type="button" variant="ghost" size="sm" @click="clearSelection">
                <X class="mr-1.5 h-3.5 w-3.5" /> Quitar seleccionados
            </Button>
        </div>
    </div>
</template>
