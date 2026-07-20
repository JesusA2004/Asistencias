<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Regla de alcance compartida entre la captura de asistencia por supervisor,
 * la política de evidencias fotográficas y la galería de evidencias: un
 * administrador/RH opera sobre todo; un supervisor solo sobre lo que tenga
 * en supervisor_assignments (asignación a toda la empresa si service_point_id
 * es null, o a un punto de servicio específico).
 */
class SupervisorScope
{
    public static function hasBroadAccess(User $user): bool
    {
        return $user->hasRole('administrador') || $user->hasRole('rh');
    }

    public static function assignmentsForClient(User $user, int $clientId): Collection
    {
        return $user->supervisorAssignments()->where('client_id', $clientId)->get();
    }

    public static function hasAccessToServicePoint(User $user, int $clientId, ?int $servicePointId): bool
    {
        if (self::hasBroadAccess($user)) {
            return true;
        }

        $assignments = self::assignmentsForClient($user, $clientId);

        if ($assignments->isEmpty()) {
            return false;
        }

        $hasClientWideAccess = $assignments->contains(fn ($a) => $a->service_point_id === null);
        $hasSpecificPointAccess = $servicePointId !== null
            && $assignments->contains(fn ($a) => (int) $a->service_point_id === $servicePointId);

        return $hasClientWideAccess || $hasSpecificPointAccess;
    }

    /** IDs de client_id a los que el usuario tiene algún acceso (para scoping de listados). */
    public static function assignedClientIds(User $user): Collection
    {
        return $user->supervisorAssignments()->pluck('client_id')->unique();
    }

    /** IDs de service_point_id asignados explícitamente (asignaciones no client-wide). */
    public static function assignedServicePointIds(User $user): Collection
    {
        return $user->supervisorAssignments()->whereNotNull('service_point_id')->pluck('service_point_id')->unique();
    }
}
