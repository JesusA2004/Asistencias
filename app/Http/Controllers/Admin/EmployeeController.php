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

        $employees = Employee::query()
            ->with(['client:id,name', 'servicePoint:id,name', 'shift:id,name'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%")
                ->orWhere('employee_number', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")))
            ->when($request->client_id, fn ($q, $c) => $q->where('client_id', $c))
            ->when($request->service_point_id, fn ($q, $sp) => $q->where('service_point_id', $sp))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('last_name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/Employees/Index', [
            'employees' => $employees,
            'clients' => Client::where('status', 'activo')->orderBy('name')->get(['id', 'name']),
            'servicePoints' => $request->client_id
                ? ServicePoint::where('client_id', $request->client_id)->where('status', 'activo')->orderBy('name')->get(['id', 'name', 'client_id'])
                : [],
            'shifts' => Shift::where('status', 'activo')->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'client_id', 'service_point_id', 'status']),
        ]);
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
        $import = new EmployeeImport();
        Excel::import($import, $request->file('file'));

        $failures = $import->failures();

        if ($failures->isEmpty()) {
            return back()->with('success', "Se importaron {$import->imported} colaboradores correctamente.");
        }

        $errorMessages = $failures
            ->map(fn ($failure) => "Fila {$failure->row()}: " . implode(' ', $failure->errors()))
            ->implode(' | ');

        return back()
            ->with('success', $import->imported > 0 ? "Se importaron {$import->imported} colaboradores." : null)
            ->with('error', "{$failures->count()} fila(s) con errores: {$errorMessages}");
    }
}
