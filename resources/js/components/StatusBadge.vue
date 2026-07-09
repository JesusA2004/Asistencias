<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { AttendanceStatus, ClientStatus, EmployeeStatus } from '@/types/models';

const props = defineProps<{
    status: AttendanceStatus | ClientStatus | EmployeeStatus | string;
}>();

const statusConfig: Record<string, { label: string; cls: string }> = {
    presente: { label: 'Presente', cls: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-400' },
    falta: { label: 'Falta', cls: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-950 dark:text-red-400' },
    descanso: { label: 'Descanso', cls: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950 dark:text-blue-400' },
    permiso: { label: 'Permiso', cls: 'bg-yellow-100 text-yellow-700 border-yellow-200 dark:bg-yellow-950 dark:text-yellow-400' },
    incapacidad: { label: 'Incapacidad', cls: 'bg-orange-100 text-orange-700 border-orange-200 dark:bg-orange-950 dark:text-orange-400' },
    retardo: { label: 'Retardo', cls: 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-950 dark:text-purple-400' },
    activo: { label: 'Activo', cls: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-400' },
    inactivo: { label: 'Inactivo', cls: 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400' },
    baja: { label: 'Baja', cls: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-950 dark:text-red-400' },
};

const config = computed(() => statusConfig[props.status] ?? { label: props.status, cls: 'bg-gray-100 text-gray-600' });
</script>

<template>
    <Badge variant="outline" :class="['text-xs font-medium', config.cls]">
        {{ config.label }}
    </Badge>
</template>
