<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyAttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (! $employee) {
            return Inertia::render('employee/MyAttendances', [
                'attendances' => null,
                'employee' => null,
                'stats' => null,
                'filters' => [],
            ]);
        }

        $dateFrom = $request->date_from ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?: Carbon::now()->endOfMonth()->format('Y-m-d');
        $status = $request->status;

        $baseQuery = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', '>=', $dateFrom)
            ->whereDate('attendance_date', '<=', $dateTo);

        $attendances = (clone $baseQuery)
            ->when($status, fn ($q, $v) => $q->where('status', $v))
            ->with(['servicePoint:id,name'])
            ->orderByDesc('attendance_date')
            ->get();

        $stats = (clone $baseQuery)->selectRaw('
                COUNT(*) as total,
                SUM(status = "presente") as present,
                SUM(status = "falta") as absent,
                SUM(status = "retardo") as late,
                SUM(status IN ("descanso", "permiso", "incapacidad")) as rest
            ')
            ->first();

        return Inertia::render('employee/MyAttendances', [
            'attendances' => $attendances,
            'employee' => $employee->load(['client:id,name', 'shift:id,name,start_time,end_time']),
            'stats' => [
                'present' => (int) $stats->present,
                'absent' => (int) $stats->absent,
                'late' => (int) $stats->late,
                'rest' => (int) $stats->rest,
                'total' => (int) $stats->total,
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $status,
            ],
        ]);
    }
}
