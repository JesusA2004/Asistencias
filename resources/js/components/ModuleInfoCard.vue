<script setup lang="ts">
import { ChevronDown, Info } from '@lucide/vue';
import { ref } from 'vue';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';

const props = defineProps<{
    /** Clave única para recordar si el usuario ya colapsó esta guía (por navegador). */
    storageKey: string;
    /** Qué es este módulo. */
    whatIsIt: string;
    /** Cuándo usarlo. */
    whenToUse: string;
    /** Pasos a seguir, opcional. */
    steps?: string[];
}>();

const STORAGE_PREFIX = 'module-info-open:';

const readInitialOpen = (): boolean => {
    if (typeof window === 'undefined') {
        return true;
    }

    const stored = window.localStorage.getItem(STORAGE_PREFIX + props.storageKey);

    return stored === null ? true : stored === '1';
};

const isOpen = ref(readInitialOpen());

const onOpenChange = (value: boolean) => {
    isOpen.value = value;

    if (typeof window !== 'undefined') {
        window.localStorage.setItem(STORAGE_PREFIX + props.storageKey, value ? '1' : '0');
    }
};
</script>

<template>
    <Collapsible :open="isOpen" class="mb-6 rounded-lg border bg-muted/30" @update:open="onOpenChange">
        <CollapsibleTrigger class="flex w-full items-center justify-between gap-2 px-4 py-2.5 text-left">
            <span class="flex items-center gap-2 text-sm font-medium text-foreground">
                <Info class="h-4 w-4 text-primary" /> ¿Qué es este módulo?
            </span>
            <ChevronDown :class="cn('h-4 w-4 text-muted-foreground transition-transform', isOpen ? 'rotate-180' : '')" />
        </CollapsibleTrigger>
        <CollapsibleContent class="space-y-2 px-4 pb-4 text-sm">
            <p class="text-foreground">{{ whatIsIt }}</p>
            <p class="text-muted-foreground"><span class="font-medium text-foreground">Cuándo usarlo: </span>{{ whenToUse }}</p>
            <ol v-if="steps?.length" class="list-decimal space-y-1 pl-5 text-muted-foreground">
                <li v-for="(step, i) in steps" :key="i">{{ step }}</li>
            </ol>
        </CollapsibleContent>
    </Collapsible>
</template>
