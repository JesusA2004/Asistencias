<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { BarChart3, Download, FileText } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import type { Attendance, Client, PaginatedData, ServicePoint } from '@/types/models';

const props = defineProps<{
    attendances: PaginatedData<Attendance> | null;
    summary: { total: number; presente: number; falta: number; retardo: number; descanso: number; permiso: number; incapacidad: number } | null;
    clients: Client[];
    servicePoints: ServicePoint[];
    filters: Record<string, string | undefined>;
}>();

const { hasPermission } = usePermissions();

const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const clientId = ref(props.filters.client_id ?? '');
const spId = ref(props.filters.service_point_id ?? '');
const status = ref(props.filters.status ?? '');

const search = () => {
    router.get('/reportes', {
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        client_id: clientId.value || undefined,
        service_point_id: spId.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true });
};

const exportParams = () => new URLSearchParams({
    date_from: dateFrom.value,
    date_to: dateTo.value,
    ...(clientId.value && { client_id: clientId.value }),
    ...(spId.value && { service_point_id: spId.value }),
    ...(status.value && { status: status.value }),
}).toString();

const onPage = (p: number) => {
    router.get('/reportes', { ...props.filters, page: p }, { preserveState: true });
};

const STATUSES = ['presente', 'falta', 'descanso', 'permiso', 'incapacidad', 'retardo'];
</script>

<template>
    <div class="p-6">
        <PageHeader title="Reportes de Asistencia" description="Genera y exporta reportes por rango de fechas">
            <template #actions>
                <template v-if="hasPermission('Exportar reportes') && dateFrom && dateTo">
                    <Button variant="outline" as="a" :href="`/reportes/excel?${exportParams()}`">
                        <Download class="h-4 w-4 mr-2" /> Excel
                    </Button>
                    <Button variant="outline" as="a" :href="`/reportes/pdf?${exportParams()}`" target="_blank">
                        <FileText class="h-4 w-4 mr-2" /> PDF
                    </Button>
                </template>
            </template>
        </PageHeader>

        <!-- Filters -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6 bg-muted/30 p-4 rounded-lg border">
            <div>
                <Label class="text-xs">Desde *</Label>
                <Input type="date" v-model="dateFrom" class="mt-1 h-8 text-sm" />
            </div>
            <div>
                <Label class="text-xs">Hasta *</Label>
                <Input type="date" v-model="dateTo" class="mt-1 h-8 text-sm" />
            </div>
            <div>
                <Label class="text-xs">Empresa</Label>
                <Select :model-value="clientId || '__all__'" @update:model-value="(v) => clientId = v === '__all__' ? '' : (v as string)">
                    <SelectTrigger class="mt-1 h-8 text-sm"><SelectValue placeholder="Todas" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="__all__">Todas</SelectItem>
                        <SelectItem v-for="c in clients" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div>
                <Label class="text-xs">Estado</Label>
                <Select :model-value="status || '__all__'" @update:model-value="(v) => status = v === '__all__' ? '' : (v as string)">
                    <SelectTrigger class="mt-1 h-8 text-sm"><SelectValue placeholder="Todos" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="__all__">Todos</SelectItem>
                        <SelectItem v-for="s in STATUSES" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="flex items-end">
                <Button class="w-full" :disabled="!dateFrom || !dateTo" @click="search">
                    <BarChart3 class="h-4 w-4 mr-2" /> Generar
                </Button>
            </div>
        </div>

        <!-- Summary cards -->
        <div v-if="summary" class="grid grid-cols-3 md:grid-cols-7 gap-3 mb-6">
            <Card class="border-0 shadow-sm text-center p-3">
                <div class="text-xl font-bold">{{ summary.total }}</div>
                <div class="text-xs text-muted-foreground">Total</div>
            </Card>
            <Card class="border-0 shadow-sm text-center p-3 bg-green-50 dark:bg-green-950">
                <div class="text-xl font-bold text-green-700">{{ summary.presente }}</div>
                <div class="text-xs text-green-600">Presentes</div>
            </Card>
            <Card class="border-0 shadow-sm text-center p-3 bg-red-50 dark:bg-red-950">
                <div class="text-xl font-bold text-red-700">{{ summary.falta }}</div>
                <div class="text-xs text-red-600">Faltas</div>
            </Card>
            <Card class="border-0 shadow-sm text-center p-3 bg-purple-50 dark:bg-purple-950">
                <div class="text-xl font-bold text-purple-700">{{ summary.retardo }}</div>
                <div class="text-xs text-purple-600">Retardos</div>
            </Card>
            <Card class="border-0 shadow-sm text-center p-3 bg-blue-50 dark:bg-blue-950">
                <div class="text-xl font-bold text-blue-700">{{ summary.descanso }}</div>
                <div class="text-xs text-blue-600">Descansos</div>
            </Card>
            <Card class="border-0 shadow-sm text-center p-3 bg-yellow-50 dark:bg-yellow-950">
                <div class="text-xl font-bold text-yellow-700">{{ summary.permiso }}</div>
                <div class="text-xs text-yellow-600">Permisos</div>
            </Card>
            <Card class="border-0 shadow-sm text-center p-3 bg-orange-50 dark:bg-orange-950">
                <div class="text-xl font-bold text-orange-700">{{ summary.incapacidad }}</div>
                <div class="text-xs text-orange-600">Incapacidades</div>
            </Card>
        </div>

        <!-- Results table -->
        <div v-if="attendances" class="rounded-lg border bg-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-muted/30">
                    <tr>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Fecha</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">No. Emp</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Nombre</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Empresa</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Punto</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Estado</th>
                        <th class="text-center p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Horario</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Supervisor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!attendances.data.length">
                        <td colspan="8" class="py-12 text-center text-muted-foreground">Sin resultados para los filtros seleccionados.</td>
                    </tr>
                    <tr v-for="a in attendances.data" :key="a.id" class="border-t hover:bg-muted/40">
                        <td class="p-3 font-mono text-xs">{{ a.attendance_date }}</td>
                        <td class="p-3 font-mono text-xs">{{ a.employee?.employee_number }}</td>
                        <td class="p-3 text-sm">{{ a.employee?.name }} {{ a.employee?.last_name }}</td>
                        <td class="p-3 text-sm">{{ a.client?.name }}</td>
                        <td class="p-3 text-sm">{{ a.service_point?.name }}</td>
                        <td class="p-3"><StatusBadge :status="a.status" /></td>
                        <td class="p-3 text-center font-mono text-xs">{{ a.entry_time ?? '--:--' }} – {{ a.exit_time ?? '--:--' }}</td>
                        <td class="p-3 text-sm">{{ a.supervisor?.name }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="attendances && attendances.last_page > 1" class="flex justify-between items-center mt-4 text-sm">
            <span class="text-muted-foreground">{{ attendances.from }}–{{ attendances.to }} de {{ attendances.total }}</span>
            <div class="flex gap-1">
                <Button variant="outline" size="sm" :disabled="attendances.current_page <= 1" @click="onPage(attendances.current_page - 1)">Anterior</Button>
                <Button variant="outline" size="sm" :disabled="attendances.current_page >= attendances.last_page" @click="onPage(attendances.current_page + 1)">Siguiente</Button>
            </div>
        </div>
    </div>
</template>
