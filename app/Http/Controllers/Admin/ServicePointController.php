<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServicePoint\StoreServicePointRequest;
use App\Http\Requests\ServicePoint\UpdateServicePointRequest;
use App\Models\Client;
use App\Models\ServicePoint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServicePointController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ServicePoint::class);

        $servicePoints = ServicePoint::query()
            ->with('client:id,name')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->client_id, fn ($q, $c) => $q->where('client_id', $c))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $clients = Client::where('status', 'activo')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/ServicePoints/Index', [
            'servicePoints' => $servicePoints,
            'clients' => $clients,
            'filters' => $request->only(['search', 'client_id', 'status']),
        ]);
    }

    public function store(StoreServicePointRequest $request): RedirectResponse
    {
        ServicePoint::create($request->validated());

        return back()->with('success', 'Punto de servicio creado correctamente.');
    }

    public function update(UpdateServicePointRequest $request, ServicePoint $servicePoint): RedirectResponse
    {
        $servicePoint->update($request->validated());

        return back()->with('success', 'Punto de servicio actualizado correctamente.');
    }

    public function destroy(ServicePoint $servicePoint): RedirectResponse
    {
        $this->authorize('delete', $servicePoint);
        $servicePoint->delete();

        return back()->with('success', 'Punto de servicio eliminado correctamente.');
    }
}
