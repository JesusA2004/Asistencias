<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\Shift;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Aplica todos los filtros del reporte a una consulta de asistencias.
     * Se usa tanto en la vista como en la exportación a Excel y PDF, para que
     * los tres siempre respeten exactamente los mismos filtros.
     */
    public static function applyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('attendance_date', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->whereDate('attendance_date', '<=', $v))
            ->when($request->client_id, fn ($q, $v) => $q->where('client_id', $v))
            ->when($request->service_point_id, fn ($q, $v) => $q->where('service_point_id', $v))
            ->when($request->employee_id, fn ($q, $v) => $q->where('employee_id', $v))
            ->when($request->supervisor_id, fn ($q, $v) => $q->where('supervisor_id', $v))
            ->when($request->shift_id, fn ($q, $v) => $q->where('shift_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->boolean('only_incidents'), fn ($q) => $q->whereIn('status', ['falta', 'retardo']))
            ->when($request->boolean('only_present'), fn ($q) => $q->where('status', 'presente'))
            ->when($request->search, fn ($q, $v) => $q->whereHas('employee', fn ($eq) => $eq
                ->where('employee_number', 'like', "%{$v}%")
                ->orWhere('name', 'like', "%{$v}%")
                ->orWhere('last_name', 'like', "%{$v}%")
                ->orWhere('second_last_name', 'like', "%{$v}%")));
    }

    private function filterKeys(): array
    {
        return [
            'date_from', 'date_to', 'client_id', 'service_point_id', 'employee_id',
            'supervisor_id', 'shift_id', 'status', 'search', 'only_incidents', 'only_present',
        ];
    }

    public function index(Request $request): Response
    {
        abort_unless(auth()->user()->can('Ver reportes'), 403);

        $attendances = null;
        $summary = null;

        if ($request->date_from && $request->date_to) {
            $query = self::applyFilters(
                Attendance::query()->with([
                    'employee:id,employee_number,name,last_name',
                    'client:id,name',
                    'servicePoint:id,name',
                    'supervisor:id,name',
                ]),
                $request
            )->orderBy('attendance_date')->orderBy('employee_id');

            $attendances = $query->paginate(50)->withQueryString();

            $summary = self::applyFilters(Attendance::query(), $request)
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
            'servicePoints' => ServicePoint::where('status', 'activo')->orderBy('name')->get(['id', 'client_id', 'name']),
            'employees' => Employee::orderBy('name')->get(['id', 'client_id', 'employee_number', 'name', 'last_name']),
            'supervisors' => User::role('supervisor')->orderBy('name')->get(['id', 'name']),
            'shifts' => Shift::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only($this->filterKeys()),
        ]);
    }

    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless(auth()->user()->can('Exportar reportes'), 403);

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        return Excel::download(
            new \App\Exports\AttendanceExport($request),
            'reporte-asistencias-' . $request->date_from . '-' . $request->date_to . '.xlsx'
        );
    }

    public function exportPdf(Request $request): HttpResponse
    {
        abort_unless(auth()->user()->can('Exportar reportes'), 403);

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $attendances = self::applyFilters(
            Attendance::query()->with([
                'employee:id,employee_number,name,last_name',
                'client:id,name',
                'servicePoint:id,name',
                'supervisor:id,name',
            ]),
            $request
        )
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
