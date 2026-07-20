<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\ImportEmployeeRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Imports\EmployeeImport;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\Shift;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        $recentSince = now()->subDays(30);

        $employees = Employee::query()
            ->with(['client:id,name', 'servicePoint:id,name', 'shift:id,name'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%")
                ->orWhere('employee_number', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")))
            ->when($request->client_id, fn ($q, $c) => $q->where('client_id', $c))
            ->when($request->service_point_id, fn ($q, $sp) => $q->where('service_point_id', $sp))
            ->when($request->shift_id, fn ($q, $sh) => $q->where('shift_id', $sh))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->supervisor_id, fn ($q, $supervisorId) => $q->whereIn('id', $this->employeeIdsForSupervisor((int) $supervisorId)))
            ->when($request->has_user === 'con', fn ($q) => $q->whereNotNull('user_id'))
            ->when($request->has_user === 'sin', fn ($q) => $q->whereNull('user_id'))
            ->when($request->has_recent_attendance === 'con', fn ($q) => $q->whereHas('attendances', fn ($q) => $q->where('attendance_date', '>=', $recentSince)))
            ->when($request->has_recent_attendance === 'sin', fn ($q) => $q->whereDoesntHave('attendances', fn ($q) => $q->where('attendance_date', '>=', $recentSince)))
            ->orderBy('last_name')
            ->paginate(25)
            ->withQueryString();

        $supervisors = User::role('supervisor')->orderBy('name')->get(['id', 'name']);

        $countsByClient = Employee::query()
            ->join('clients', 'clients.id', '=', 'employees.client_id')
            ->selectRaw('clients.id as client_id, clients.name as client_name, count(*) as total')
            ->groupBy('clients.id', 'clients.name')
            ->orderBy('clients.name')
            ->get();

        $countsByServicePoint = Employee::query()
            ->join('service_points', 'service_points.id', '=', 'employees.service_point_id')
            ->selectRaw('service_points.id as service_point_id, service_points.name as service_point_name, count(*) as total')
            ->groupBy('service_points.id', 'service_points.name')
            ->orderBy('service_points.name')
            ->get();

        return Inertia::render('admin/Employees/Index', [
            'employees' => $employees,
            'clients' => Client::where('status', 'activo')->orderBy('name')->get(['id', 'name']),
            'servicePoints' => ServicePoint::where('status', 'activo')->orderBy('name')->get(['id', 'name', 'client_id']),
            'shifts' => Shift::where('status', 'activo')->orderBy('name')->get(['id', 'name']),
            'supervisors' => $supervisors,
            'countsByClient' => $countsByClient,
            'countsByServicePoint' => $countsByServicePoint,
            'filters' => $request->only([
                'search', 'client_id', 'service_point_id', 'shift_id', 'status',
                'supervisor_id', 'has_user', 'has_recent_attendance',
            ]),
        ]);
    }

    /**
     * IDs de colaboradores dentro del alcance de un supervisor (asignación por empresa
     * completa o por punto específico).
     *
     * @return array<int>
     */
    private function employeeIdsForSupervisor(int $supervisorId): array
    {
        $assignments = SupervisorAssignment::where('supervisor_user_id', $supervisorId)->get();

        $clientWideIds = $assignments->whereNull('service_point_id')->pluck('client_id');
        $servicePointIds = $assignments->whereNotNull('service_point_id')->pluck('service_point_id');

        return Employee::where(function ($q) use ($clientWideIds, $servicePointIds) {
            $q->whereIn('client_id', $clientWideIds)
                ->orWhereIn('service_point_id', $servicePointIds);
        })->pluck('id')->all();
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        Employee::create($request->validated());

        return back()->with('success', 'Colaborador creado correctamente.');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return back()->with('success', 'Colaborador actualizado correctamente.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);
        $employee->delete();

        return back()->with('success', 'Colaborador eliminado correctamente.');
    }

    public function import(ImportEmployeeRequest $request): RedirectResponse
    {
        $import = new EmployeeImport;
        Excel::import($import, $request->file('file'));

        $failures = $import->failures();

        if ($failures->isEmpty()) {
            return back()->with('success', "Se importaron {$import->imported} colaboradores correctamente.");
        }

        $errorMessages = $failures
            ->map(fn ($failure) => "Fila {$failure->row()}: ".implode(' ', $failure->errors()))
            ->implode(' | ');

        return back()
            ->with('success', $import->imported > 0 ? "Se importaron {$import->imported} colaboradores." : null)
            ->with('error', "{$failures->count()} fila(s) con errores: {$errorMessages}");
    }
}
