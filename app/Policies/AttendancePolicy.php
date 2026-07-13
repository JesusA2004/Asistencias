<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Ver asistencias');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $user->can('Ver asistencias');
    }

    public function create(User $user): bool
    {
        return $user->can('Registrar asistencias');
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $user->can('Corregir asistencias') || $user->can('Editar asistencias');
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->can('Eliminar asistencias');
    }

    /**
     * Captura completa manual (crear o editar entrada/salida/estado en una sola pantalla):
     * reservada a administradores, RH, o cualquiera con permiso explícito de corrección.
     */
    public function manualCapture(User $user): bool
    {
        return $user->hasRole('administrador') || $user->hasRole('rh') || $user->can('Corregir asistencias');
    }
}
