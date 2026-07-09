<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { History } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import type { AttendanceAudit, PaginatedData } from '@/types/models';

const props = defineProps<{
    audits: PaginatedData<AttendanceAudit>;
    filters: Record<string, string | undefined>;
}>();

const filterAction = ref(props.filters.action ?? '');
const filterFrom = ref(props.filters.date_from ?? '');
const filterTo = ref(props.filters.date_to ?? '');

const applyFilters = () => {
    router.get('/auditoria', {
        action: filterAction.value || undefined,
        date_from: filterFrom.value || undefined,
        date_to: filterTo.value || undefined,
    }, { preserveState: true });
};

const onPage = (p: number) => router.get('/auditoria', { ...props.filters, page: p }, { preserveState: true });

const actionColors: Record<string, string> = {
    creado: 'bg-green-100 text-green-700 border-green-200',
    actualizado: 'bg-blue-100 text-blue-700 border-blue-200',
    corregido: 'bg-yellow-100 text-yellow-700 border-yellow-200',
    eliminado: 'bg-red-100 text-red-700 border-red-200',
};
</script>

<template>
    <div class="p-6">
        <PageHeader title="Auditoría" description="Registro de todas las acciones realizadas sobre asistencias" />

        <!-- Filters -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6 bg-muted/30 p-4 rounded-lg border">
            <div>
                <Label class="text-xs">Acción</Label>
                <Select v-model="filterAction" @update:model-value="applyFilters">
                    <SelectTrigger class="mt-1 h-8 text-sm"><SelectValue placeholder="Todas" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Todas</SelectItem>
                        <SelectItem value="creado">Creado</SelectItem>
                        <SelectItem value="actualizado">Actualizado</SelectItem>
                        <SelectItem value="corregido">Corregido</SelectItem>
                        <SelectItem value="eliminado">Eliminado</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div>
                <Label class="text-xs">Desde</Label>
                <Input type="date" v-model="filterFrom" class="mt-1 h-8 text-sm" @change="applyFilters" />
            </div>
            <div>
                <Label class="text-xs">Hasta</Label>
                <Input type="date" v-model="filterTo" class="mt-1 h-8 text-sm" @change="applyFilters" />
            </div>
            <div class="flex items-end">
                <Button variant="outline" size="sm" class="w-full" @click="applyFilters">Filtrar</Button>
            </div>
        </div>

        <!-- Table -->
        <div class="rounded-lg border bg-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-muted/30">
                    <tr>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Fecha/Hora</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Acción</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Colaborador</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Empresa</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Realizó</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Motivo</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Cambios</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!audits.data.length">
                        <td colspan="7"><EmptyState :icon="History" title="Sin registros de auditoría" description="No hay cambios registrados para los filtros aplicados." /></td>
                    </tr>
                    <tr
                        v-for="audit in audits.data"
                        :key="audit.id"
                        class="border-t hover:bg-muted/40 transition-colors"
                    >
                        <td class="p-3 font-mono text-xs whitespace-nowrap">{{ audit.created_at }}</td>
                        <td class="p-3">
                            <Badge variant="outline" :class="['text-xs', actionColors[audit.action] ?? '']">
                                {{ audit.action }}
                            </Badge>
                        </td>
                        <td class="p-3 text-sm">
                            <div>{{ audit.attendance?.employee?.name }} {{ audit.attendance?.employee?.last_name }}</div>
                            <div class="text-xs text-muted-foreground font-mono">{{ audit.attendance?.employee?.employee_number }}</div>
                        </td>
                        <td class="p-3 text-sm">{{ audit.attendance?.client?.name ?? '—' }}</td>
                        <td class="p-3 text-sm">{{ audit.changer?.name ?? '—' }}</td>
                        <td class="p-3 text-xs text-muted-foreground max-w-[200px] truncate" :title="audit.reason ?? ''">
                            {{ audit.reason ?? '—' }}
                        </td>
                        <td class="p-3">
                            <div v-if="audit.old_values || audit.new_values" class="text-xs">
                                <div v-if="audit.old_values" class="text-red-600">
                                    Antes: {{ JSON.stringify(audit.old_values) }}
                                </div>
                                <div v-if="audit.new_values" class="text-green-600">
                                    Después: {{ JSON.stringify(audit.new_values) }}
                                </div>
                            </div>
                            <span v-else class="text-xs text-muted-foreground">—</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="audits.last_page > 1" class="flex justify-between items-center mt-4 text-sm">
            <span class="text-muted-foreground">{{ audits.from }}–{{ audits.to }} de {{ audits.total }}</span>
            <div class="flex gap-1">
                <Button variant="outline" size="sm" :disabled="audits.current_page <= 1" @click="onPage(audits.current_page - 1)">Ant</Button>
                <Button variant="outline" size="sm" :disabled="audits.current_page >= audits.last_page" @click="onPage(audits.current_page + 1)">Sig</Button>
            </div>
        </div>
    </div>
</template>
