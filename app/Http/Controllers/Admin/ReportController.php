<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', \App\Models\AttendanceAudit::class);

        $attendances = collect();
        $summary = null;

        if ($request->date_from && $request->date_to) {
            $query = Attendance::query()
                ->with([
                    'employee:id,employee_number,name,last_name',
                    'client:id,name',
                    'servicePoint:id,name',
                    'supervisor:id,name',
                ])
                ->whereBetween('attendance_date', [$request->date_from, $request->date_to])
                ->when($request->client_id, fn ($q, $c) => $q->where('client_id', $c))
                ->when($request->service_point_id, fn ($q, $sp) => $q->where('service_point_id', $sp))
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->orderBy('attendance_date')
                ->orderBy('employee_id');

            $attendances = $query->paginate(50)->withQueryString();

            $summary = Attendance::whereBetween('attendance_date', [$request->date_from, $request->date_to])
                ->when($request->client_id, fn ($q, $c) => $q->where('client_id', $c))
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(status = "presente") as presente,
                    SUM(status = "falta") as falta,
                    SUM(status = "retardo") as retardo,
                    SUM(status = "descanso") as descanso,
                    SUM(status = "permiso") as permiso,
                    SUM(status = "incapacidad") as incapacidad
                ')
                ->first();
        }

        return Inertia::render('admin/Reports/Index', [
            'attendances' => $attendances,
            'summary' => $summary,
            'clients' => Client::where('status', 'activo')->orderBy('name')->get(['id', 'name']),
            'servicePoints' => $request->client_id
                ? ServicePoint::where('client_id', $request->client_id)->orderBy('name')->get(['id', 'name'])
                : [],
            'filters' => $request->only(['date_from', 'date_to', 'client_id', 'service_point_id', 'status']),
        ]);
    }

    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('create', \App\Models\AttendanceAudit::class);

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        return Excel::download(
            new \App\Exports\AttendanceExport($request->all()),
            'reporte-asistencias-' . $request->date_from . '-' . $request->date_to . '.xlsx'
        );
    }

    public function exportPdf(Request $request): HttpResponse
    {
        $this->authorize('create', \App\Models\AttendanceAudit::class);

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $attendances = Attendance::with([
            'employee:id,employee_number,name,last_name',
            'client:id,name',
            'servicePoint:id,name',
            'supervisor:id,name',
        ])
            ->whereBetween('attendance_date', [$request->date_from, $request->date_to])
            ->when($request->client_id, fn ($q, $c) => $q->where('client_id', $c))
            ->orderBy('attendance_date')
            ->limit(1000)
            ->get();

        $pdf = Pdf::loadView('reports.attendances', [
            'attendances' => $attendances,
            'filters' => $request->all(),
            'generated_at' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('reporte-asistencias.pdf');
    }
}
