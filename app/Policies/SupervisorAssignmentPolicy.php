<?php

namespace App\Policies;

use App\Models\SupervisorAssignment;
use App\Models\User;

class SupervisorAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Ver asignaciones');
    }

    public function create(User $user): bool
    {
        return $user->can('Crear asignaciones');
    }

    public function delete(User $user, SupervisorAssignment $assignment): bool
    {
        return $user->can('Eliminar asignaciones');
    }
}
