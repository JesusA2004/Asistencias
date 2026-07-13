<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

withDefaults(
    defineProps<{
        name: string;
        employeeNumber: string;
        shiftName?: string | null;
        stateLabel?: string;
        stateBadgeClass?: string;
    }>(),
    {
        shiftName: null,
        stateLabel: undefined,
        stateBadgeClass: '',
    },
);
</script>

<template>
    <div class="rounded-xl border bg-card p-4 shadow-sm transition-all hover:shadow-md hover:border-primary/30">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-medium">{{ name }}</span>
                <span class="font-mono text-xs text-muted-foreground">{{ employeeNumber }}</span>
                <Badge v-if="shiftName" variant="outline" class="text-xs font-normal">{{ shiftName }}</Badge>
            </div>
            <Badge v-if="stateLabel" variant="outline" :class="['text-xs', stateBadgeClass]">{{ stateLabel }}</Badge>
        </div>
        <slot />
    </div>
</template>
