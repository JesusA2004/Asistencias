<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { HardHat, Pencil, Plus, Trash2, Upload } from '@lucide/vue';
import { toast } from 'vue-sonner';
import DeleteDialog from '@/components/DeleteDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import type { Client, Employee, PaginatedData, ServicePoint, Shift } from '@/types/models';

const props = defineProps<{
    employees: PaginatedData<Employee>;
    clients: Client[];
    servicePoints: ServicePoint[];
    shifts: Shift[];
    filters: Record<string, string | undefined>;
}>();

const { hasPermission } = usePermissions();
const showModal = ref(false);
const showImportModal = ref(false);
const deleteId = ref<number | null>(null);
const editingEmployee = ref<Employee | null>(null);

const importForm = useForm<{ file: File | null }>({
    file: null,
});

const form = useForm({
    employee_number: '',
    name: '',
    last_name: '',
    second_last_name: '',
    email: '',
    phone: '',
    status: 'activo' as Employee['status'],
    client_id: '',
    service_point_id: '',
    shift_id: '',
});

const openCreate = () => {
    editingEmployee.value = null;
    form.reset();
    form.status = 'activo';
    showModal.value = true;
};

const openEdit = (emp: Employee) => {
    editingEmployee.value = emp;
    form.employee_number = emp.employee_number;
    form.name = emp.name;
    form.last_name = emp.last_name;
    form.second_last_name = emp.second_last_name ?? '';
    form.email = emp.email ?? '';
    form.phone = emp.phone ?? '';
    form.status = emp.status;
    form.client_id = emp.client_id ? String(emp.client_id) : '';
    form.service_point_id = emp.service_point_id ? String(emp.service_point_id) : '';
    form.shift_id = emp.shift_id ? String(emp.shift_id) : '';
    showModal.value = true;
};

const submit = () => {
    const data = {
        ...form.data(),
        client_id: form.client_id || null,
        service_point_id: form.service_point_id || null,
        shift_id: form.shift_id || null,
    };
    if (editingEmployee.value) {
        form.put(`/colaboradores/${editingEmployee.value.id}`, {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post('/colaboradores', {
            onSuccess: () => { showModal.value = false; form.reset(); form.status = 'activo'; },
        });
    }
};

const confirmDelete = () => {
    if (!deleteId.value) return;
    router.delete(`/colaboradores/${deleteId.value}`, { onFinish: () => { deleteId.value = null; } });
};

const openImport = () => {
    importForm.reset();
    importForm.clearErrors();
    showImportModal.value = true;
};

const onFileChange = (e: Event) => {
    importForm.file = (e.target as HTMLInputElement).files?.[0] ?? null;
};

const submitImport = () => {
    importForm.post('/colaboradores/importar', {
        forceFormData: true,
        onSuccess: (page) => {
            const flash = (page.props.flash ?? {}) as { success?: string | null; error?: string | null };
            if (flash.success) toast.success(flash.success);
            if (flash.error) toast.error(flash.error);
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const onSearch = (q: string) => router.get('/colaboradores', { ...props.filters, search: q }, { preserveState: true, replace: true });
const onPage = (p: number) => router.get('/colaboradores', { ...props.filters, page: p }, { preserveState: true });

const filteredSPs = (clientId: string) => props.servicePoints.filter((sp) => !clientId || sp.client_id === Number(clientId));
</script>

<template>
    <div class="p-6">
        <PageHeader title="Colaboradores" description="Gestión del personal de seguridad activo">
            <template #actions>
                <Button v-if="hasPermission('Importar colaboradores')" variant="outline" @click="openImport">
                    <Upload class="h-4 w-4 mr-2" /> Importar colaboradores
                </Button>
                <Button v-if="hasPermission('Crear colaboradores')" @click="openCreate">
                    <Plus class="h-4 w-4 mr-2" /> Nuevo Colaborador
                </Button>
            </template>
        </PageHeader>

        <!-- Quick filters -->
        <div class="flex gap-3 mb-4 flex-wrap">
            <div class="relative flex-1 min-w-[200px] max-w-sm">
                <Input placeholder="Buscar por nombre o número..." @input="(e) => onSearch((e.target as HTMLInputElement).value)" />
            </div>
            <Select :model-value="filters.client_id ?? '__all__'" @update:model-value="(v) => router.get('/colaboradores', { ...filters, client_id: v === '__all__' ? undefined : v }, { preserveState: true })">
                <SelectTrigger class="w-48"><SelectValue placeholder="Empresa..." /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="__all__">Todas</SelectItem>
                    <SelectItem v-for="c in clients" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                </SelectContent>
            </Select>
            <Select :model-value="filters.status ?? '__all__'" @update:model-value="(v) => router.get('/colaboradores', { ...filters, status: v === '__all__' ? undefined : v }, { preserveState: true })">
                <SelectTrigger class="w-36"><SelectValue placeholder="Estado..." /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="__all__">Todos</SelectItem>
                    <SelectItem value="activo">Activo</SelectItem>
                    <SelectItem value="inactivo">Inactivo</SelectItem>
                    <SelectItem value="baja">Baja</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="rounded-lg border bg-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-muted/30">
                    <tr>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">No. Emp</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Nombre</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Empresa</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Punto Servicio</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Turno</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Estado</th>
                        <th class="text-right p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!employees.data.length">
                        <td colspan="7"><EmptyState :icon="HardHat" title="Sin colaboradores" description="No hay colaboradores con los filtros aplicados." /></td>
                    </tr>
                    <tr v-for="emp in employees.data" :key="emp.id" class="border-t hover:bg-muted/40 transition-colors">
                        <td class="p-3 font-mono text-xs">{{ emp.employee_number }}</td>
                        <td class="p-3">
                            <div class="font-medium">{{ emp.name }} {{ emp.last_name }}</div>
                            <div v-if="emp.second_last_name" class="text-xs text-muted-foreground">{{ emp.second_last_name }}</div>
                        </td>
                        <td class="p-3 text-sm">{{ emp.client?.name ?? '—' }}</td>
                        <td class="p-3 text-sm">{{ emp.service_point?.name ?? '—' }}</td>
                        <td class="p-3 text-sm">{{ emp.shift?.name ?? '—' }}</td>
                        <td class="p-3"><StatusBadge :status="emp.status" /></td>
                        <td class="p-3">
                            <div class="flex items-center justify-end gap-1">
                                <Button v-if="hasPermission('Editar colaboradores')" variant="ghost" size="sm" @click="openEdit(emp)">
                                    <Pencil class="h-4 w-4" />
                                </Button>
                                <Button v-if="hasPermission('Eliminar colaboradores')" variant="ghost" size="sm" class="text-destructive" @click="deleteId = emp.id">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="employees.last_page > 1" class="flex justify-between items-center mt-4 text-sm">
            <span class="text-muted-foreground">{{ employees.from }}–{{ employees.to }} de {{ employees.total }}</span>
            <div class="flex gap-1">
                <Button variant="outline" size="sm" :disabled="employees.current_page <= 1" @click="onPage(employees.current_page - 1)">Anterior</Button>
                <Button variant="outline" size="sm" :disabled="employees.current_page >= employees.last_page" @click="onPage(employees.current_page + 1)">Siguiente</Button>
            </div>
        </div>

        <!-- Modal -->
        <Dialog :open="showModal" @update:open="showModal = $event">
            <DialogContent class="max-w-lg max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{ editingEmployee ? 'Editar Colaborador' : 'Nuevo Colaborador' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submit" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <Label>No. Empleado *</Label>
                            <Input v-model="form.employee_number" placeholder="EMP-001" class="mt-1" />
                            <p v-if="form.errors.employee_number" class="text-destructive text-xs mt-1">{{ form.errors.employee_number }}</p>
                        </div>
                        <div>
                            <Label>Nombre *</Label>
                            <Input v-model="form.name" placeholder="Nombre" class="mt-1" />
                        </div>
                        <div>
                            <Label>Apellido Paterno *</Label>
                            <Input v-model="form.last_name" placeholder="Apellido" class="mt-1" />
                        </div>
                        <div class="col-span-2">
                            <Label>Apellido Materno</Label>
                            <Input v-model="form.second_last_name" placeholder="Apellido materno (opcional)" class="mt-1" />
                        </div>
                        <div>
                            <Label>Email</Label>
                            <Input type="email" v-model="form.email" class="mt-1" />
                        </div>
                        <div>
                            <Label>Teléfono</Label>
                            <Input v-model="form.phone" class="mt-1" />
                        </div>
                        <div>
                            <Label>Estado *</Label>
                            <Select v-model="form.status">
                                <SelectTrigger class="mt-1"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="activo">Activo</SelectItem>
                                    <SelectItem value="inactivo">Inactivo</SelectItem>
                                    <SelectItem value="baja">Baja</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Empresa</Label>
                            <Select v-model="form.client_id">
                                <SelectTrigger class="mt-1"><SelectValue placeholder="Selecciona..." /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Sin asignar</SelectItem>
                                    <SelectItem v-for="c in clients" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Punto de Servicio</Label>
                            <Select v-model="form.service_point_id">
                                <SelectTrigger class="mt-1"><SelectValue placeholder="Selecciona..." /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Sin asignar</SelectItem>
                                    <SelectItem v-for="sp in filteredSPs(form.client_id)" :key="sp.id" :value="String(sp.id)">{{ sp.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Turno</Label>
                            <Select v-model="form.shift_id">
                                <SelectTrigger class="mt-1"><SelectValue placeholder="Selecciona..." /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Sin turno</SelectItem>
                                    <SelectItem v-for="s in shifts" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showModal = false">Cancelar</Button>
                        <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Guardando...' : 'Guardar' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <DeleteDialog
            :open="!!deleteId"
            title="¿Eliminar colaborador?"
            description="El colaborador será eliminado del sistema (soft delete). Sus registros de asistencia se conservan."
            @update:open="deleteId = null"
            @confirm="confirmDelete"
        />

        <!-- Import modal -->
        <Dialog :open="showImportModal" @update:open="showImportModal = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Importar colaboradores</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submitImport" class="space-y-3">
                    <p class="text-sm text-muted-foreground">
                        Sube un archivo Excel (.xlsx, .xls o .csv) con las columnas:
                        <code class="text-xs">employee_number, name, last_name, second_last_name, email, phone, status, client_id, service_point_id, shift_id</code>.
                    </p>
                    <div>
                        <Label>Archivo *</Label>
                        <Input type="file" accept=".xlsx,.xls,.csv" class="mt-1" @change="onFileChange" />
                        <p v-if="importForm.errors.file" class="text-destructive text-xs mt-1">{{ importForm.errors.file }}</p>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showImportModal = false">Cancelar</Button>
                        <Button type="submit" :disabled="importForm.processing || !importForm.file">
                            {{ importForm.processing ? 'Importando...' : 'Importar' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
