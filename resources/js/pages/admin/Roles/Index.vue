<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Shield, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import DeleteDialog from '@/components/DeleteDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FormActions from '@/components/FormActions.vue';
import FormDialogContent from '@/components/FormDialogContent.vue';
import FormInput from '@/components/FormInput.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { usePermissions } from '@/composables/usePermissions';
import type { Permission, Role } from '@/types/models';

const props = defineProps<{
    roles: (Role & { users_count: number })[];
    permissions: Permission[];
}>();

const { hasPermission } = usePermissions();
const showModal = ref(false);
const deleteId = ref<number | null>(null);
const editingRole = ref<Role | null>(null);

const form = useForm({
    name: '',
    permissions: [] as string[],
});

const groupedPermissions = computed(() => {
    const groups: Record<string, Permission[]> = {};

    for (const p of props.permissions) {
        const parts = p.name.split(' ');
        const entity = parts.length > 1 ? parts.slice(1).join(' ') : 'General';

        if (!groups[entity]) {
groups[entity] = [];
}

        groups[entity].push(p);
    }

    return groups;
});

const togglePermission = (name: string) => {
    const idx = form.permissions.indexOf(name);

    if (idx >= 0) {
form.permissions.splice(idx, 1);
} else {
form.permissions.push(name);
}
};

const isGroupFullySelected = (perms: Permission[]) => perms.every((p) => form.permissions.includes(p.name));

const toggleGroup = (perms: Permission[]) => {
    if (isGroupFullySelected(perms)) {
        form.permissions = form.permissions.filter((name) => !perms.some((p) => p.name === name));
    } else {
        const names = perms.map((p) => p.name);
        form.permissions = Array.from(new Set([...form.permissions, ...names]));
    }
};

const toggleAll = () => {
    if (form.permissions.length === props.permissions.length) {
        form.permissions = [];
    } else {
        form.permissions = props.permissions.map((p) => p.name);
    }
};

const openCreate = () => {
    editingRole.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (role: Role) => {
    editingRole.value = role;
    form.clearErrors();
    form.name = role.name;
    form.permissions = (role.permissions ?? []).map((p) => p.name);
    showModal.value = true;
};

const submit = () => {
    if (editingRole.value) {
        form.put(`/roles/${editingRole.value.id}`, {
            onSuccess: () => {
 showModal.value = false; 
},
        });
    } else {
        form.post('/roles', {
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

    router.delete(`/roles/${deleteId.value}`, { onFinish: () => {
 deleteId.value = null; 
} });
};

const SYSTEM_ROLES = ['administrador', 'supervisor', 'colaborador', 'rh'];
</script>

<template>
    <div class="p-6">
        <PageHeader title="Roles y Permisos" description="Controla el acceso de cada tipo de usuario">
            <template #actions>
                <Button v-if="hasPermission('Crear roles y permisos')" @click="openCreate">
                    <Plus class="h-4 w-4 mr-2" /> Nuevo Rol
                </Button>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
                v-for="role in roles"
                :key="role.id"
                class="bg-card border rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <Shield class="h-4 w-4 text-primary" />
                            <h3 class="font-semibold capitalize">{{ role.name }}</h3>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">{{ role.users_count }} usuario(s)</p>
                    </div>
                    <div class="flex gap-1">
                        <Button v-if="hasPermission('Editar roles y permisos')" variant="ghost" size="sm" @click="openEdit(role)">
                            <Pencil class="h-3.5 w-3.5" />
                        </Button>
                        <Button
                            v-if="hasPermission('Eliminar roles y permisos') && !SYSTEM_ROLES.includes(role.name)"
                            variant="ghost" size="sm" class="text-destructive"
                            @click="deleteId = role.id"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </div>
                <Separator class="my-3" />
                <div class="flex flex-wrap gap-1">
                    <Badge
                        v-for="perm in (role.permissions ?? []).slice(0, 6)"
                        :key="perm.id"
                        variant="secondary"
                        class="text-xs"
                    >
                        {{ perm.name }}
                    </Badge>
                    <Badge v-if="(role.permissions ?? []).length > 6" variant="outline" class="text-xs">
                        +{{ (role.permissions ?? []).length - 6 }} más
                    </Badge>
                </div>
            </div>

            <div v-if="!roles.length" class="lg:col-span-4">
                <EmptyState :icon="Shield" title="Sin roles" />
            </div>
        </div>

        <!-- Modal -->
        <Dialog :open="showModal" @update:open="showModal = $event">
            <FormDialogContent class="sm:max-w-3xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{ editingRole ? `Editar rol: ${editingRole.name}` : 'Nuevo Rol' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submit" class="space-y-4">
                    <FormInput
                        v-model="form.name"
                        label="Nombre del rol"
                        required
                        :disabled="!!(editingRole && SYSTEM_ROLES.includes(editingRole.name))"
                        placeholder="Ej. coordinador"
                        :error="form.errors.name"
                        :hint="editingRole && SYSTEM_ROLES.includes(editingRole.name) ? 'Los roles del sistema no pueden renombrarse.' : undefined"
                    />

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium">Permisos</span>
                            <Button type="button" variant="ghost" size="sm" @click="toggleAll">
                                {{ form.permissions.length === permissions.length ? 'Desmarcar todos' : 'Marcar todos' }}
                            </Button>
                        </div>
                        <div class="space-y-4 border rounded-lg p-3 bg-muted/20 max-h-96 overflow-y-auto">
                            <div v-for="(perms, group) in groupedPermissions" :key="group">
                                <div class="flex items-center justify-between mb-1.5">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{{ group }}</p>
                                    <button
                                        type="button"
                                        class="text-xs text-primary hover:underline"
                                        @click="toggleGroup(perms)"
                                    >
                                        {{ isGroupFullySelected(perms) ? 'Ninguno' : 'Todos' }}
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-1.5">
                                    <label
                                        v-for="perm in perms"
                                        :key="perm.id"
                                        class="flex items-center gap-2 cursor-pointer py-0.5"
                                    >
                                        <Checkbox
                                            :checked="form.permissions.includes(perm.name)"
                                            @update:checked="togglePermission(perm.name)"
                                        />
                                        <span class="text-xs">{{ perm.name }}</span>
                                    </label>
                                </div>
                                <Separator class="mt-3" />
                            </div>
                        </div>
                    </div>

                    <FormActions :processing="form.processing" @cancel="showModal = false" />
                </form>
            </FormDialogContent>
        </Dialog>

        <DeleteDialog
            :open="!!deleteId"
            title="¿Eliminar rol?"
            description="Los usuarios con este rol perderán sus permisos asignados."
            @update:open="deleteId = null"
            @confirm="confirmDelete"
        />
    </div>
</template>
