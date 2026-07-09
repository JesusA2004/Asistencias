<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\SupervisorAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $today = Carbon::today();

        if ($user->hasRole('administrador')) {
            return $this->adminDashboard($today);
        }

        if ($user->hasRole('supervisor')) {
            return $this->supervisorDashboard($user, $today);
        }

        if ($user->hasRole('colaborador')) {
            return $this->employeeDashboard($user, $today);
        }

        return $this->rhDashboard($today);
    }

    private function adminDashboard(Carbon $today): Response
    {
        $todayAttendances = Attendance::whereDate('attendance_date', $today);

        $totalEmployees = Employee::where('status', 'activo')->count();
        $todayPresent = (clone $todayAttendances)->where('status', 'presente')->count();
        $todayAbsent = (clone $todayAttendances)->where('status', 'falta')->count();
        $todayLate = (clone $todayAttendances)->where('status', 'retardo')->count();
        $todayTotal = (clone $todayAttendances)->count();
        $compliance = $totalEmployees > 0 ? round(($todayPresent / $totalEmployees) * 100, 1) : 0;

        // Last 30 days chart
        $last30Days = Attendance::select(
            DB::raw('DATE(attendance_date) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw("SUM(CASE WHEN status = 'presente' THEN 1 ELSE 0 END) as present"),
            DB::raw("SUM(CASE WHEN status = 'falta' THEN 1 ELSE 0 END) as absent"),
            DB::raw("SUM(CASE WHEN status = 'retardo' THEN 1 ELSE 0 END) as late")
        )
            ->where('attendance_date', '>=', Carbon::today()->subDays(29))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Absences by client
        $absentByClient = Attendance::select('client_id', DB::raw('COUNT(*) as total'))
            ->where('status', 'falta')
            ->whereDate('attendance_date', $today)
            ->with('client:id,name')
            ->groupBy('client_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'active_employees' => $totalEmployees,
                'today_present' => $todayPresent,
                'today_absent' => $todayAbsent,
                'today_late' => $todayLate,
                'compliance_percentage' => $compliance,
                'active_clients' => Client::where('status', 'activo')->count(),
                'pending_captures' => max(0, $totalEmployees - $todayTotal),
            ],
            'chart_daily' => $last30Days,
            'chart_absents_by_client' => $absentByClient,
        ]);
    }

    private function supervisorDashboard($user, Carbon $today): Response
    {
        $assignedClients = $user->supervisorAssignments()->pluck('client_id');
        $assignedSPs = $user->supervisorAssignments()->whereNotNull('service_point_id')->pluck('service_point_id');

        $capturedToday = Attendance::where('supervisor_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->count();

        $totalEmployees = Employee::where('status', 'activo')
            ->whereIn('client_id', $assignedClients)
            ->count();

        return Inertia::render('supervisor/Dashboard', [
            'stats' => [
                'assigned_locations' => $user->supervisorAssignments()->count(),
                'pending_captures_today' => max(0, $totalEmployees - $capturedToday),
                'captured_today' => $capturedToday,
                'absences_today' => Attendance::where('supervisor_id', $user->id)
                    ->whereDate('attendance_date', $today)
                    ->where('status', 'falta')->count(),
                'lates_today' => Attendance::where('supervisor_id', $user->id)
                    ->whereDate('attendance_date', $today)
                    ->where('status', 'retardo')->count(),
            ],
        ]);
    }

    private function employeeDashboard($user, Carbon $today): Response
    {
        $employee = $user->employee;

        if (! $employee) {
            return Inertia::render('employee/Dashboard', ['stats' => null]);
        }

        $monthStart = $today->copy()->startOfMonth();
        $monthAttendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$monthStart, $today])
            ->get();

        return Inertia::render('employee/Dashboard', [
            'stats' => [
                'month_present' => $monthAttendances->where('status', 'presente')->count(),
                'month_absent' => $monthAttendances->where('status', 'falta')->count(),
                'month_late' => $monthAttendances->where('status', 'retardo')->count(),
                'month_total' => $monthAttendances->count(),
            ],
            'recent_attendances' => $monthAttendances->take(10)->values(),
        ]);
    }

    private function rhDashboard(Carbon $today): Response
    {
        return Inertia::render('rh/Dashboard', [
            'stats' => [
                'total_employees' => Employee::where('status', 'activo')->count(),
                'today_present' => Attendance::whereDate('attendance_date', $today)->where('status', 'presente')->count(),
                'active_clients' => Client::where('status', 'activo')->count(),
            ],
        ]);
    }
}
