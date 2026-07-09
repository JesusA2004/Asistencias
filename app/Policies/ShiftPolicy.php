<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;

class ShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Ver turnos');
    }

    public function create(User $user): bool
    {
        return $user->can('Crear turnos');
    }

    public function update(User $user, Shift $shift): bool
    {
        return $user->can('Editar turnos');
    }

    public function delete(User $user, Shift $shift): bool
    {
        return $user->can('Eliminar turnos');
    }
}
