<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
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
}
