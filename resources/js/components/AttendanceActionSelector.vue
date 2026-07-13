<script setup lang="ts">
import { LogIn, LogOut, SlidersHorizontal, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

export type CaptureAction = 'entrada' | 'salida' | 'incidencia' | 'manual';

const props = defineProps<{
    modelValue: CaptureAction | null;
    counts: Record<CaptureAction, number>;
    canUseManual: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: CaptureAction];
}>();

const ACTIONS: { value: CaptureAction; label: string; description: string; icon: typeof LogIn }[] = [
    { value: 'entrada', label: 'Registrar entrada', description: 'Colaboradores que están llegando', icon: LogIn },
    { value: 'salida', label: 'Registrar salida', description: 'Colaboradores que ya terminaron turno', icon: LogOut },
    { value: 'incidencia', label: 'Registrar incidencia', description: 'Falta, permiso, descanso, incapacidad o retardo', icon: TriangleAlert },
    { value: 'manual', label: 'Captura manual', description: 'Modo avanzado: editar todo en una pantalla', icon: SlidersHorizontal },
];

const visibleActions = computed(() => ACTIONS.filter((a) => a.value !== 'manual' || props.canUseManual));
</script>

<template>
    <div class="rounded-xl border bg-card p-5 shadow-sm">
        <h2 class="mb-3 text-sm font-semibold text-foreground">Paso 2 · ¿Qué quieres registrar?</h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <button
                v-for="action in visibleActions"
                :key="action.value"
                type="button"
                :class="cn(
                    'flex flex-col items-start gap-2 rounded-xl border p-4 text-left transition-all hover:shadow-md hover:border-primary/30',
                    modelValue === action.value ? 'border-primary bg-primary/5 shadow-sm' : 'border-border bg-card',
                )"
                @click="emit('update:modelValue', action.value)"
            >
                <div class="flex w-full items-center justify-between">
                    <component :is="action.icon" :class="cn('h-5 w-5', modelValue === action.value ? 'text-primary' : 'text-muted-foreground')" />
                    <span
                        v-if="counts[action.value] !== undefined"
                        :class="cn(
                            'rounded-full px-2 py-0.5 text-xs font-semibold',
                            modelValue === action.value ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground',
                        )"
                    >
                        {{ counts[action.value] }}
                    </span>
                </div>
                <span class="text-sm font-semibold">{{ action.label }}</span>
                <span class="text-xs text-muted-foreground">{{ action.description }}</span>
            </button>
        </div>
    </div>
</template>
