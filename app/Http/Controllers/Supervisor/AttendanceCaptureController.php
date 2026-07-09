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

        $assignedClientIds = $user->supervisorAssignments()->pluck('client_id');
        $assignedSPIds = $user->supervisorAssignments()->whereNotNull('service_point_id')->pluck('service_point_id');

        $clients = Client::whereIn('id', $assignedClientIds)->where('status', 'activo')->orderBy('name')->get(['id', 'name']);

        $servicePoints = collect();
        $employees = collect();
        $existingAttendances = collect();

        if ($request->client_id && $assignedClientIds->contains($request->client_id)) {
            $servicePoints = ServicePoint::where('client_id', $request->client_id)
                ->where('status', 'activo')
                ->when($assignedSPIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $assignedSPIds))
                ->orderBy('name')
                ->get(['id', 'name']);

            if ($request->service_point_id) {
                $date = $request->date ?? Carbon::today()->format('Y-m-d');

                $employees = Employee::where('service_point_id', $request->service_point_id)
                    ->where('status', 'activo')
                    ->orderBy('last_name')
                    ->get(['id', 'employee_number', 'name', 'last_name', 'second_last_name']);

                $existingAttendances = Attendance::where('service_point_id', $request->service_point_id)
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
            'alreadySaved' => $existingAttendances->isNotEmpty(),
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
        $supervisorAssignment = $user->supervisorAssignments()
            ->where('client_id', $request->client_id)
            ->first();

        if (! $supervisorAssignment) {
            return back()->with('error', 'No tienes asignación para esta empresa.');
        }

        DB::transaction(function () use ($request, $user) {
            foreach ($request->records as $record) {
                $existing = Attendance::where('employee_id', $record['employee_id'])
                    ->whereDate('attendance_date', $request->attendance_date)
                    ->where('service_point_id', $request->service_point_id)
                    ->first();

                if ($existing) {
                    continue; // Supervisor cannot overwrite existing records
                }

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
            }
        });

        return back()->with('success', 'Asistencias registradas correctamente.');
    }
}
