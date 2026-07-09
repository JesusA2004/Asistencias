<?php

namespace App\Policies;

use App\Models\AttendanceAudit;
use App\Models\User;

class AttendanceAuditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Ver auditoría');
    }

    public function create(User $user): bool
    {
        return $user->can('Exportar reportes');
    }
}
