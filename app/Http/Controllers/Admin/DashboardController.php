<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
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

        $monthStart = $today->copy()->startOfMonth();

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

        // Absences by client (today)
        $absentByClient = Attendance::select('client_id', DB::raw('COUNT(*) as total'))
            ->where('status', 'falta')
            ->whereDate('attendance_date', $today)
            ->with('client:id,name')
            ->groupBy('client_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Distribución de estados (hoy)
        $statusCounts = Attendance::whereDate('attendance_date', $today)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusDistribution = collect(['presente', 'falta', 'retardo', 'descanso', 'permiso', 'incapacidad'])
            ->map(fn ($status) => [
                'status' => $status,
                'total' => (int) ($statusCounts[$status] ?? 0),
            ])
            ->values();

        // Cumplimiento por ubicación (hoy) — puntos con al menos un colaborador activo
        $complianceByLocation = ServicePoint::query()
            ->where('service_points.status', 'activo')
            ->leftJoin('employees', function ($join) {
                $join->on('employees.service_point_id', '=', 'service_points.id')
                    ->where('employees.status', 'activo');
            })
            ->leftJoin('attendances', function ($join) use ($today) {
                $join->on('attendances.employee_id', '=', 'employees.id')
                    ->whereDate('attendances.attendance_date', $today)
                    ->where('attendances.status', 'presente');
            })
            ->groupBy('service_points.id', 'service_points.name')
            ->selectRaw('service_points.name as name, COUNT(DISTINCT employees.id) as total_employees, COUNT(DISTINCT attendances.employee_id) as present')
            ->havingRaw('COUNT(DISTINCT employees.id) > 0')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'total_employees' => (int) $row->total_employees,
                'compliance' => $row->total_employees > 0 ? round(($row->present / $row->total_employees) * 100, 1) : 0,
            ])
            ->sortBy('compliance')
            ->values()
            ->take(10);

        // Retardos por supervisor (mes actual)
        $latesBySupervisor = Attendance::select('supervisor_id', DB::raw('COUNT(*) as total'))
            ->where('status', 'retardo')
            ->whereBetween('attendance_date', [$monthStart, $today])
            ->whereNotNull('supervisor_id')
            ->with('supervisor:id,name')
            ->groupBy('supervisor_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Top 10 puntos con más incidencias (falta + retardo) del mes
        $topIncidentPoints = Attendance::select('service_point_id', DB::raw("SUM(CASE WHEN status IN ('falta', 'retardo') THEN 1 ELSE 0 END) as incidents"))
            ->whereBetween('attendance_date', [$monthStart, $today])
            ->whereNotNull('service_point_id')
            ->with('servicePoint:id,name')
            ->groupBy('service_point_id')
            ->havingRaw("SUM(CASE WHEN status IN ('falta', 'retardo') THEN 1 ELSE 0 END) > 0")
            ->orderByDesc('incidents')
            ->limit(10)
            ->get();

        // Capturas pendientes por empresa (hoy)
        $pendingCapturesByClient = Client::where('status', 'activo')
            ->withCount(['employees' => fn ($q) => $q->where('status', 'activo')])
            ->get()
            ->map(function ($client) use ($today) {
                $captured = Attendance::where('client_id', $client->id)->whereDate('attendance_date', $today)->count();

                return [
                    'name' => $client->name,
                    'pending' => max(0, $client->employees_count - $captured),
                    'total' => $client->employees_count,
                ];
            })
            ->filter(fn ($c) => $c['total'] > 0 && $c['pending'] > 0)
            ->sortByDesc('pending')
            ->values()
            ->take(10);

        // Comparativo semanal (esta semana vs semana anterior)
        $weeklyStats = function (Carbon $start, Carbon $end) {
            return Attendance::whereBetween('attendance_date', [$start, $end])
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'presente' THEN 1 ELSE 0 END) as presente,
                    SUM(CASE WHEN status = 'falta' THEN 1 ELSE 0 END) as falta,
                    SUM(CASE WHEN status = 'retardo' THEN 1 ELSE 0 END) as retardo
                ")
                ->first();
        };

        $thisWeekStart = $today->copy()->startOfWeek();
        $lastWeekStart = $thisWeekStart->copy()->subWeek();
        $lastWeekEnd = $thisWeekStart->copy()->subDay();

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
            'status_distribution' => $statusDistribution,
            'compliance_by_location' => $complianceByLocation,
            'lates_by_supervisor' => $latesBySupervisor,
            'top_incident_points' => $topIncidentPoints,
            'pending_captures_by_client' => $pendingCapturesByClient,
            'weekly_comparison' => [
                'current' => $weeklyStats($thisWeekStart, $today),
                'previous' => $weeklyStats($lastWeekStart, $lastWeekEnd),
            ],
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

        $assignedLocations = $user->supervisorAssignments()
            ->with(['client:id,name', 'servicePoint:id,name'])
            ->get()
            ->map(fn ($a) => [
                'client' => $a->client?->name,
                'service_point' => $a->servicePoint?->name ?? 'Toda la empresa',
            ]);

        $recentCaptures = Attendance::where('supervisor_id', $user->id)
            ->with(['employee:id,name,last_name', 'servicePoint:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'employee_id', 'service_point_id', 'status', 'attendance_date', 'created_at']);

        $last7Days = Attendance::where('supervisor_id', $user->id)
            ->select(
                DB::raw('DATE(attendance_date) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'presente' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN status = 'falta' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN status = 'retardo' THEN 1 ELSE 0 END) as late")
            )
            ->where('attendance_date', '>=', $today->copy()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

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
            'assigned_locations_list' => $assignedLocations,
            'recent_captures' => $recentCaptures,
            'chart_7_days' => $last7Days,
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
            'recent_attendances' => $monthAttendances->sortByDesc('attendance_date')->take(10)->values(),
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
