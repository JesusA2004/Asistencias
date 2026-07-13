<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceAudit;
use App\Models\AttendanceEvent;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceCaptureController extends Controller
{
    /**
     * Administradores y RH no dependen de una fila en supervisor_assignments: operan
     * sobre todas las empresas/puntos activos. Un supervisor normal sigue restringido
     * a lo que tenga asignado explícitamente.
     */
    private function hasBroadCaptureAccess(User $user): bool
    {
        return $user->hasRole('administrador') || $user->hasRole('rh');
    }

    public function index(Request $request): Response
    {
        $user = auth()->user();
        $this->authorize('create', Attendance::class);

        $hasBroadAccess = $this->hasBroadCaptureAccess($user);
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
                    ->with('shift:id,name,start_time,end_time,tolerance_minutes')
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
            'canUseManualCapture' => $user->can('manualCapture', Attendance::class),
        ]);
    }

    /** Null si el usuario tiene acceso a la empresa/punto solicitados; el mensaje de error si no. */
    private function checkAssignmentAccess(Request $request, User $user): ?string
    {
        if ($this->hasBroadCaptureAccess($user)) {
            return null;
        }

        $assignments = $user->supervisorAssignments()->where('client_id', $request->client_id)->get();

        if ($assignments->isEmpty()) {
            return 'No tienes asignación para esta empresa.';
        }

        $hasClientWideAccess = $assignments->contains(fn ($a) => $a->service_point_id === null);
        $hasSpecificPointAccess = $assignments->contains(fn ($a) => (int) $a->service_point_id === (int) $request->service_point_id);

        if (! $hasClientWideAccess && ! $hasSpecificPointAccess) {
            return 'No tienes asignación para este punto de servicio.';
        }

        return null;
    }

    private function checkServicePointBelongsToClient(Request $request): ?string
    {
        $belongs = ServicePoint::where('id', $request->service_point_id)
            ->where('client_id', $request->client_id)
            ->exists();

        return $belongs ? null : 'El punto de servicio no pertenece a la empresa seleccionada.';
    }

    private function checkEmployeesBelong(array $employeeIds, int $clientId, int $servicePointId): ?string
    {
        $ids = collect($employeeIds)->unique();

        $validCount = Employee::whereIn('id', $ids)
            ->where('client_id', $clientId)
            ->where('service_point_id', $servicePointId)
            ->count();

        return $validCount === $ids->count() ? null : 'Uno o más colaboradores no pertenecen a la empresa o punto de servicio seleccionados.';
    }

    public function storeEntry(Request $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_point_id' => 'required|exists:service_points,id',
            'attendance_date' => 'required|date|before_or_equal:today',
            'entries' => 'required|array|min:1',
            'entries.*.employee_id' => 'required|exists:employees,id',
            'entries.*.entry_time' => 'required|date_format:H:i',
            'entries.*.status' => 'required|in:presente,retardo',
            'entries.*.notes' => 'nullable|string|max:300',
        ]);

        $user = auth()->user();

        if ($error = $this->checkAssignmentAccess($request, $user)) {
            return back()->with('error', $error);
        }

        if ($error = $this->checkServicePointBelongsToClient($request)) {
            return back()->with('error', $error);
        }

        $employeeIds = collect($request->entries)->pluck('employee_id')->all();

        if ($error = $this->checkEmployeesBelong($employeeIds, (int) $request->client_id, (int) $request->service_point_id)) {
            return back()->with('error', $error);
        }

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($request, $user, &$created, &$skipped) {
            foreach ($request->entries as $entry) {
                $existing = Attendance::where('employee_id', $entry['employee_id'])
                    ->whereDate('attendance_date', $request->attendance_date)
                    ->where('service_point_id', $request->service_point_id)
                    ->first();

                if ($existing && $existing->entry_time !== null) {
                    $skipped++;

                    continue;
                }

                if ($existing) {
                    // Registro existente sin entrada (caso raro, p. ej. creado por incidencia manual
                    // que luego se corrige): se completa en vez de duplicar.
                    $existing->update([
                        'entry_time' => $entry['entry_time'],
                        'status' => $entry['status'],
                        'notes' => $entry['notes'] ?? $existing->notes,
                        'updated_by' => $user->id,
                    ]);
                    $attendance = $existing;
                    $auditAction = 'actualizado';
                } else {
                    $employee = Employee::find($entry['employee_id']);

                    $attendance = Attendance::create([
                        'employee_id' => $entry['employee_id'],
                        'client_id' => $request->client_id,
                        'service_point_id' => $request->service_point_id,
                        'shift_id' => $employee?->shift_id,
                        'supervisor_id' => $user->id,
                        'attendance_date' => $request->attendance_date,
                        'status' => $entry['status'],
                        'entry_time' => $entry['entry_time'],
                        'notes' => $entry['notes'] ?? null,
                        'created_by' => $user->id,
                    ]);
                    $auditAction = 'creado';
                }

                $created++;

                AttendanceAudit::create([
                    'attendance_id' => $attendance->id,
                    'action' => $auditAction,
                    'old_values' => null,
                    'new_values' => $attendance->only(['status', 'entry_time', 'exit_time', 'notes', 'attendance_date']),
                    'reason' => 'Registro de entrada',
                    'changed_by' => $user->id,
                    'created_at' => now(),
                ]);

                AttendanceEvent::create([
                    'attendance_id' => $attendance->id,
                    'event_type' => 'entrada',
                    'event_time' => Carbon::parse($request->attendance_date . ' ' . $entry['entry_time']),
                    'value' => $entry['entry_time'],
                    'created_by' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'notes' => null,
                    'created_at' => now(),
                ]);
            }
        });

        $message = "{$created} entrada(s) registrada(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} ya tenía(n) entrada registrada y se omitieron.";
        }

        return back()->with($created > 0 ? 'success' : 'error', $message);
    }

    public function storeExit(Request $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_point_id' => 'required|exists:service_points,id',
            'attendance_date' => 'required|date|before_or_equal:today',
            'exits' => 'required|array|min:1',
            'exits.*.employee_id' => 'required|exists:employees,id',
            'exits.*.exit_time' => 'required|date_format:H:i',
            'exits.*.notes' => 'nullable|string|max:300',
        ]);

        $user = auth()->user();

        if ($error = $this->checkAssignmentAccess($request, $user)) {
            return back()->with('error', $error);
        }

        if ($error = $this->checkServicePointBelongsToClient($request)) {
            return back()->with('error', $error);
        }

        $employeeIds = collect($request->exits)->pluck('employee_id')->all();

        if ($error = $this->checkEmployeesBelong($employeeIds, (int) $request->client_id, (int) $request->service_point_id)) {
            return back()->with('error', $error);
        }

        $updated = 0;
        $skippedNoEntry = 0;
        $skippedHasExit = 0;

        DB::transaction(function () use ($request, $user, &$updated, &$skippedNoEntry, &$skippedHasExit) {
            foreach ($request->exits as $exit) {
                $attendance = Attendance::where('employee_id', $exit['employee_id'])
                    ->whereDate('attendance_date', $request->attendance_date)
                    ->where('service_point_id', $request->service_point_id)
                    ->first();

                if (! $attendance || $attendance->entry_time === null) {
                    $skippedNoEntry++;

                    continue;
                }

                if ($attendance->exit_time !== null) {
                    $skippedHasExit++;

                    continue;
                }

                $oldValues = $attendance->only(['status', 'entry_time', 'exit_time', 'notes']);

                $attendance->update([
                    'exit_time' => $exit['exit_time'],
                    'notes' => $exit['notes'] ?? $attendance->notes,
                    'updated_by' => $user->id,
                ]);

                $updated++;

                AttendanceAudit::create([
                    'attendance_id' => $attendance->id,
                    'action' => 'actualizado',
                    'old_values' => $oldValues,
                    'new_values' => $attendance->fresh()->only(['status', 'entry_time', 'exit_time', 'notes']),
                    'reason' => 'Registro de salida',
                    'changed_by' => $user->id,
                    'created_at' => now(),
                ]);

                AttendanceEvent::create([
                    'attendance_id' => $attendance->id,
                    'event_type' => 'salida',
                    'event_time' => Carbon::parse($request->attendance_date . ' ' . $exit['exit_time']),
                    'value' => $exit['exit_time'],
                    'created_by' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'notes' => null,
                    'created_at' => now(),
                ]);
            }
        });

        $parts = ["{$updated} salida(s) registrada(s)."];
        if ($skippedNoEntry > 0) {
            $parts[] = "{$skippedNoEntry} sin entrada registrada, se omitieron.";
        }
        if ($skippedHasExit > 0) {
            $parts[] = "{$skippedHasExit} ya tenía(n) salida registrada.";
        }

        return back()->with($updated > 0 ? 'success' : 'error', implode(' ', $parts));
    }

    public function storeIncident(Request $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_point_id' => 'required|exists:service_points,id',
            'attendance_date' => 'required|date|before_or_equal:today',
            'status' => 'required|in:falta,descanso,permiso,incapacidad,retardo',
            'notes' => 'nullable|string|max:300',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:employees,id',
        ]);

        // El motivo es obligatorio (con contenido real) para permiso/incapacidad; el resto lo
        // deja opcional, tal como se definió con el usuario.
        if (in_array($request->status, ['permiso', 'incapacidad'], true) && strlen(trim((string) $request->notes)) < 5) {
            return back()->with('error', 'El motivo es obligatorio (mínimo 5 caracteres) para permiso o incapacidad.');
        }

        $user = auth()->user();

        if ($error = $this->checkAssignmentAccess($request, $user)) {
            return back()->with('error', $error);
        }

        if ($error = $this->checkServicePointBelongsToClient($request)) {
            return back()->with('error', $error);
        }

        if ($error = $this->checkEmployeesBelong($request->employee_ids, (int) $request->client_id, (int) $request->service_point_id)) {
            return back()->with('error', $error);
        }

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($request, $user, &$created, &$skipped) {
            foreach ($request->employee_ids as $employeeId) {
                $existing = Attendance::where('employee_id', $employeeId)
                    ->whereDate('attendance_date', $request->attendance_date)
                    ->where('service_point_id', $request->service_point_id)
                    ->first();

                if ($existing) {
                    $skipped++;

                    continue;
                }

                $employee = Employee::find($employeeId);

                $attendance = Attendance::create([
                    'employee_id' => $employeeId,
                    'client_id' => $request->client_id,
                    'service_point_id' => $request->service_point_id,
                    'shift_id' => $employee?->shift_id,
                    'supervisor_id' => $user->id,
                    'attendance_date' => $request->attendance_date,
                    'status' => $request->status,
                    'notes' => $request->notes,
                    'created_by' => $user->id,
                ]);

                $created++;

                AttendanceAudit::create([
                    'attendance_id' => $attendance->id,
                    'action' => 'creado',
                    'old_values' => null,
                    'new_values' => $attendance->only(['status', 'entry_time', 'exit_time', 'notes', 'attendance_date']),
                    'reason' => $request->notes ?: 'Incidencia registrada por supervisor',
                    'changed_by' => $user->id,
                    'created_at' => now(),
                ]);

                AttendanceEvent::create([
                    'attendance_id' => $attendance->id,
                    'event_type' => 'incidencia',
                    'event_time' => now(),
                    'value' => $attendance->status,
                    'created_by' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'notes' => $request->notes,
                    'created_at' => now(),
                ]);
            }
        });

        $message = "{$created} incidencia(s) registrada(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} ya tenía(n) asistencia registrada ese día y se omitieron.";
        }

        return back()->with($created > 0 ? 'success' : 'error', $message);
    }

    public function storeManual(Request $request): RedirectResponse
    {
        $this->authorize('manualCapture', Attendance::class);

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_point_id' => 'required|exists:service_points,id',
            'attendance_date' => 'required|date|before_or_equal:today',
            'records' => 'required|array|min:1',
            'records.*.employee_id' => 'required|exists:employees,id',
            'records.*.status' => 'required|in:presente,falta,descanso,permiso,incapacidad,retardo',
            'records.*.entry_time' => 'nullable|date_format:H:i',
            'records.*.exit_time' => 'nullable|date_format:H:i',
            'records.*.notes' => 'nullable|string|max:300',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        if ($error = $this->checkAssignmentAccess($request, $user)) {
            return back()->with('error', $error);
        }

        if ($error = $this->checkServicePointBelongsToClient($request)) {
            return back()->with('error', $error);
        }

        $employeeIds = collect($request->records)->pluck('employee_id')->all();

        if ($error = $this->checkEmployeesBelong($employeeIds, (int) $request->client_id, (int) $request->service_point_id)) {
            return back()->with('error', $error);
        }

        $anyExisting = Attendance::whereIn('employee_id', $employeeIds)
            ->whereDate('attendance_date', $request->attendance_date)
            ->where('service_point_id', $request->service_point_id)
            ->exists();

        if ($anyExisting && strlen(trim((string) $request->reason)) < 10) {
            return back()->with('error', 'Debes indicar un motivo (mínimo 10 caracteres): uno o más colaboradores ya tienen asistencia registrada ese día.');
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($request, $user, &$created, &$updated) {
            foreach ($request->records as $record) {
                $existing = Attendance::where('employee_id', $record['employee_id'])
                    ->whereDate('attendance_date', $request->attendance_date)
                    ->where('service_point_id', $request->service_point_id)
                    ->first();

                $payload = [
                    'status' => $record['status'],
                    'entry_time' => $record['entry_time'] ?? null,
                    'exit_time' => $record['exit_time'] ?? null,
                    'notes' => $record['notes'] ?? null,
                ];

                if ($existing) {
                    $oldValues = $existing->only(['status', 'entry_time', 'exit_time', 'notes']);

                    $existing->update([...$payload, 'updated_by' => $user->id]);

                    $updated++;

                    AttendanceAudit::create([
                        'attendance_id' => $existing->id,
                        'action' => 'corregido',
                        'old_values' => $oldValues,
                        'new_values' => $existing->fresh()->only(['status', 'entry_time', 'exit_time', 'notes']),
                        'reason' => $request->reason,
                        'changed_by' => $user->id,
                        'created_at' => now(),
                    ]);
                } else {
                    $employee = Employee::find($record['employee_id']);

                    $attendance = Attendance::create([
                        'employee_id' => $record['employee_id'],
                        'client_id' => $request->client_id,
                        'service_point_id' => $request->service_point_id,
                        'shift_id' => $employee?->shift_id,
                        'supervisor_id' => $user->id,
                        'attendance_date' => $request->attendance_date,
                        ...$payload,
                        'created_by' => $user->id,
                    ]);

                    $created++;

                    AttendanceAudit::create([
                        'attendance_id' => $attendance->id,
                        'action' => 'creado',
                        'old_values' => null,
                        'new_values' => $attendance->only(['status', 'entry_time', 'exit_time', 'notes', 'attendance_date']),
                        'reason' => $request->reason ?: 'Captura manual completa',
                        'changed_by' => $user->id,
                        'created_at' => now(),
                    ]);
                }
            }
        });

        return back()->with('success', "{$created} registrada(s), {$updated} corregida(s).");
    }
}
