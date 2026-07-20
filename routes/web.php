<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AttendanceEvidenceController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ServicePointController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\SupervisorAssignmentController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttendancePhotoController;
use App\Http\Controllers\AttendanceWarningAcceptanceController;
use App\Http\Controllers\Employee\SelfAttendanceController;
use App\Http\Controllers\Supervisor\AttendanceCaptureController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'))->name('home');

// Bloquear registro público — redirige a login
Route::get('/register', fn () => redirect()->route('login'))->name('register');

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard (role-aware)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Administración ──────────────────────────────────────────────
    Route::middleware('permission:Ver usuarios')->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update');
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');
    });

    Route::middleware('permission:Ver roles y permisos')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    Route::middleware('permission:Ver colaboradores')->group(function () {
        Route::get('/colaboradores', [EmployeeController::class, 'index'])->name('empleados.index');
        Route::post('/colaboradores', [EmployeeController::class, 'store'])->name('empleados.store');
        Route::put('/colaboradores/{employee}', [EmployeeController::class, 'update'])->name('empleados.update');
        Route::delete('/colaboradores/{employee}', [EmployeeController::class, 'destroy'])->name('empleados.destroy');
    });

    Route::middleware('permission:Importar colaboradores')->group(function () {
        Route::post('/colaboradores/importar', [EmployeeController::class, 'import'])->name('empleados.importar');
    });

    Route::middleware('permission:Ver empresas')->group(function () {
        Route::get('/empresas', [ClientController::class, 'index'])->name('clientes.index');
        Route::post('/empresas', [ClientController::class, 'store'])->name('clientes.store');
        Route::put('/empresas/{client}', [ClientController::class, 'update'])->name('clientes.update');
        Route::delete('/empresas/{client}', [ClientController::class, 'destroy'])->name('clientes.destroy');
    });

    Route::middleware('permission:Ver puntos de servicio')->group(function () {
        Route::get('/puntos-servicio', [ServicePointController::class, 'index'])->name('puntos.index');
        Route::post('/puntos-servicio', [ServicePointController::class, 'store'])->name('puntos.store');
        Route::put('/puntos-servicio/{servicePoint}', [ServicePointController::class, 'update'])->name('puntos.update');
        Route::delete('/puntos-servicio/{servicePoint}', [ServicePointController::class, 'destroy'])->name('puntos.destroy');
    });

    Route::middleware('permission:Ver turnos')->group(function () {
        Route::get('/turnos', [ShiftController::class, 'index'])->name('turnos.index');
        Route::post('/turnos', [ShiftController::class, 'store'])->name('turnos.store');
        Route::put('/turnos/{shift}', [ShiftController::class, 'update'])->name('turnos.update');
        Route::delete('/turnos/{shift}', [ShiftController::class, 'destroy'])->name('turnos.destroy');
    });

    Route::middleware('permission:Ver asignaciones')->group(function () {
        Route::get('/asignaciones', [SupervisorAssignmentController::class, 'index'])->name('asignaciones.index');
        Route::post('/asignaciones', [SupervisorAssignmentController::class, 'store'])->name('asignaciones.store');
        Route::delete('/asignaciones/{assignment}', [SupervisorAssignmentController::class, 'destroy'])->name('asignaciones.destroy');
    });

    // ── Asistencias ─────────────────────────────────────────────────
    Route::middleware('permission:Registrar asistencias')->group(function () {
        Route::get('/asistencias/capturar', [AttendanceCaptureController::class, 'index'])->name('asistencias.capturar');
        Route::post('/asistencias/capturar/entrada', [AttendanceCaptureController::class, 'storeEntry'])->name('asistencias.capturar.entrada');
        Route::post('/asistencias/capturar/salida', [AttendanceCaptureController::class, 'storeExit'])->name('asistencias.capturar.salida');
        Route::post('/asistencias/capturar/incidencia', [AttendanceCaptureController::class, 'storeIncident'])->name('asistencias.capturar.incidencia');
        Route::post('/asistencias/capturar/manual', [AttendanceCaptureController::class, 'storeManual'])->name('asistencias.capturar.manual');
    });

    Route::middleware('permission:Ver asistencias')->group(function () {
        Route::get('/asistencias/gestion', [AttendanceController::class, 'index'])->name('asistencias.index');
        Route::patch('/asistencias/{attendance}/corregir', [AttendanceController::class, 'correct'])->name('asistencias.corregir');
        Route::delete('/asistencias/{attendance}', [AttendanceController::class, 'destroy'])->name('asistencias.destroy');
    });

    // Hub: /asistencias no es una página en sí — redirige al primer tab al que el
    // usuario tenga acceso (Gestión > Capturar > Evidencias), para que los enlaces
    // viejos a "/asistencias" (antes la Gestión) y el ítem único del sidebar sigan
    // funcionando sin que cada rol tenga que memorizar una sub-ruta distinta.
    Route::get('/asistencias', function () {
        $user = auth()->user();

        if ($user->can('Ver asistencias')) {
            return redirect('/asistencias/gestion');
        }

        if ($user->can('Registrar asistencias')) {
            return redirect('/asistencias/capturar');
        }

        if ($user->can('Ver evidencias de asistencia')) {
            return redirect('/asistencias/evidencias');
        }

        abort(403);
    })->name('asistencias.hub');

    Route::redirect('/mis-asistencias', '/mi-asistencia')->name('mis-asistencias.index');

    Route::middleware('permission:Registrar mi asistencia')->group(function () {
        Route::get('/mi-asistencia', [SelfAttendanceController::class, 'index'])->name('mi-asistencia.index');
        Route::post('/mi-asistencia/entrada', [SelfAttendanceController::class, 'storeEntry'])->name('mi-asistencia.entrada');
        Route::post('/mi-asistencia/salida', [SelfAttendanceController::class, 'storeExit'])->name('mi-asistencia.salida');
    });

    // ── Reportes ─────────────────────────────────────────────────────
    Route::middleware('permission:Ver reportes')->group(function () {
        Route::get('/reportes', [ReportController::class, 'index'])->name('reportes.index');
    });

    Route::middleware('permission:Exportar reportes')->group(function () {
        Route::get('/reportes/excel', [ReportController::class, 'exportExcel'])->name('reportes.excel');
        Route::get('/reportes/pdf', [ReportController::class, 'exportPdf'])->name('reportes.pdf');
    });

    // ── Auditoría ────────────────────────────────────────────────────
    Route::middleware('permission:Ver auditoría')->group(function () {
        Route::get('/auditoria', [AuditController::class, 'index'])->name('auditoria.index');
    });

    // ── Configuración ────────────────────────────────────────────────
    Route::middleware('permission:Ver configuración')->group(function () {
        Route::get('/configuracion', [SystemSettingController::class, 'index'])->name('configuracion.index');
    });

    Route::middleware('permission:Editar configuración')->group(function () {
        Route::patch('/configuracion', [SystemSettingController::class, 'update'])->name('configuracion.update');
    });

    // ── Evidencias de Asistencia ────────────────────────────────────────
    Route::middleware('permission:Ver evidencias de asistencia')->group(function () {
        Route::get('/asistencias/evidencias', [AttendanceEvidenceController::class, 'index'])->name('evidencias.index');
        Route::get('/evidencias-asistencia/{photo}/foto', [AttendancePhotoController::class, 'show'])->name('evidencias.foto');
        Route::get('/evidencias-asistencia/{photo}/miniatura', [AttendancePhotoController::class, 'thumbnail'])->name('evidencias.miniatura');
    });

    Route::redirect('/evidencias-asistencia', '/asistencias/evidencias');

    // Aceptación del aviso de evidencia fotográfica: cualquier autenticado que llegue a un flujo con cámara.
    Route::post('/asistencias/aceptar-aviso', [AttendanceWarningAcceptanceController::class, 'store'])->name('asistencias.aceptar-aviso');
});

require __DIR__.'/settings.php';
