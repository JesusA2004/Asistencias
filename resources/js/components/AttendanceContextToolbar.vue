<script setup lang="ts">
import { Loader2 } from '@lucide/vue';
import DatePicker from '@/components/DatePicker.vue';
import FormField from '@/components/FormField.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import type {SearchableSelectOption} from '@/components/SearchableSelect.vue';

defineProps<{
    clientId: string | number | null;
    servicePointId: string | number | null;
    date: string;
    shiftFilter: string | number | null;
    clientOptions: SearchableSelectOption[];
    servicePointOptions: SearchableSelectOption[];
    shiftOptions: SearchableSelectOption[];
    maxDate: string;
    loading?: boolean;
    messages: string[];
}>();

const emit = defineEmits<{
    'update:clientId': [value: string | number | null];
    'update:servicePointId': [value: string | number | null];
    'update:date': [value: string | null];
    'update:shiftFilter': [value: string | number | null];
}>();
</script>

<template>
    <div class="rounded-xl border bg-card p-5 shadow-sm">
        <div class="mb-3 flex items-center gap-2">
            <h2 class="text-sm font-semibold text-foreground">Paso 1 · ¿Dónde vas a capturar?</h2>
            <Loader2 v-if="loading" class="h-3.5 w-3.5 animate-spin text-muted-foreground" />
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <SearchableSelect
                :model-value="clientId"
                :options="clientOptions"
                label="Empresa"
                required
                placeholder="Selecciona empresa..."
                empty-text="No tienes empresas asignadas"
                :clearable="false"
                @update:model-value="emit('update:clientId', $event)"
            />
            <SearchableSelect
                :model-value="servicePointId"
                :options="servicePointOptions"
                label="Punto de Servicio"
                required
                placeholder="Selecciona punto..."
                empty-text="Sin puntos disponibles"
                :disabled="!clientId"
                :clearable="false"
                @update:model-value="emit('update:servicePointId', $event)"
            />
            <FormField label="Fecha" required>
                <DatePicker :model-value="date" :max-value="maxDate" @update:model-value="emit('update:date', $event)" />
            </FormField>
            <SearchableSelect
                :model-value="shiftFilter"
                :options="shiftOptions"
                label="Turno"
                placeholder="Todos los turnos"
                :disabled="!shiftOptions.length"
                @update:model-value="emit('update:shiftFilter', $event)"
            />
        </div>
        <ul v-if="messages.length" class="mt-3 space-y-1 border-t pt-3">
            <li v-for="(m, i) in messages" :key="i" class="text-sm text-muted-foreground">
                {{ m }}
            </li>
        </ul>
    </div>
</template>
