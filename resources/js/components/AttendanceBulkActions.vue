<script setup lang="ts">
import { CheckCircle2, Eraser, Users, XCircle } from '@lucide/vue';
import { ref } from 'vue';
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
import { Button } from '@/components/ui/button';
import type { AttendanceStatus } from '@/types/models';

defineProps<{
    selectedCount: number;
    hasRecords: boolean;
}>();

const emit = defineEmits<{
    markSelected: [status: AttendanceStatus];
    markAll: [status: AttendanceStatus];
    clearStates: [];
    addAll: [];
}>();

const confirmOpen = ref(false);
const pendingAllStatus = ref<AttendanceStatus | null>(null);

const askMarkAll = (status: AttendanceStatus) => {
    pendingAllStatus.value = status;
    confirmOpen.value = true;
};

const confirmMarkAll = () => {
    if (pendingAllStatus.value) {
        emit('markAll', pendingAllStatus.value);
    }

    confirmOpen.value = false;
    pendingAllStatus.value = null;
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-2 rounded-xl border bg-muted/30 p-3">
        <span class="mr-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Acciones masivas</span>

        <Button type="button" variant="outline" size="sm" @click="emit('addAll')">
            <Users class="mr-1.5 h-3.5 w-3.5" /> Agregar todos
        </Button>

        <div class="mx-1 h-5 w-px bg-border" />

        <Button
            type="button"
            variant="outline"
            size="sm"
            :disabled="!selectedCount"
            @click="emit('markSelected', 'presente')"
        >
            <CheckCircle2 class="mr-1.5 h-3.5 w-3.5 text-green-600" /> Seleccionados: Presente
        </Button>
        <Button
            type="button"
            variant="outline"
            size="sm"
            :disabled="!selectedCount"
            @click="emit('markSelected', 'retardo')"
        >
            Seleccionados: Retardo
        </Button>

        <div class="mx-1 h-5 w-px bg-border" />

        <Button
            type="button"
            variant="secondary"
            size="sm"
            :disabled="!hasRecords"
            @click="askMarkAll('presente')"
        >
            <CheckCircle2 class="mr-1.5 h-3.5 w-3.5" /> Todos: Presente
        </Button>
        <Button
            type="button"
            variant="secondary"
            size="sm"
            :disabled="!hasRecords"
            @click="askMarkAll('falta')"
        >
            <XCircle class="mr-1.5 h-3.5 w-3.5" /> Todos: Falta
        </Button>

        <Button
            type="button"
            variant="ghost"
            size="sm"
            class="ml-auto"
            :disabled="!hasRecords"
            @click="emit('clearStates')"
        >
            <Eraser class="mr-1.5 h-3.5 w-3.5" /> Limpiar estados
        </Button>

        <AlertDialog v-model:open="confirmOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>¿Sobrescribir todos los estados?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Esto marcará a todos los colaboradores de la lista de captura con el mismo estado,
                        reemplazando cualquier estado que ya hayas capturado manualmente. Esta acción no
                        afecta asistencias ya guardadas.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="confirmOpen = false">Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="confirmMarkAll">Sí, aplicar a todos</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
