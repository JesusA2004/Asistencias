<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendancePhoto;
use App\Models\Client;
use App\Models\ServicePoint;
use App\Models\User;
use App\Support\SupervisorScope;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceEvidenceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AttendancePhoto::class);

        $user = $request->user();
        $hasBroadAccess = $user->can('Ver todas las evidencias');
        $hasLocationScopedAccess = $user->can('Ver evidencias de sus ubicaciones');

        $assignedClientIds = $hasBroadAccess ? collect() : SupervisorScope::assignedClientIds($user);
        $assignedSPIds = $hasBroadAccess ? collect() : SupervisorScope::assignedServicePointIds($user);

        $photos = AttendancePhoto::query()
            ->with([
                'employee:id,employee_number,name,last_name',
                'client:id,name',
                'servicePoint:id,name',
                'capturedBy:id,name',
                'attendance:id,status',
            ])
            ->when(! $hasBroadAccess, function ($q) use ($hasLocationScopedAccess, $assignedClientIds, $assignedSPIds) {
                if (! $hasLocationScopedAccess) {
                    // Ni "ver todas" ni "ver de sus ubicaciones": no debería llegar aquí por el
                    // middleware de permiso base, pero por seguridad no se muestra nada.
                    $q->whereRaw('1 = 0');

                    return;
                }

                $q->where(function ($q) use ($assignedClientIds, $assignedSPIds) {
                    $q->whereIn('client_id', $assignedClientIds)
                        ->where(function ($q) use ($assignedSPIds) {
                            $q->whereIn('service_point_id', $assignedSPIds)
                                ->orWhereNull('service_point_id');
                        });
                });
            })
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('captured_at', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->whereDate('captured_at', '<=', $v))
            ->when($request->client_id, fn ($q, $v) => $q->where('client_id', $v))
            ->when($request->service_point_id, fn ($q, $v) => $q->where('service_point_id', $v))
            ->when($request->supervisor_id, fn ($q, $v) => $q->where('captured_by_user_id', $v))
            ->when($request->employee_id, fn ($q, $v) => $q->where('employee_id', $v))
            ->when($request->capture_type, fn ($q, $v) => $q->where('capture_type', $v))
            ->when($request->capture_origin, fn ($q, $v) => $q->where('capture_origin', $v))
            ->when($request->status, fn ($q, $v) => $q->whereHas('attendance', fn ($q) => $q->where('status', $v)))
            ->when($request->search, fn ($q, $s) => $q->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%")
                ->orWhere('employee_number', 'like', "%{$s}%")))
            ->orderByDesc('captured_at')
            ->paginate(24)
            ->withQueryString();

        $clients = Client::when(! $hasBroadAccess, fn ($q) => $q->whereIn('id', $assignedClientIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        $servicePoints = ServicePoint::whereIn('client_id', $hasBroadAccess ? Client::pluck('id') : $assignedClientIds)
            ->orderBy('name')
            ->get(['id', 'client_id', 'name']);

        $supervisors = User::whereIn('id', AttendancePhoto::query()
            ->when(! $hasBroadAccess, fn ($q) => $q->whereIn('client_id', $assignedClientIds))
            ->distinct()
            ->pluck('captured_by_user_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('admin/AttendanceEvidence/Index', [
            'photos' => $photos,
            'clients' => $clients,
            'servicePoints' => $servicePoints,
            'supervisors' => $supervisors,
            'filters' => $request->only([
                'date_from', 'date_to', 'client_id', 'service_point_id', 'supervisor_id',
                'employee_id', 'capture_type', 'capture_origin', 'status', 'search',
            ]),
            'canReview' => $user->can('Revisar evidencias de asistencia'),
        ]);
    }
}
