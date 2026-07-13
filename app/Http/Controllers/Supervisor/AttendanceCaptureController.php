<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceAudit;
use App\Models\AttendanceEvent;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceCaptureController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $this->authorize('create', Attendance::class);

        // Los administradores (y roles con acceso amplio) no dependen de una fila en
        // supervisor_assignments: ven todas las empresas/puntos activos. Un supervisor
        // normal sigue restringido a lo que tenga asignado explícitamente.
        $hasBroadAccess = $user->hasRole('administrador');
        $assignedClientIds = $user->supervisorAssignments()->pluck('client_id')->unique();
        $assignedSPIds = $user->supervisorAssignments()->whereNotNull('service_point_id')->pluck('service_point_id')->unique();
        $hasAssignments = $hasBroadAccess || $assignedClientIds->isNotEmpty();

        $clients = Client::when(! $hasBroadAccess, fn ($q) => $q->whereIn('id', $assignedClientIds))
            ->where('status', 'activo')
            ->orderBy('name')
            ->get(['id', 'name']);

        $clientIdsInScope = $hasBroadAccess ? $clients->pluck('id') : $assignedClientIds;

        // Se cargan todos los puntos de servicio en alcance (de todas las empresas) para
        // que el frontend filtre por empresa al instante, sin ida y vuelta al servidor.
        $servicePoints = ServicePoint::whereIn('client_id', $clientIdsInScope)
            ->where('status', 'activo')
            ->when(! $hasBroadAccess && $assignedSPIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $assignedSPIds))
            ->orderBy('name')
            ->get(['id', 'client_id', 'name']);

        $employees = collect();
        $existingAttendances = collect();

        $clientId = $request->integer('client_id');
        $servicePointId = $request->integer('service_point_id');

        if ($clientId && $servicePointId && ($hasBroadAccess || $assignedClientIds->contains($clientId))) {
            $spBelongsToClient = $servicePoints->contains(
                fn ($sp) => (int) $sp->id === $servicePointId && (int) $sp->client_id === $clientId
            );

            if ($spBelongsToClient) {
                $date = $request->date ?? Carbon::today()->format('Y-m-d');

                $employees = Employee::where('service_point_id', $servicePointId)
                    ->where('status', 'activo')
                    ->with('shift:id,name')
                    ->orderBy('last_name')
                    ->get(['id', 'employee_number', 'name', 'last_name', 'second_last_name', 'shift_id']);

                $existingAttendances = Attendance::where('service_point_id', $servicePointId)
                    ->whereDate('attendance_date', $date)
                    ->get()
                    ->keyBy('employee_id');
            }
        }

        return Inertia::render('supervisor/AttendanceCapture', [
            'clients' => $clients,
            'servicePoints' => $servicePoints,
            'employees' => $employees,
            'existingAttendances' => $existingAttendances,
            'filters' => $request->only(['client_id', 'service_point_id', 'date']),
            'hasAssignments' => $hasAssignments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $request->validate([
            'service_point_id' => 'required|exists:service_points,id',
            'client_id' => 'required|exists:clients,id',
            'attendance_date' => 'required|date|before_or_equal:today',
            'records' => 'required|array|min:1',
            'records.*.employee_id' => 'required|exists:employees,id',
            'records.*.status' => 'required|in:presente,falta,descanso,permiso,incapacidad,retardo',
            'records.*.entry_time' => 'nullable|date_format:H:i',
            'records.*.exit_time' => 'nullable|date_format:H:i',
            'records.*.notes' => 'nullable|string|max:300',
        ]);

        $user = auth()->user();
        $hasBroadAccess = $user->hasRole('administrador');

        if (! $hasBroadAccess) {
            $assignments = $user->supervisorAssignments()
                ->where('client_id', $request->client_id)
                ->get();

            if ($assignments->isEmpty()) {
                return back()->with('error', 'No tienes asignación para esta empresa.');
            }

            $hasClientWideAccess = $assignments->contains(fn ($a) => $a->service_point_id === null);
            $hasSpecificPointAccess = $assignments->contains(fn ($a) => (int) $a->service_point_id === (int) $request->service_point_id);

            if (! $hasClientWideAccess && ! $hasSpecificPointAccess) {
                return back()->with('error', 'No tienes asignación para este punto de servicio.');
            }
        }

        $servicePointBelongsToClient = ServicePoint::where('id', $request->service_point_id)
            ->where('client_id', $request->client_id)
            ->exists();

        if (! $servicePointBelongsToClient) {
            return back()->with('error', 'El punto de servicio no pertenece a la empresa seleccionada.');
        }

        $employeeIds = collect($request->records)->pluck('employee_id')->unique();

        $validEmployeeCount = Employee::whereIn('id', $employeeIds)
            ->where('client_id', $request->client_id)
            ->where('service_point_id', $request->service_point_id)
            ->count();

        if ($validEmployeeCount !== $employeeIds->count()) {
            return back()->with('error', 'Uno o más colaboradores no pertenecen a la empresa o punto de servicio seleccionados.');
        }

        $createdCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($request, $user, &$createdCount, &$skippedCount) {
            foreach ($request->records as $record) {
                $existing = Attendance::where('employee_id', $record['employee_id'])
                    ->whereDate('attendance_date', $request->attendance_date)
                    ->where('service_point_id', $request->service_point_id)
                    ->first();

                if ($existing) {
                    $skippedCount++;

                    continue; // Supervisor cannot overwrite existing records
                }

                $createdCount++;

                $employee = Employee::find($record['employee_id']);

                $attendance = Attendance::create([
                    'employee_id' => $record['employee_id'],
                    'client_id' => $request->client_id,
                    'service_point_id' => $request->service_point_id,
                    'shift_id' => $employee?->shift_id,
                    'supervisor_id' => $user->id,
                    'attendance_date' => $request->attendance_date,
                    'status' => $record['status'],
                    'entry_time' => $record['entry_time'] ?? null,
                    'exit_time' => $record['exit_time'] ?? null,
                    'notes' => $record['notes'] ?? null,
                    'created_by' => $user->id,
                ]);

                AttendanceAudit::create([
                    'attendance_id' => $attendance->id,
                    'action' => 'creado',
                    'old_values' => null,
                    'new_values' => $attendance->only(['status', 'entry_time', 'exit_time', 'notes', 'attendance_date']),
                    'reason' => 'Captura inicial de supervisor',
                    'changed_by' => $user->id,
                    'created_at' => now(),
                ]);

                AttendanceEvent::create([
                    'attendance_id' => $attendance->id,
                    'event_type' => 'asistencia',
                    'event_time' => now(),
                    'value' => $attendance->status,
                    'created_by' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'notes' => null,
                    'created_at' => now(),
                ]);

                if ($attendance->entry_time !== null) {
                    AttendanceEvent::create([
                        'attendance_id' => $attendance->id,
                        'event_type' => 'entrada',
                        'event_time' => Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $attendance->entry_time),
                        'value' => (string) $attendance->entry_time,
                        'created_by' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'notes' => null,
                        'created_at' => now(),
                    ]);
                }

                if ($attendance->exit_time !== null) {
                    AttendanceEvent::create([
                        'attendance_id' => $attendance->id,
                        'event_type' => 'salida',
                        'event_time' => Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $attendance->exit_time),
                        'value' => (string) $attendance->exit_time,
                        'created_by' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'notes' => null,
                        'created_at' => now(),
                    ]);
                }
            }
        });

        $message = "{$createdCount} asistencia(s) registrada(s) correctamente.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} ya existía(n) y se omitieron.";
        }

        return back()->with('success', $message);
    }
}
