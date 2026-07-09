<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $clients = Client::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('business_name', 'like', "%{$s}%")
                ->orWhere('rfc', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->withCount('employees', 'servicePoints')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        Client::create($request->validated());

        return back()->with('success', 'Empresa creada correctamente.');
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return back()->with('success', 'Empresa actualizada correctamente.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);
        $client->delete();

        return back()->with('success', 'Empresa eliminada correctamente.');
    }
}
