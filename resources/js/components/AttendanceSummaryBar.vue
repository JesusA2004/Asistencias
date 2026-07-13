<script setup lang="ts">
import { Save } from '@lucide/vue';
import { Button } from '@/components/ui/button';

withDefaults(
    defineProps<{
        count: number;
        countLabel: string;
        submitLabel: string;
        saving: boolean;
        disabled?: boolean;
    }>(),
    {
        disabled: false,
    },
);

const emit = defineEmits<{
    save: [];
}>();
</script>

<template>
    <div class="sticky bottom-0 z-10 -mx-6 mt-6 border-t bg-background/95 px-6 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/80">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm">
                <span class="font-semibold">{{ count }}</span> <span class="text-muted-foreground">{{ countLabel }}</span>
            </div>
            <Button :disabled="disabled || !count || saving" @click="emit('save')">
                <Save class="mr-2 h-4 w-4" />
                {{ saving ? 'Guardando...' : submitLabel }}
            </Button>
        </div>
    </div>
</template>
