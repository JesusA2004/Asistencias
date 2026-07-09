<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\StoreShiftRequest;
use App\Http\Requests\Shift\UpdateShiftRequest;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Shift::class);

        $shifts = Shift::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Shifts/Index', [
            'shifts' => $shifts,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(StoreShiftRequest $request): RedirectResponse
    {
        Shift::create($request->validated());

        return back()->with('success', 'Turno creado correctamente.');
    }

    public function update(UpdateShiftRequest $request, Shift $shift): RedirectResponse
    {
        $shift->update($request->validated());

        return back()->with('success', 'Turno actualizado correctamente.');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $this->authorize('delete', $shift);
        $shift->delete();

        return back()->with('success', 'Turno eliminado correctamente.');
    }
}
