<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ServicePointController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\SupervisorAssignmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Employee\MyAttendanceController;
use App\Http\Controllers\Supervisor\AttendanceCaptureController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

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
        Route::post('/asistencias/capturar', [AttendanceCaptureController::class, 'store'])->name('asistencias.capturar.store');
    });

    Route::middleware('permission:Ver asistencias')->group(function () {
        Route::get('/asistencias', [AttendanceController::class, 'index'])->name('asistencias.index');
        Route::patch('/asistencias/{attendance}/corregir', [AttendanceController::class, 'correct'])->name('asistencias.corregir');
        Route::delete('/asistencias/{attendance}', [AttendanceController::class, 'destroy'])->name('asistencias.destroy');
    });

    Route::middleware('permission:Ver mis asistencias')->group(function () {
        Route::get('/mis-asistencias', [MyAttendanceController::class, 'index'])->name('mis-asistencias.index');
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
});

require __DIR__ . '/settings.php';
