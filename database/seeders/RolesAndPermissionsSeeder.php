<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Usuarios
            'Ver usuarios', 'Crear usuarios', 'Editar usuarios', 'Eliminar usuarios',
            // Colaboradores
            'Ver colaboradores', 'Crear colaboradores', 'Editar colaboradores', 'Eliminar colaboradores', 'Importar colaboradores',
            // Empresas
            'Ver empresas', 'Crear empresas', 'Editar empresas', 'Eliminar empresas',
            // Puntos de servicio
            'Ver puntos de servicio', 'Crear puntos de servicio', 'Editar puntos de servicio', 'Eliminar puntos de servicio',
            // Turnos
            'Ver turnos', 'Crear turnos', 'Editar turnos', 'Eliminar turnos',
            // Asistencias
            'Ver asistencias', 'Registrar asistencias', 'Editar asistencias', 'Eliminar asistencias', 'Corregir asistencias', 'Ver mis asistencias',
            // Reportes
            'Ver reportes', 'Exportar reportes',
            // Auditoría
            'Ver auditoría',
            // Dashboard
            'Ver dashboard',
            // Roles y permisos
            'Ver roles y permisos', 'Crear roles y permisos', 'Editar roles y permisos', 'Eliminar roles y permisos',
            // Asignaciones
            'Ver asignaciones', 'Crear asignaciones', 'Eliminar asignaciones',
            // Configuración
            'Ver configuración', 'Editar configuración',
            // Evidencias de asistencia
            'Registrar mi asistencia', 'Ver evidencias de asistencia', 'Revisar evidencias de asistencia',
            'Ver todas las evidencias', 'Ver evidencias de sus ubicaciones',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $supervisor = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $colaborador = Role::firstOrCreate(['name' => 'colaborador', 'guard_name' => 'web']);
        $rh = Role::firstOrCreate(['name' => 'rh', 'guard_name' => 'web']);

        $admin->syncPermissions(Permission::all());

        $supervisor->syncPermissions([
            'Ver dashboard',
            'Ver puntos de servicio',
            'Registrar asistencias',
            'Ver mis asistencias',
            'Ver colaboradores',
            'Ver asignaciones',
            'Ver evidencias de asistencia',
            'Ver evidencias de sus ubicaciones',
        ]);

        $colaborador->syncPermissions([
            'Ver mis asistencias',
            'Ver dashboard',
            'Registrar mi asistencia',
            // Solo para poder ver sus propias fotos de evidencia (AttendancePhotoPolicy las
            // acota a employee_id propio); no se le da "ver de sus ubicaciones" ni "ver todas",
            // así que el índice de la galería de evidencias le queda vacío si llega a entrar.
            'Ver evidencias de asistencia',
        ]);

        $rh->syncPermissions([
            'Ver dashboard',
            'Ver reportes',
            'Exportar reportes',
            'Ver colaboradores',
            'Ver empresas',
            'Ver puntos de servicio',
            'Ver asistencias',
            'Ver turnos',
            'Registrar asistencias',
            'Ver evidencias de asistencia',
            'Revisar evidencias de asistencia',
            'Ver todas las evidencias',
        ]);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@asistencias.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin2024!'),
                'email_verified_at' => now(),
            ]
        );
        $adminUser->assignRole('administrador');
    }
}
