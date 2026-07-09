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

        $month = $request->month ?? Carbon::now()->format('Y-m');
        [$year, $monthNum] = explode('-', $month);

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $monthNum)
            ->with(['servicePoint:id,name', 'client:id,name'])
            ->orderBy('attendance_date')
            ->get();

        return Inertia::render('employee/MyAttendances', [
            'attendances' => $attendances,
            'employee' => $employee->load(['client:id,name', 'shift:id,name,start_time,end_time']),
            'stats' => [
                'present' => $attendances->where('status', 'presente')->count(),
                'absent' => $attendances->where('status', 'falta')->count(),
                'late' => $attendances->where('status', 'retardo')->count(),
                'rest' => $attendances->whereIn('status', ['descanso', 'permiso', 'incapacidad'])->count(),
                'total' => $attendances->count(),
            ],
            'filters' => ['month' => $month],
        ]);
    }
}
