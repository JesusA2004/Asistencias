<script setup lang="ts">
import { ArrowRight } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';

defineProps<{
    title: string;
    description?: string;
    icon?: LucideIcon;
    nextStepHint?: string;
}>();
</script>

<template>
    <EmptyState :title="title" :description="description" :icon="icon">
        <p v-if="nextStepHint" class="flex items-center gap-1.5 text-sm font-medium text-primary">
            <ArrowRight class="h-3.5 w-3.5" /> {{ nextStepHint }}
        </p>
    </EmptyState>
</template>
