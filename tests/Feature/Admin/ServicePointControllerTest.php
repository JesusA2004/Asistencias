<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\ServicePoint;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ServicePointControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrador');
        $this->client = Client::create(['name' => 'Empresa Demo', 'status' => 'activo']);
    }

    public function test_admin_can_create_service_point(): void
    {
        $this->actingAs($this->admin)
            ->post('/puntos-servicio', [
                'client_id' => $this->client->id,
                'name' => 'Planta Norte',
                'address' => 'Av. Siempre Viva 123',
                'status' => 'activo',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('service_points', ['name' => 'Planta Norte', 'client_id' => $this->client->id]);
    }

    public function test_service_point_requires_valid_client(): void
    {
        $this->actingAs($this->admin)
            ->post('/puntos-servicio', [
                'client_id' => 999999,
                'name' => 'Punto Fantasma',
                'status' => 'activo',
            ])
            ->assertSessionHasErrors('client_id');
    }

    public function test_admin_can_update_service_point(): void
    {
        $sp = ServicePoint::create(['client_id' => $this->client->id, 'name' => 'Original', 'status' => 'activo']);

        $this->actingAs($this->admin)
            ->put("/puntos-servicio/{$sp->id}", [
                'client_id' => $this->client->id,
                'name' => 'Renombrado',
                'status' => 'inactivo',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('service_points', ['id' => $sp->id, 'name' => 'Renombrado', 'status' => 'inactivo']);
    }

    public function test_admin_can_delete_service_point(): void
    {
        $sp = ServicePoint::create(['client_id' => $this->client->id, 'name' => 'Para borrar', 'status' => 'activo']);

        $this->actingAs($this->admin)
            ->delete("/puntos-servicio/{$sp->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('service_points', ['id' => $sp->id]);
    }

    public function test_service_points_can_be_filtered_by_client(): void
    {
        $otherClient = Client::create(['name' => 'Otra Empresa', 'status' => 'activo']);
        ServicePoint::create(['client_id' => $this->client->id, 'name' => 'Punto A', 'status' => 'activo']);
        ServicePoint::create(['client_id' => $otherClient->id, 'name' => 'Punto B', 'status' => 'activo']);

        $this->actingAs($this->admin)
            ->get("/puntos-servicio?client_id={$this->client->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/ServicePoints/Index')
                ->has('servicePoints.data', 1)
                ->where('servicePoints.data.0.name', 'Punto A')
            );
    }
}
