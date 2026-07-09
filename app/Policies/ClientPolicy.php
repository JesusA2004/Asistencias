<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Ver empresas');
    }

    public function view(User $user, Client $client): bool
    {
        return $user->can('Ver empresas');
    }

    public function create(User $user): bool
    {
        return $user->can('Crear empresas');
    }

    public function update(User $user, Client $client): bool
    {
        return $user->can('Editar empresas');
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->can('Eliminar empresas');
    }
}
