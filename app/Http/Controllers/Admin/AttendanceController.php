<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceAudit;
use App\Models\Client;
use App\Models\ServicePoint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Attendance::class);

        $query = Attendance::query()
            ->with([
                'employee:id,employee_number,name,last_name',
                'client:id,name',
                'servicePoint:id,name',
                'supervisor:id,name',
            ])
            ->when($request->date, fn ($q, $d) => $q->whereDate('attendance_date', $d))
            ->when($request->client_id, fn ($q, $c) => $q->where('client_id', $c))
            ->when($request->service_point_id, fn ($q, $sp) => $q->where('service_point_id', $sp))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->supervisor_id, fn ($q, $s) => $q->where('supervisor_id', $s))
            ->when($request->employee_search, fn ($q, $s) => $q->whereHas('employee', fn ($eq) => $eq
                ->where('name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%")
                ->orWhere('employee_number', 'like', "%{$s}%")))
            ->orderByDesc('attendance_date')
            ->orderBy('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/Attendances/Index', [
            'attendances' => $query,
            'clients' => Client::where('status', 'activo')->orderBy('name')->get(['id', 'name']),
            'servicePoints' => $request->client_id
                ? ServicePoint::where('client_id', $request->client_id)->orderBy('name')->get(['id', 'name'])
                : [],
            'filters' => $request->only(['date', 'client_id', 'service_point_id', 'status', 'supervisor_id', 'employee_search']),
        ]);
    }

    public function correct(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('update', $attendance);

        $request->validate([
            'status' => 'required|in:presente,falta,descanso,permiso,incapacidad,retardo',
            'entry_time' => 'nullable|date_format:H:i',
            'exit_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
            'reason' => 'required|string|min:10|max:500',
        ]);

        $oldValues = $attendance->only(['status', 'entry_time', 'exit_time', 'notes']);

        $attendance->update([
            'status' => $request->status,
            'entry_time' => $request->entry_time,
            'exit_time' => $request->exit_time,
            'notes' => $request->notes,
            'updated_by' => auth()->id(),
        ]);

        AttendanceAudit::create([
            'attendance_id' => $attendance->id,
            'action' => 'corregido',
            'old_values' => $oldValues,
            'new_values' => $attendance->fresh()->only(['status', 'entry_time', 'exit_time', 'notes']),
            'reason' => $request->reason,
            'changed_by' => auth()->id(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Asistencia corregida y auditada correctamente.');
    }

    public function destroy(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('delete', $attendance);

        $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);

        AttendanceAudit::create([
            'attendance_id' => $attendance->id,
            'action' => 'eliminado',
            'old_values' => $attendance->only(['status', 'entry_time', 'exit_time', 'notes', 'attendance_date']),
            'new_values' => null,
            'reason' => $request->reason,
            'changed_by' => auth()->id(),
            'created_at' => now(),
        ]);

        $attendance->update(['deleted_by' => auth()->id()]);
        $attendance->delete();

        return back()->with('success', 'Asistencia eliminada correctamente.');
    }
}
