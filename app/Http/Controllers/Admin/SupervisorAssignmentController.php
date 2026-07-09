<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ServicePoint;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupervisorAssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SupervisorAssignment::class);

        $assignments = SupervisorAssignment::query()
            ->with(['supervisor:id,name,email', 'client:id,name', 'servicePoint:id,name'])
            ->when($request->supervisor_id, fn ($q, $s) => $q->where('supervisor_user_id', $s))
            ->when($request->client_id, fn ($q, $c) => $q->where('client_id', $c))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $supervisors = User::role('supervisor')->orderBy('name')->get(['id', 'name', 'email']);
        $clients = Client::where('status', 'activo')->orderBy('name')->get(['id', 'name']);
        $servicePoints = ServicePoint::where('status', 'activo')->with('client:id,name')->orderBy('name')->get(['id', 'name', 'client_id']);

        return Inertia::render('admin/Assignments/Index', [
            'assignments' => $assignments,
            'supervisors' => $supervisors,
            'clients' => $clients,
            'servicePoints' => $servicePoints,
            'filters' => $request->only(['supervisor_id', 'client_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SupervisorAssignment::class);

        $request->validate([
            'supervisor_user_id' => 'required|exists:users,id',
            'client_id' => 'required|exists:clients,id',
            'service_point_id' => 'nullable|exists:service_points,id',
        ]);

        SupervisorAssignment::firstOrCreate([
            'supervisor_user_id' => $request->supervisor_user_id,
            'client_id' => $request->client_id,
            'service_point_id' => $request->service_point_id,
        ]);

        return back()->with('success', 'Asignación creada correctamente.');
    }

    public function destroy(SupervisorAssignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);
        $assignment->delete();

        return back()->with('success', 'Asignación eliminada correctamente.');
    }
}
