<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, Users, X } from '@lucide/vue';
import type {ColumnDef} from '@tanstack/vue-table';
import { computed, ref, watch } from 'vue';
import AppDataTable from '@/components/AppDataTable.vue';
import DeleteDialog from '@/components/DeleteDialog.vue';
import FormActions from '@/components/FormActions.vue';
import FormDialogContent from '@/components/FormDialogContent.vue';
import FormInput from '@/components/FormInput.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { usePermissions } from '@/composables/usePermissions';
import { formatDateTimeMx } from '@/lib/formatters';
import type { AppUser, PaginatedData, Role } from '@/types/models';

type UserWithRoles = Omit<AppUser, 'roles'> & { roles: Role[]; email_verified_at: string | null; created_at: string };

const props = defineProps<{
    users: PaginatedData<UserWithRoles>;
    roles: Role[];
    filters: Record<string, string | undefined>;
}>();

const { hasPermission, user: currentUser } = usePermissions();
const showModal = ref(false);
const deleteId = ref<number | null>(null);
const editingUser = ref<UserWithRoles | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
});

const openCreate = () => {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (user: UserWithRoles) => {
    editingUser.value = user;
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.password_confirmation = '';
    form.role = user.roles?.[0]?.name ?? '';
    showModal.value = true;
};

const submit = () => {
    if (editingUser.value) {
        form.put(`/usuarios/${editingUser.value.id}`, {
            onSuccess: () => {
 showModal.value = false; 
},
        });
    } else {
        form.post('/usuarios', {
            onSuccess: () => {
 showModal.value = false; form.reset(); 
},
        });
    }
};

const confirmDelete = () => {
    if (!deleteId.value) {
return;
}

    router.delete(`/usuarios/${deleteId.value}`, { onFinish: () => {
 deleteId.value = null; 
} });
};

const onSearch = (q: string) => router.get('/usuarios', { ...props.filters, search: q }, { preserveState: true, replace: true });
const onPage = (p: number) => router.get('/usuarios', { ...props.filters, page: p }, { preserveState: true });

const roleOptions = computed(() => props.roles.map((r) => ({ value: r.name, label: r.name })));
const filterRole = ref(props.filters.role ?? '');
const filterRoleModel = computed({
    get: () => filterRole.value,
    set: (v: string | number | null) => {
        filterRole.value = v == null ? '' : String(v);
    },
});
const hasActiveFilters = computed(() => !!(props.filters.search || props.filters.role));

watch(filterRole, (value) => {
    router.get('/usuarios', { ...props.filters, role: value || undefined }, { preserveState: true, replace: true });
});

const clearFilters = () => {
    filterRole.value = '';
    router.get('/usuarios', {}, { preserveState: true, replace: true });
};

const roleModel = computed({
    get: () => form.role,
    set: (v: string | number | null) => {
        form.role = v == null ? '' : String(v);
    },
});

const roleColors: Record<string, string> = {
    administrador: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-950 dark:text-red-400',
    supervisor: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950 dark:text-blue-400',
    colaborador: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-400',
    rh: 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-950 dark:text-purple-400',
};

const columns: ColumnDef<UserWithRoles>[] = [
    { accessorKey: 'name', header: 'Nombre' },
    { accessorKey: 'email', header: 'Email' },
    { accessorKey: 'roles', header: 'Rol' },
    { accessorKey: 'created_at', header: 'Creado' },
];
</script>

<template>
    <div class="p-6">
        <PageHeader title="Usuarios" description="Administra cuentas y permisos de acceso al sistema.">
            <template #actions>
                <Button v-if="hasPermission('Crear usuarios')" @click="openCreate">
                    <Plus class="h-4 w-4 mr-2" /> Nuevo Usuario
                </Button>
            </template>
        </PageHeader>

        <AppDataTable
            :columns="columns"
            :data="users.data"
            :pagination="users"
            search-placeholder="Buscar por nombre o email..."
            empty-title="Sin usuarios"
            :empty-icon="Users"
            @search="onSearch"
            @page-change="onPage"
        >
            <template #filters>
                <SearchableSelect
                    v-model="filterRoleModel"
                    :options="roleOptions"
                    placeholder="Todos los roles"
                    class="w-48"
                />
                <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearFilters">
                    <X class="h-3.5 w-3.5 mr-1" /> Limpiar filtros
                </Button>
            </template>
            <template #cell-roles="{ item }">
                <Badge
                    v-for="role in (item.roles ?? [])"
                    :key="role.id"
                    variant="outline"
                    :class="['text-xs capitalize', roleColors[role.name] ?? '']"
                >
                    {{ role.name }}
                </Badge>
            </template>
            <template #cell-created_at="{ value }">
                <span class="text-xs text-muted-foreground">{{ formatDateTimeMx(value as string) }}</span>
            </template>
            <template #actions="{ item }">
                <Button v-if="hasPermission('Editar usuarios')" variant="ghost" size="sm" @click="openEdit(item)">
                    <Pencil class="h-4 w-4" />
                </Button>
                <Button
                    v-if="hasPermission('Eliminar usuarios') && item.id !== currentUser.id"
                    variant="ghost"
                    size="sm"
                    class="text-destructive"
                    @click="deleteId = item.id"
                >
                    <Trash2 class="h-4 w-4" />
                </Button>
            </template>
        </AppDataTable>

        <Dialog :open="showModal" @update:open="showModal = $event">
            <FormDialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editingUser ? 'Editar Usuario' : 'Nuevo Usuario' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submit" class="space-y-4">
                    <FormInput v-model="form.name" label="Nombre" required placeholder="Nombre completo" :error="form.errors.name" />
                    <FormInput v-model="form.email" type="email" label="Email" required placeholder="correo@ejemplo.com" :error="form.errors.email" />
                    <FormInput
                        v-model="form.password"
                        type="password"
                        :label="editingUser ? 'Nueva contraseña (dejar en blanco para no cambiar)' : 'Contraseña'"
                        :required="!editingUser"
                        placeholder="••••••••"
                        :error="form.errors.password"
                    />
                    <FormInput v-model="form.password_confirmation" type="password" label="Confirmar contraseña" placeholder="••••••••" />
                    <SearchableSelect
                        v-model="roleModel"
                        :options="roleOptions"
                        label="Rol"
                        required
                        placeholder="Selecciona rol..."
                        :error="form.errors.role"
                    />
                    <FormActions :processing="form.processing" @cancel="showModal = false" />
                </form>
            </FormDialogContent>
        </Dialog>

        <DeleteDialog
            :open="!!deleteId"
            title="¿Eliminar usuario?"
            description="El usuario perderá acceso al sistema."
            @update:open="deleteId = null"
            @confirm="confirmDelete"
        />
    </div>
</template>
