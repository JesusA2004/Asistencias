<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceAudit;
use App\Models\AttendanceEvent;
use App\Models\AttendanceTermsAcceptance;
use App\Models\Employee;
use App\Services\AttendancePhotoService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SelfAttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! setting('allow_employee_self_attendance', false)) {
            return Inertia::render('employee/SelfAttendance', [
                'enabled' => false,
                'employee' => null,
                'attendance' => null,
                'settings' => null,
                'needsWarningAcceptance' => false,
            ]);
        }

        $employee = $user->employee;

        if (! $employee) {
            return Inertia::render('employee/SelfAttendance', [
                'enabled' => true,
                'employee' => null,
                'attendance' => null,
                'settings' => null,
                'needsWarningAcceptance' => false,
            ]);
        }

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', Carbon::today())
            ->first();

        $requiresPhoto = setting('employee_self_attendance_requires_photo', true);
        $warningVersion = (int) setting('attendance_warning_version', 1);

        $needsWarningAcceptance = $requiresPhoto && ! AttendanceTermsAcceptance::where('user_id', $user->id)
            ->where('context', 'colaborador')
            ->where('warning_version', $warningVersion)
            ->whereDate('accepted_at', Carbon::today())
            ->exists();

        return Inertia::render('employee/SelfAttendance', [
            'enabled' => true,
            'employee' => $employee->load(['client:id,name', 'servicePoint:id,name', 'shift:id,name,start_time,end_time,tolerance_minutes']),
            'attendance' => $attendance,
            'settings' => [
                'allow_exit' => setting('employee_self_attendance_allow_exit', true),
                'requires_photo' => $requiresPhoto,
                'requires_location' => setting('employee_self_attendance_requires_location', false),
                'warning_text' => setting('attendance_warning_text', ''),
                'warning_version' => $warningVersion,
            ],
            'needsWarningAcceptance' => $needsWarningAcceptance,
        ]);
    }

    private function suggestStatus(CarbonInterface $now, ?Employee $employee): string
    {
        $shift = $employee?->shift;

        if (! $shift) {
            return 'presente';
        }

        $limit = Carbon::parse($shift->start_time)->addMinutes($shift->tolerance_minutes ?? 0);
        $entry = Carbon::createFromTimeString($now->format('H:i:s'));

        return $entry->format('H:i') > $limit->format('H:i') ? 'retardo' : 'presente';
    }

    public function storeEntry(Request $request, AttendancePhotoService $photoService): RedirectResponse
    {
        $user = $request->user();

        if (! setting('allow_employee_self_attendance', false)) {
            return back()->with('error', 'El registro de asistencia por colaborador está deshabilitado.');
        }

        $employee = $user->employee?->load('shift');

        if (! $employee) {
            return back()->with('error', 'Tu cuenta no está vinculada a un colaborador.');
        }

        $requiresPhoto = setting('employee_self_attendance_requires_photo', true);
        $requiresLocation = setting('employee_self_attendance_requires_location', false);

        $request->validate([
            'photo' => ($requiresPhoto ? 'required' : 'nullable').'|image|mimes:jpeg,jpg,png|max:5120',
            'latitude' => ($requiresLocation ? 'required' : 'nullable').'|numeric|between:-90,90',
            'longitude' => ($requiresLocation ? 'required' : 'nullable').'|numeric|between:-180,180',
            'device_time' => 'nullable|date',
        ]);

        if (! $employee->client_id || ! $employee->service_point_id) {
            return back()->with('error', 'Tu colaborador no tiene empresa o punto de servicio asignado.');
        }

        $now = now();

        $existing = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $now->toDateString())
            ->first();

        if ($existing && $existing->entry_time !== null) {
            return back()->with('error', 'Ya registraste tu entrada hoy.');
        }

        $status = $this->suggestStatus($now, $employee);

        $attendance = DB::transaction(function () use ($existing, $employee, $user, $now, $status, $request, $photoService) {
            if ($existing) {
                $oldValues = $existing->only(['status', 'entry_time', 'exit_time', 'notes']);
                $existing->update([
                    'entry_time' => $now->format('H:i'),
                    'status' => $status,
                    'updated_by' => $user->id,
                ]);
                $attendance = $existing;

                AttendanceAudit::create([
                    'attendance_id' => $attendance->id,
                    'action' => 'actualizado',
                    'old_values' => $oldValues,
                    'new_values' => $attendance->fresh()->only(['status', 'entry_time', 'exit_time', 'notes']),
                    'reason' => 'Autorregistro de entrada por colaborador',
                    'changed_by' => $user->id,
                    'created_at' => now(),
                ]);
            } else {
                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'client_id' => $employee->client_id,
                    'service_point_id' => $employee->service_point_id,
                    'shift_id' => $employee->shift_id,
                    'supervisor_id' => $user->id,
                    'attendance_date' => $now->toDateString(),
                    'status' => $status,
                    'entry_time' => $now->format('H:i'),
                    'created_by' => $user->id,
                ]);

                AttendanceAudit::create([
                    'attendance_id' => $attendance->id,
                    'action' => 'creado',
                    'old_values' => null,
                    'new_values' => $attendance->only(['status', 'entry_time', 'exit_time', 'notes', 'attendance_date']),
                    'reason' => 'Autorregistro de entrada por colaborador',
                    'changed_by' => $user->id,
                    'created_at' => now(),
                ]);
            }

            $event = AttendanceEvent::create([
                'attendance_id' => $attendance->id,
                'event_type' => 'entrada',
                'event_time' => $now,
                'value' => $now->format('H:i'),
                'created_by' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'notes' => null,
                'created_at' => now(),
            ]);

            if ($request->hasFile('photo')) {
                $photoService->store(
                    $request->file('photo'),
                    $attendance,
                    $event,
                    $employee,
                    $user,
                    'entrada',
                    'colaborador',
                    $request,
                    $request->float('latitude') ?: null,
                    $request->float('longitude') ?: null,
                    $request->filled('device_time') ? Carbon::parse($request->input('device_time')) : null,
                );
            }

            return $attendance;
        });

        return back()->with('success', 'Entrada registrada correctamente.');
    }

    public function storeExit(Request $request, AttendancePhotoService $photoService): RedirectResponse
    {
        $user = $request->user();

        if (! setting('allow_employee_self_attendance', false)) {
            return back()->with('error', 'El registro de asistencia por colaborador está deshabilitado.');
        }

        if (! setting('employee_self_attendance_allow_exit', true)) {
            return back()->with('error', 'El registro de salida no está habilitado.');
        }

        $employee = $user->employee;

        if (! $employee) {
            return back()->with('error', 'Tu cuenta no está vinculada a un colaborador.');
        }

        $requiresPhoto = setting('employee_self_attendance_requires_photo', true);
        $requiresLocation = setting('employee_self_attendance_requires_location', false);

        $request->validate([
            'photo' => ($requiresPhoto ? 'required' : 'nullable').'|image|mimes:jpeg,jpg,png|max:5120',
            'latitude' => ($requiresLocation ? 'required' : 'nullable').'|numeric|between:-90,90',
            'longitude' => ($requiresLocation ? 'required' : 'nullable').'|numeric|between:-180,180',
            'device_time' => 'nullable|date',
        ]);

        $now = now();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $now->toDateString())
            ->first();

        if (! $attendance || $attendance->entry_time === null) {
            return back()->with('error', 'No has registrado tu entrada hoy.');
        }

        if ($attendance->exit_time !== null) {
            return back()->with('error', 'Ya registraste tu salida hoy.');
        }

        DB::transaction(function () use ($attendance, $user, $now, $request, $employee, $photoService) {
            $oldValues = $attendance->only(['status', 'entry_time', 'exit_time', 'notes']);

            $attendance->update([
                'exit_time' => $now->format('H:i'),
                'updated_by' => $user->id,
            ]);

            AttendanceAudit::create([
                'attendance_id' => $attendance->id,
                'action' => 'actualizado',
                'old_values' => $oldValues,
                'new_values' => $attendance->fresh()->only(['status', 'entry_time', 'exit_time', 'notes']),
                'reason' => 'Autorregistro de salida por colaborador',
                'changed_by' => $user->id,
                'created_at' => now(),
            ]);

            $event = AttendanceEvent::create([
                'attendance_id' => $attendance->id,
                'event_type' => 'salida',
                'event_time' => $now,
                'value' => $now->format('H:i'),
                'created_by' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'notes' => null,
                'created_at' => now(),
            ]);

            if ($request->hasFile('photo')) {
                $photoService->store(
                    $request->file('photo'),
                    $attendance,
                    $event,
                    $employee,
                    $user,
                    'salida',
                    'colaborador',
                    $request,
                    $request->float('latitude') ?: null,
                    $request->float('longitude') ?: null,
                    $request->filled('device_time') ? Carbon::parse($request->input('device_time')) : null,
                );
            }
        });

        return back()->with('success', 'Salida registrada correctamente.');
    }
}
