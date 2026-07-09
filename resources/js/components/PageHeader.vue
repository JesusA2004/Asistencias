<script setup lang="ts">
import { Separator } from '@/components/ui/separator';

defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
                <p v-if="description" class="text-sm text-muted-foreground mt-1">{{ description }}</p>
            </div>
            <div class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
        <Separator class="mt-4" />
    </div>
</template>
