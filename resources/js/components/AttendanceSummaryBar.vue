<script setup lang="ts">
import { Save } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{
    totalSelected: number;
    totalNew: number;
    totalAlreadyRegistered: number;
    saving: boolean;
}>();

const emit = defineEmits<{
    save: [];
}>();
</script>

<template>
    <div class="sticky bottom-0 z-10 -mx-6 mt-6 border-t bg-background/95 px-6 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/80">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-4 text-sm">
                <span><span class="font-semibold">{{ totalSelected }}</span> <span class="text-muted-foreground">seleccionados</span></span>
                <span><span class="font-semibold text-green-600 dark:text-green-400">{{ totalNew }}</span> <span class="text-muted-foreground">nuevos</span></span>
                <span v-if="totalAlreadyRegistered">
                    <span class="font-semibold text-amber-600 dark:text-amber-400">{{ totalAlreadyRegistered }}</span>
                    <span class="text-muted-foreground"> ya registrados</span>
                </span>
            </div>
            <Button :disabled="!totalNew || saving" @click="emit('save')">
                <Save class="mr-2 h-4 w-4" />
                {{ saving ? 'Guardando...' : `Guardar ${totalNew} asistencia${totalNew === 1 ? '' : 's'}` }}
            </Button>
        </div>
    </div>
</template>
