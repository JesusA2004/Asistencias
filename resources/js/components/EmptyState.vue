<script setup lang="ts">
import { InboxIcon } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

defineProps<{
    title?: string;
    description?: string;
    icon?: LucideIcon;
}>();
</script>

<template>
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="rounded-full bg-muted p-4 mb-4">
            <component :is="icon ?? InboxIcon" class="h-8 w-8 text-muted-foreground" />
        </div>
        <h3 class="text-lg font-medium text-foreground">{{ title ?? 'Sin resultados' }}</h3>
        <p class="text-sm text-muted-foreground mt-1 max-w-sm">
            {{ description ?? 'No se encontraron registros con los filtros aplicados.' }}
        </p>
        <div class="mt-4">
            <slot />
        </div>
    </div>
</template>
