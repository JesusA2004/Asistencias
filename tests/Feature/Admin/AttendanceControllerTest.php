<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceAudit;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrador');

        $client = Client::create(['name' => 'Empresa Demo', 'status' => 'activo']);
        $sp = ServicePoint::create(['client_id' => $client->id, 'name' => 'Punto Demo', 'status' => 'activo']);
        $employee = Employee::create([
            'employee_number' => 'EMP-001', 'name' => 'Juan', 'last_name' => 'Pérez',
            'status' => 'activo', 'client_id' => $client->id, 'service_point_id' => $sp->id,
        ]);

        $this->attendance = Attendance::create([
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'service_point_id' => $sp->id,
            'supervisor_id' => $this->admin->id,
            'attendance_date' => now()->format('Y-m-d'),
            'status' => 'presente',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_correcting_attendance_requires_a_reason(): void
    {
        $this->actingAs($this->admin)
            ->patch("/asistencias/{$this->attendance->id}/corregir", [
                'status' => 'retardo',
            ])
            ->assertSessionHasErrors('reason');
    }

    public function test_correcting_attendance_updates_status_and_creates_audit(): void
    {
        $this->actingAs($this->admin)
            ->patch("/asistencias/{$this->attendance->id}/corregir", [
                'status' => 'retardo',
                'entry_time' => '08:15',
                'reason' => 'El colaborador llegó tarde por tráfico intenso.',
            ])
            ->assertRedirect();

        $this->attendance->refresh();
        $this->assertSame('retardo', $this->attendance->status);

        $this->assertDatabaseHas('attendance_audits', [
            'attendance_id' => $this->attendance->id,
            'action' => 'corregido',
        ]);
    }

    public function test_deleting_attendance_requires_reason_of_at_least_ten_characters(): void
    {
        $this->actingAs($this->admin)
            ->delete("/asistencias/{$this->attendance->id}", ['reason' => 'corto'])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseHas('attendances', ['id' => $this->attendance->id]);
    }

    public function test_deleting_attendance_with_valid_reason_soft_deletes_and_audits(): void
    {
        $this->actingAs($this->admin)
            ->delete("/asistencias/{$this->attendance->id}", [
                'reason' => 'Registro duplicado por error de captura del supervisor.',
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('attendances', ['id' => $this->attendance->id]);
        $this->assertDatabaseHas('attendance_audits', [
            'attendance_id' => $this->attendance->id,
            'action' => 'eliminado',
        ]);
    }
}
