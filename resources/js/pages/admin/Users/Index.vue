<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, Users } from '@lucide/vue';
import DeleteDialog from '@/components/DeleteDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
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
import type { AppUser, PaginatedData, Role } from '@/types/models';

type UserWithRoles = AppUser & { email_verified_at: string | null; created_at: string };

const props = defineProps<{
    users: PaginatedData<UserWithRoles>;
    roles: Role[];
    filters: Record<string, string | undefined>;
}>();

const { hasPermission } = usePermissions();
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
    showModal.value = true;
};

const openEdit = (user: UserWithRoles) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.password_confirmation = '';
    form.role = user.roles?.[0] ?? '';
    showModal.value = true;
};

const submit = () => {
    if (editingUser.value) {
        form.put(`/usuarios/${editingUser.value.id}`, {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post('/usuarios', {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
};

const confirmDelete = () => {
    if (!deleteId.value) return;
    router.delete(`/usuarios/${deleteId.value}`, { onFinish: () => { deleteId.value = null; } });
};

const roleColors: Record<string, string> = {
    administrador: 'bg-red-100 text-red-700 border-red-200',
    supervisor: 'bg-blue-100 text-blue-700 border-blue-200',
    colaborador: 'bg-green-100 text-green-700 border-green-200',
    rh: 'bg-purple-100 text-purple-700 border-purple-200',
};
</script>

<template>
    <div class="p-6">
        <PageHeader title="Usuarios" description="Administración de cuentas de usuario">
            <template #actions>
                <Button v-if="hasPermission('Crear usuarios')" @click="openCreate">
                    <Plus class="h-4 w-4 mr-2" /> Nuevo Usuario
                </Button>
            </template>
        </PageHeader>

        <div class="rounded-lg border bg-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-muted/30">
                    <tr>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Nombre</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Email</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Rol</th>
                        <th class="text-left p-3 text-xs uppercase tracking-wider text-muted-foreground font-semibold">Creado</th>
                        <th class="text-right p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!users.data.length">
                        <td colspan="5"><EmptyState :icon="Users" title="Sin usuarios" /></td>
                    </tr>
                    <tr v-for="user in users.data" :key="user.id" class="border-t hover:bg-muted/40 transition-colors">
                        <td class="p-3 font-medium">{{ user.name }}</td>
                        <td class="p-3 text-muted-foreground">{{ user.email }}</td>
                        <td class="p-3">
                            <Badge
                                v-for="role in (user.roles ?? [])"
                                :key="role"
                                variant="outline"
                                :class="['text-xs', roleColors[role] ?? '']"
                            >
                                {{ role }}
                            </Badge>
                        </td>
                        <td class="p-3 text-xs text-muted-foreground">{{ user.created_at }}</td>
                        <td class="p-3">
                            <div class="flex items-center justify-end gap-1">
                                <Button v-if="hasPermission('Editar usuarios')" variant="ghost" size="sm" @click="openEdit(user)">
                                    <Pencil class="h-4 w-4" />
                                </Button>
                                <Button v-if="hasPermission('Eliminar usuarios')" variant="ghost" size="sm" class="text-destructive" @click="deleteId = user.id">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Dialog :open="showModal" @update:open="showModal = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editingUser ? 'Editar Usuario' : 'Nuevo Usuario' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label>Nombre *</Label>
                        <Input v-model="form.name" placeholder="Nombre completo" class="mt-1" />
                        <p v-if="form.errors.name" class="text-destructive text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <Label>Email *</Label>
                        <Input type="email" v-model="form.email" placeholder="correo@ejemplo.com" class="mt-1" />
                        <p v-if="form.errors.email" class="text-destructive text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <Label>{{ editingUser ? 'Nueva contraseña (dejar en blanco para no cambiar)' : 'Contraseña *' }}</Label>
                        <Input type="password" v-model="form.password" placeholder="••••••••" class="mt-1" />
                        <p v-if="form.errors.password" class="text-destructive text-xs mt-1">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <Label>Confirmar contraseña</Label>
                        <Input type="password" v-model="form.password_confirmation" placeholder="••••••••" class="mt-1" />
                    </div>
                    <div>
                        <Label>Rol *</Label>
                        <Select v-model="form.role">
                            <SelectTrigger class="mt-1"><SelectValue placeholder="Selecciona rol..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.role" class="text-destructive text-xs mt-1">{{ form.errors.role }}</p>
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
            title="¿Eliminar usuario?"
            description="El usuario perderá acceso al sistema."
            @update:open="deleteId = null"
            @confirm="confirmDelete"
        />
    </div>
</template>
