<?php

namespace Tests\Feature\Supervisor;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCaptureControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $supervisor;

    private Client $client;

    private ServicePoint $servicePoint;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->supervisor = User::factory()->create();
        $this->supervisor->assignRole('supervisor');

        $this->client = Client::create(['name' => 'Empresa Demo', 'status' => 'activo']);
        $this->servicePoint = ServicePoint::create(['client_id' => $this->client->id, 'name' => 'Punto Demo', 'status' => 'activo']);
        $this->employee = Employee::create([
            'employee_number' => 'EMP-001', 'name' => 'Juan', 'last_name' => 'Pérez',
            'status' => 'activo', 'client_id' => $this->client->id, 'service_point_id' => $this->servicePoint->id,
        ]);

        SupervisorAssignment::create([
            'supervisor_user_id' => $this->supervisor->id,
            'client_id' => $this->client->id,
            'service_point_id' => $this->servicePoint->id,
        ]);
    }

    public function test_supervisor_can_capture_attendance_for_assigned_point(): void
    {
        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar', [
                'client_id' => $this->client->id,
                'service_point_id' => $this->servicePoint->id,
                'attendance_date' => now()->format('Y-m-d'),
                'records' => [
                    ['employee_id' => $this->employee->id, 'status' => 'presente', 'entry_time' => '08:00'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'status' => 'presente',
            'supervisor_id' => $this->supervisor->id,
        ]);
    }

    public function test_supervisor_cannot_capture_for_unassigned_service_point(): void
    {
        $otherSp = ServicePoint::create(['client_id' => $this->client->id, 'name' => 'Punto No Asignado', 'status' => 'activo']);
        $otherEmployee = Employee::create([
            'employee_number' => 'EMP-002', 'name' => 'Otro', 'last_name' => 'Empleado',
            'status' => 'activo', 'client_id' => $this->client->id, 'service_point_id' => $otherSp->id,
        ]);

        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar', [
                'client_id' => $this->client->id,
                'service_point_id' => $otherSp->id,
                'attendance_date' => now()->format('Y-m-d'),
                'records' => [
                    ['employee_id' => $otherEmployee->id, 'status' => 'presente'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('attendances', ['employee_id' => $otherEmployee->id]);
    }

    public function test_supervisor_cannot_create_duplicate_attendance_for_same_day(): void
    {
        Attendance::create([
            'employee_id' => $this->employee->id,
            'client_id' => $this->client->id,
            'service_point_id' => $this->servicePoint->id,
            'attendance_date' => now()->format('Y-m-d'),
            'status' => 'presente',
            'supervisor_id' => $this->supervisor->id,
            'created_by' => $this->supervisor->id,
        ]);

        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar', [
                'client_id' => $this->client->id,
                'service_point_id' => $this->servicePoint->id,
                'attendance_date' => now()->format('Y-m-d'),
                'records' => [
                    ['employee_id' => $this->employee->id, 'status' => 'falta'],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(1, Attendance::where('employee_id', $this->employee->id)->count());
        $this->assertDatabaseHas('attendances', ['employee_id' => $this->employee->id, 'status' => 'presente']);
    }
}
