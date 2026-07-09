<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceAudit;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\Shift;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = collect([
            Shift::create([
                'name' => 'Turno Matutino',
                'start_time' => '06:00',
                'end_time' => '14:00',
                'work_days' => ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'],
                'tolerance_minutes' => 10,
                'status' => 'activo',
            ]),
            Shift::create([
                'name' => 'Turno Vespertino',
                'start_time' => '14:00',
                'end_time' => '22:00',
                'work_days' => ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'],
                'tolerance_minutes' => 15,
                'status' => 'activo',
            ]),
            Shift::create([
                'name' => 'Turno Nocturno',
                'start_time' => '22:00',
                'end_time' => '06:00',
                'work_days' => ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'],
                'tolerance_minutes' => 10,
                'status' => 'activo',
            ]),
        ]);

        $clientsData = [
            ['name' => 'Grupo Industrial del Norte', 'business_name' => 'Grupo Industrial del Norte SA de CV', 'rfc' => 'GIN010101AB1', 'points' => ['Planta Norte', 'Almacén Central']],
            ['name' => 'Plaza Comercial Reforma', 'business_name' => 'Plaza Comercial Reforma SA de CV', 'rfc' => 'PCR020202BC2', 'points' => ['Entrada Principal', 'Estacionamiento']],
            ['name' => 'Corporativo Santa Fe', 'business_name' => 'Corporativo Santa Fe SA de CV', 'rfc' => 'CSF030303CD3', 'points' => ['Torre A', 'Torre B']],
        ];

        $supervisor = User::firstOrCreate(
            ['email' => 'supervisor@asistencias.com'],
            ['name' => 'Carlos Supervisor', 'password' => Hash::make('Supervisor2024!'), 'email_verified_at' => now()]
        );
        $supervisor->syncRoles(['supervisor']);

        $rh = User::firstOrCreate(
            ['email' => 'rh@asistencias.com'],
            ['name' => 'Recursos Humanos', 'password' => Hash::make('RH2024!'), 'email_verified_at' => now()]
        );
        $rh->syncRoles(['rh']);

        $firstNames = ['Juan', 'María', 'Luis', 'Ana', 'Carlos', 'Laura', 'Pedro', 'Sofía', 'Miguel', 'Fernanda', 'José', 'Daniela', 'Ricardo', 'Andrea', 'Roberto', 'Paola'];
        $lastNames = ['García', 'Martínez', 'López', 'Hernández', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores', 'Rivera', 'Gómez'];

        $employeeCounter = 1;
        $allEmployees = collect();

        foreach ($clientsData as $clientData) {
            $client = Client::create([
                'name' => $clientData['name'],
                'business_name' => $clientData['business_name'],
                'rfc' => $clientData['rfc'],
                'status' => 'activo',
            ]);

            $servicePoints = collect($clientData['points'])->map(fn ($name) => ServicePoint::create([
                'client_id' => $client->id,
                'name' => $name,
                'address' => 'Dirección de ejemplo, ' . $name,
                'status' => 'activo',
            ]));

            // Supervisor asignado a esta empresa completa
            SupervisorAssignment::firstOrCreate([
                'supervisor_user_id' => $supervisor->id,
                'client_id' => $client->id,
                'service_point_id' => null,
            ]);

            foreach ($servicePoints as $sp) {
                $employeeCount = random_int(4, 6);

                for ($i = 0; $i < $employeeCount; $i++) {
                    $name = $firstNames[array_rand($firstNames)];
                    $lastName = $lastNames[array_rand($lastNames)];
                    $secondLastName = $lastNames[array_rand($lastNames)];
                    $shift = $shifts[array_rand($shifts->all())];

                    $employee = Employee::create([
                        'employee_number' => sprintf('EMP-%04d', $employeeCounter++),
                        'name' => $name,
                        'last_name' => $lastName,
                        'second_last_name' => $secondLastName,
                        'email' => strtolower($name . '.' . $lastName . $employeeCounter . '@demo.com'),
                        'phone' => '55' . random_int(10000000, 99999999),
                        'status' => 'activo',
                        'client_id' => $client->id,
                        'service_point_id' => $sp->id,
                        'shift_id' => $shift->id,
                    ]);

                    $allEmployees->push($employee);
                }
            }
        }

        // Vincula un colaborador de ejemplo a un usuario para el login "colaborador"
        $firstEmployee = $allEmployees->first();
        if ($firstEmployee) {
            $employeeUser = User::firstOrCreate(
                ['email' => 'colaborador@asistencias.com'],
                ['name' => $firstEmployee->name . ' ' . $firstEmployee->last_name, 'password' => Hash::make('Colaborador2024!'), 'email_verified_at' => now()]
            );
            $employeeUser->syncRoles(['colaborador']);
            $firstEmployee->update(['user_id' => $employeeUser->id]);
        }

        // ~30 días de asistencias variadas para poblar dashboard, reportes y auditoría
        $statusWeights = [
            'presente' => 82,
            'retardo' => 8,
            'falta' => 5,
            'descanso' => 3,
            'permiso' => 1,
            'incapacidad' => 1,
        ];

        $pickStatus = function () use ($statusWeights) {
            $rand = random_int(1, 100);
            $acc = 0;
            foreach ($statusWeights as $status => $weight) {
                $acc += $weight;
                if ($rand <= $acc) {
                    return $status;
                }
            }

            return 'presente';
        };

        $today = Carbon::today();
        $auditsCreated = 0;

        for ($daysAgo = 29; $daysAgo >= 0; $daysAgo--) {
            $date = $today->copy()->subDays($daysAgo);

            // Sin capturas los domingos, para que "capturas pendientes" tenga sentido.
            if ($date->isSunday()) {
                continue;
            }

            foreach ($allEmployees as $employee) {
                // El día de hoy deja huecos intencionales (capturas pendientes reales).
                if ($daysAgo === 0 && random_int(1, 100) <= 30) {
                    continue;
                }

                $status = $pickStatus();
                $entryTime = null;
                $exitTime = null;

                if (in_array($status, ['presente', 'retardo'], true)) {
                    $entryTime = $status === 'retardo' ? sprintf('%02d:%02d', random_int(7, 9), random_int(15, 45)) : sprintf('%02d:%02d', random_int(6, 7), random_int(0, 15));
                    $exitTime = sprintf('%02d:%02d', random_int(14, 16), random_int(0, 59));
                }

                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'client_id' => $employee->client_id,
                    'service_point_id' => $employee->service_point_id,
                    'shift_id' => $employee->shift_id,
                    'supervisor_id' => $supervisor->id,
                    'attendance_date' => $date->format('Y-m-d'),
                    'status' => $status,
                    'entry_time' => $entryTime,
                    'exit_time' => $exitTime,
                    'created_by' => $supervisor->id,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

                // Genera un puñado de correcciones auditadas para la pantalla de Auditoría.
                if ($auditsCreated < 12 && $daysAgo > 1 && random_int(1, 100) <= 4) {
                    $oldValues = $attendance->only(['status', 'entry_time', 'exit_time', 'notes']);
                    $attendance->update(['status' => 'presente', 'entry_time' => '07:00', 'exit_time' => '15:00', 'updated_by' => $supervisor->id]);

                    AttendanceAudit::create([
                        'attendance_id' => $attendance->id,
                        'action' => 'corregido',
                        'old_values' => $oldValues,
                        'new_values' => $attendance->fresh()->only(['status', 'entry_time', 'exit_time', 'notes']),
                        'reason' => 'Corrección administrativa: el colaborador sí asistió, se omitió el registro inicial.',
                        'changed_by' => $supervisor->id,
                        'created_at' => $date,
                    ]);
                    $auditsCreated++;
                }
            }
        }
    }
}
