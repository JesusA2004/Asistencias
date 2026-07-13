<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
    currentStep: 1 | 2 | 3 | 4;
}>();

const STEPS = [
    { n: 1, label: 'Contexto' },
    { n: 2, label: 'Acción' },
    { n: 3, label: 'Colaboradores' },
    { n: 4, label: 'Confirmar' },
] as const;
</script>

<template>
    <div class="mb-6 flex items-center gap-2 overflow-x-auto pb-1">
        <template v-for="(step, i) in STEPS" :key="step.n">
            <div class="flex shrink-0 items-center gap-2">
                <span
                    :class="cn(
                        'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition-colors',
                        currentStep === step.n
                            ? 'bg-primary text-primary-foreground'
                            : currentStep > step.n
                                ? 'bg-primary/15 text-primary'
                                : 'bg-muted text-muted-foreground',
                    )"
                >
                    {{ step.n }}
                </span>
                <span :class="cn('text-sm', currentStep === step.n ? 'font-semibold text-foreground' : 'text-muted-foreground')">
                    {{ step.label }}
                </span>
            </div>
            <div v-if="i < STEPS.length - 1" class="h-px w-6 shrink-0 bg-border" />
        </template>
    </div>
</template>
