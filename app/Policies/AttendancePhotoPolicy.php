<?php

namespace App\Policies;

use App\Models\AttendancePhoto;
use App\Models\User;
use App\Support\SupervisorScope;

class AttendancePhotoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Ver evidencias de asistencia');
    }

    public function view(User $user, AttendancePhoto $photo): bool
    {
        if (! $user->can('Ver evidencias de asistencia')) {
            return false;
        }

        if ($user->can('Ver todas las evidencias')) {
            return true;
        }

        if ($user->can('Ver evidencias de sus ubicaciones')) {
            return SupervisorScope::hasAccessToServicePoint($user, $photo->client_id, $photo->service_point_id);
        }

        return $user->employee?->id === $photo->employee_id;
    }
}
