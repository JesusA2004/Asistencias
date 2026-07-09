<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Ver colaboradores');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can('Ver colaboradores');
    }

    public function create(User $user): bool
    {
        return $user->can('Crear colaboradores');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('Editar colaboradores');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('Eliminar colaboradores');
    }
}
