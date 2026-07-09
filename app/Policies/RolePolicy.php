<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Ver roles y permisos');
    }

    public function create(User $user): bool
    {
        return $user->can('Crear roles y permisos');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('Editar roles y permisos');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('Eliminar roles y permisos');
    }
}
