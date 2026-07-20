<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import { computed } from 'vue';
import DatePicker from '@/components/DatePicker.vue';
import FormField from '@/components/FormField.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Client, ServicePoint } from '@/types/models';

type SelectModel = string | number | null;

const props = defineProps<{
    dateFrom: string;
    dateTo: string;
    clientId: SelectModel;
    servicePointId: SelectModel;
    supervisorId: SelectModel;
    captureType: SelectModel;
    captureOrigin: SelectModel;
    status: SelectModel;
    search: string;
    clients: Client[];
    servicePoints: (ServicePoint & { client_id: number })[];
    supervisors: { id: number; name: string }[];
    hasActiveFilters: boolean;
}>();

const emit = defineEmits<{
    'update:dateFrom': [value: string];
    'update:dateTo': [value: string];
    'update:clientId': [value: SelectModel];
    'update:servicePointId': [value: SelectModel];
    'update:supervisorId': [value: SelectModel];
    'update:captureType': [value: SelectModel];
    'update:captureOrigin': [value: SelectModel];
    'update:status': [value: SelectModel];
    'update:search': [value: string];
    clear: [];
}>();

const clientOptions = computed(() => props.clients.map((c) => ({ value: c.id, label: c.name })));
const servicePointOptions = computed(() =>
    props.servicePoints
        .filter((sp) => !props.clientId || String(sp.client_id) === String(props.clientId))
        .map((sp) => ({ value: sp.id, label: sp.name })),
);
const supervisorOptions = computed(() => props.supervisors.map((s) => ({ value: s.id, label: s.name })));

const captureTypeOptions = [
    { value: 'entrada', label: 'Entrada' },
    { value: 'salida', label: 'Salida' },
    { value: 'incidencia', label: 'Incidencia' },
    { value: 'manual', label: 'Manual' },
];

const captureOriginOptions = [
    { value: 'colaborador', label: 'Colaborador' },
    { value: 'supervisor', label: 'Supervisor' },
    { value: 'admin', label: 'Administrador' },
    { value: 'rh', label: 'RH' },
];

const statusOptions = [
    { value: 'presente', label: 'Presente' },
    { value: 'falta', label: 'Falta' },
    { value: 'descanso', label: 'Descanso' },
    { value: 'permiso', label: 'Permiso' },
    { value: 'incapacidad', label: 'Incapacidad' },
    { value: 'retardo', label: 'Retardo' },
];
</script>

<template>
    <div class="mb-6 space-y-3 rounded-lg border bg-muted/30 p-4">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
            <FormField label="Desde">
                <DatePicker :model-value="dateFrom" placeholder="Fecha inicial" @update:model-value="emit('update:dateFrom', $event ?? '')" />
            </FormField>
            <FormField label="Hasta">
                <DatePicker :model-value="dateTo" placeholder="Fecha final" @update:model-value="emit('update:dateTo', $event ?? '')" />
            </FormField>
            <SearchableSelect
                :model-value="clientId"
                :options="clientOptions"
                label="Empresa"
                placeholder="Todas"
                @update:model-value="emit('update:clientId', $event)"
            />
            <SearchableSelect
                :model-value="servicePointId"
                :options="servicePointOptions"
                label="Punto de servicio"
                placeholder="Todos"
                @update:model-value="emit('update:servicePointId', $event)"
            />
            <SearchableSelect
                :model-value="supervisorId"
                :options="supervisorOptions"
                label="Capturado por"
                placeholder="Todos"
                @update:model-value="emit('update:supervisorId', $event)"
            />
            <SearchableSelect
                :model-value="captureType"
                :options="captureTypeOptions"
                label="Tipo de captura"
                placeholder="Todos"
                @update:model-value="emit('update:captureType', $event)"
            />
            <SearchableSelect
                :model-value="captureOrigin"
                :options="captureOriginOptions"
                label="Origen"
                placeholder="Todos"
                @update:model-value="emit('update:captureOrigin', $event)"
            />
            <SearchableSelect
                :model-value="status"
                :options="statusOptions"
                label="Estado de asistencia"
                placeholder="Todos"
                @update:model-value="emit('update:status', $event)"
            />
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <FormField label="Buscar colaborador" class="min-w-[16rem] flex-1">
                <div class="relative">
                    <Search class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        :model-value="search"
                        placeholder="Nombre o número de empleado..."
                        class="pl-8"
                        @update:model-value="emit('update:search', String($event))"
                    />
                </div>
            </FormField>
            <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="emit('clear')">
                <X class="mr-1.5 h-3.5 w-3.5" /> Limpiar filtros
            </Button>
        </div>
    </div>
</template>
