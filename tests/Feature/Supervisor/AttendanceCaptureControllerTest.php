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

    private function basePayload(): array
    {
        return [
            'client_id' => $this->client->id,
            'service_point_id' => $this->servicePoint->id,
            'attendance_date' => now()->format('Y-m-d'),
        ];
    }

    // ── Entrada ──────────────────────────────────────────────────────

    public function test_supervisor_can_register_entry_for_assigned_point(): void
    {
        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/entrada', [
                ...$this->basePayload(),
                'entries' => [
                    ['employee_id' => $this->employee->id, 'entry_time' => '08:00', 'status' => 'presente'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'entry_time' => '08:00',
            'exit_time' => null,
            'status' => 'presente',
            'supervisor_id' => $this->supervisor->id,
        ]);
        $this->assertDatabaseHas('attendance_events', ['event_type' => 'entrada']);
    }

    public function test_entry_is_not_duplicated_if_already_registered(): void
    {
        Attendance::create([
            'employee_id' => $this->employee->id, 'client_id' => $this->client->id, 'service_point_id' => $this->servicePoint->id,
            'attendance_date' => now()->format('Y-m-d'), 'status' => 'presente', 'entry_time' => '08:00',
            'supervisor_id' => $this->supervisor->id, 'created_by' => $this->supervisor->id,
        ]);

        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/entrada', [
                ...$this->basePayload(),
                'entries' => [
                    ['employee_id' => $this->employee->id, 'entry_time' => '09:00', 'status' => 'retardo'],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(1, Attendance::where('employee_id', $this->employee->id)->count());
        $this->assertDatabaseHas('attendances', ['employee_id' => $this->employee->id, 'entry_time' => '08:00']);
    }

    public function test_supervisor_cannot_register_entry_for_unassigned_service_point(): void
    {
        $otherSp = ServicePoint::create(['client_id' => $this->client->id, 'name' => 'Punto No Asignado', 'status' => 'activo']);
        $otherEmployee = Employee::create([
            'employee_number' => 'EMP-002', 'name' => 'Otro', 'last_name' => 'Empleado',
            'status' => 'activo', 'client_id' => $this->client->id, 'service_point_id' => $otherSp->id,
        ]);

        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/entrada', [
                'client_id' => $this->client->id,
                'service_point_id' => $otherSp->id,
                'attendance_date' => now()->format('Y-m-d'),
                'entries' => [
                    ['employee_id' => $otherEmployee->id, 'entry_time' => '08:00', 'status' => 'presente'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('attendances', ['employee_id' => $otherEmployee->id]);
    }

    // ── Salida ───────────────────────────────────────────────────────

    public function test_exit_requires_prior_entry(): void
    {
        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/salida', [
                ...$this->basePayload(),
                'exits' => [
                    ['employee_id' => $this->employee->id, 'exit_time' => '18:00'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('attendances', ['employee_id' => $this->employee->id]);
    }

    public function test_supervisor_can_register_exit_after_entry_exists(): void
    {
        Attendance::create([
            'employee_id' => $this->employee->id, 'client_id' => $this->client->id, 'service_point_id' => $this->servicePoint->id,
            'attendance_date' => now()->format('Y-m-d'), 'status' => 'presente', 'entry_time' => '08:00',
            'supervisor_id' => $this->supervisor->id, 'created_by' => $this->supervisor->id,
        ]);

        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/salida', [
                ...$this->basePayload(),
                'exits' => [
                    ['employee_id' => $this->employee->id, 'exit_time' => '18:00'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id, 'entry_time' => '08:00', 'exit_time' => '18:00',
        ]);
        $this->assertDatabaseHas('attendance_events', ['event_type' => 'salida']);
    }

    public function test_exit_is_not_duplicated_if_already_registered(): void
    {
        Attendance::create([
            'employee_id' => $this->employee->id, 'client_id' => $this->client->id, 'service_point_id' => $this->servicePoint->id,
            'attendance_date' => now()->format('Y-m-d'), 'status' => 'presente', 'entry_time' => '08:00', 'exit_time' => '18:00',
            'supervisor_id' => $this->supervisor->id, 'created_by' => $this->supervisor->id,
        ]);

        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/salida', [
                ...$this->basePayload(),
                'exits' => [
                    ['employee_id' => $this->employee->id, 'exit_time' => '19:00'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', ['employee_id' => $this->employee->id, 'exit_time' => '18:00']);
    }

    // ── Incidencia ───────────────────────────────────────────────────

    public function test_falta_incident_does_not_require_notes(): void
    {
        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/incidencia', [
                ...$this->basePayload(),
                'status' => 'falta',
                'employee_ids' => [$this->employee->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', ['employee_id' => $this->employee->id, 'status' => 'falta', 'entry_time' => null]);
    }

    public function test_permiso_incident_requires_notes(): void
    {
        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/incidencia', [
                ...$this->basePayload(),
                'status' => 'permiso',
                'employee_ids' => [$this->employee->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('attendances', ['employee_id' => $this->employee->id]);
    }

    public function test_permiso_incident_succeeds_with_notes(): void
    {
        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/incidencia', [
                ...$this->basePayload(),
                'status' => 'permiso',
                'notes' => 'Permiso médico autorizado',
                'employee_ids' => [$this->employee->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', ['employee_id' => $this->employee->id, 'status' => 'permiso']);
    }

    public function test_incident_is_skipped_when_attendance_already_exists(): void
    {
        Attendance::create([
            'employee_id' => $this->employee->id, 'client_id' => $this->client->id, 'service_point_id' => $this->servicePoint->id,
            'attendance_date' => now()->format('Y-m-d'), 'status' => 'presente', 'entry_time' => '08:00',
            'supervisor_id' => $this->supervisor->id, 'created_by' => $this->supervisor->id,
        ]);

        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/incidencia', [
                ...$this->basePayload(),
                'status' => 'falta',
                'employee_ids' => [$this->employee->id],
            ])
            ->assertRedirect();

        $this->assertSame(1, Attendance::where('employee_id', $this->employee->id)->count());
        $this->assertDatabaseHas('attendances', ['employee_id' => $this->employee->id, 'status' => 'presente']);
    }

    // ── Manual ───────────────────────────────────────────────────────

    public function test_supervisor_cannot_access_manual_capture(): void
    {
        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/manual', [
                ...$this->basePayload(),
                'records' => [
                    ['employee_id' => $this->employee->id, 'status' => 'presente', 'entry_time' => '08:00'],
                ],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_use_manual_capture_and_create_record(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $this->actingAs($admin)
            ->post('/asistencias/capturar/manual', [
                ...$this->basePayload(),
                'records' => [
                    ['employee_id' => $this->employee->id, 'status' => 'presente', 'entry_time' => '08:00', 'exit_time' => '18:00'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', ['employee_id' => $this->employee->id, 'entry_time' => '08:00', 'exit_time' => '18:00']);
    }

    public function test_manual_capture_requires_reason_when_editing_existing_record(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        Attendance::create([
            'employee_id' => $this->employee->id, 'client_id' => $this->client->id, 'service_point_id' => $this->servicePoint->id,
            'attendance_date' => now()->format('Y-m-d'), 'status' => 'presente', 'entry_time' => '08:00',
            'supervisor_id' => $admin->id, 'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post('/asistencias/capturar/manual', [
                ...$this->basePayload(),
                'records' => [
                    ['employee_id' => $this->employee->id, 'status' => 'falta'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', ['employee_id' => $this->employee->id, 'status' => 'presente']);

        $this->actingAs($admin)
            ->post('/asistencias/capturar/manual', [
                ...$this->basePayload(),
                'reason' => 'El colaborador reportó la falta después de la captura inicial',
                'records' => [
                    ['employee_id' => $this->employee->id, 'status' => 'falta'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', ['employee_id' => $this->employee->id, 'status' => 'falta']);
    }

    public function test_rh_can_reach_capture_screen_and_use_manual_mode(): void
    {
        $rh = User::factory()->create();
        $rh->assignRole('rh');

        $this->actingAs($rh)->get('/asistencias/capturar')->assertOk();

        $this->actingAs($rh)
            ->post('/asistencias/capturar/manual', [
                ...$this->basePayload(),
                'records' => [
                    ['employee_id' => $this->employee->id, 'status' => 'presente', 'entry_time' => '08:00'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', ['employee_id' => $this->employee->id, 'entry_time' => '08:00']);
    }
}
