<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    /** Switches que autosavean individualmente desde Configuración (ver toggle()). */
    private const TOGGLE_KEYS = [
        'allow_employee_self_attendance',
        'employee_self_attendance_requires_photo',
        'employee_self_attendance_allow_exit',
        'employee_self_attendance_requires_location',
        'supervisor_capture_requires_photo',
        'attendance_photo_review_enabled',
    ];

    public function index(): Response
    {
        $this->authorize('view', Setting::class);

        $settings = Setting::orderBy('group')->orderBy('key')->get()
            ->groupBy('group');

        return Inertia::render('admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(UpdateSettingsRequest $request, SettingsRepository $repository): RedirectResponse
    {
        $repository->setMany($request->validated());

        return back()->with('success', 'Configuración actualizada correctamente.');
    }

    /**
     * Guarda un solo switch booleano de inmediato (sin depender del formulario completo
     * de Configuración) — endpoint mínimo y directo para el autosave de los switches.
     */
    public function toggle(Request $request, SettingsRepository $repository): RedirectResponse
    {
        $this->authorize('update', Setting::class);

        $validated = $request->validate([
            'key' => ['required', 'string', Rule::in(self::TOGGLE_KEYS)],
            'value' => ['required', 'boolean'],
        ]);

        $repository->set($validated['key'], $validated['value']);

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
