<?php

namespace App\Policies;

use App\Models\ServicePoint;
use App\Models\User;

class ServicePointPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Ver puntos de servicio');
    }

    public function view(User $user, ServicePoint $servicePoint): bool
    {
        return $user->can('Ver puntos de servicio');
    }

    public function create(User $user): bool
    {
        return $user->can('Crear puntos de servicio');
    }

    public function update(User $user, ServicePoint $servicePoint): bool
    {
        return $user->can('Editar puntos de servicio');
    }

    public function delete(User $user, ServicePoint $servicePoint): bool
    {
        return $user->can('Eliminar puntos de servicio');
    }
}
