<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\ServicePoint;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorAssignmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $supervisor;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrador');
        $this->supervisor = User::factory()->create();
        $this->supervisor->assignRole('supervisor');
        $this->client = Client::create(['name' => 'Empresa Demo', 'status' => 'activo']);
    }

    public function test_admin_can_assign_supervisor_to_whole_client(): void
    {
        $this->actingAs($this->admin)
            ->post('/asignaciones', [
                'supervisor_user_id' => $this->supervisor->id,
                'client_id' => $this->client->id,
                'service_point_id' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('supervisor_assignments', [
            'supervisor_user_id' => $this->supervisor->id,
            'client_id' => $this->client->id,
            'service_point_id' => null,
        ]);
    }

    public function test_admin_can_assign_supervisor_to_specific_service_point(): void
    {
        $sp = ServicePoint::create(['client_id' => $this->client->id, 'name' => 'Punto A', 'status' => 'activo']);

        $this->actingAs($this->admin)
            ->post('/asignaciones', [
                'supervisor_user_id' => $this->supervisor->id,
                'client_id' => $this->client->id,
                'service_point_id' => $sp->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('supervisor_assignments', [
            'supervisor_user_id' => $this->supervisor->id,
            'service_point_id' => $sp->id,
        ]);
    }

    public function test_admin_can_delete_assignment(): void
    {
        $assignment = SupervisorAssignment::create([
            'supervisor_user_id' => $this->supervisor->id,
            'client_id' => $this->client->id,
            'service_point_id' => null,
        ]);

        $this->actingAs($this->admin)
            ->delete("/asignaciones/{$assignment->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('supervisor_assignments', ['id' => $assignment->id]);
    }
}
