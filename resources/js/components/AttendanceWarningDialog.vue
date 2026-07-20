<script setup lang="ts">
import { ShieldAlert } from '@lucide/vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

withDefaults(
    defineProps<{
        open: boolean;
        warningText: string;
        processing?: boolean;
    }>(),
    {
        processing: false,
    },
);

const emit = defineEmits<{
    accept: [];
    cancel: [];
}>();
</script>

<template>
    <AlertDialog :open="open">
        <AlertDialogContent class="max-w-lg">
            <AlertDialogHeader>
                <div class="flex items-center gap-2">
                    <ShieldAlert class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    <AlertDialogTitle>Aviso sobre evidencia fotográfica</AlertDialogTitle>
                </div>
                <AlertDialogDescription class="whitespace-pre-line text-sm leading-relaxed pt-2">
                    {{ warningText }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel :disabled="processing" @click="emit('cancel')">Cancelar</AlertDialogCancel>
                <AlertDialogAction :disabled="processing" @click="emit('accept')">
                    {{ processing ? 'Procesando...' : 'Entiendo y acepto' }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
