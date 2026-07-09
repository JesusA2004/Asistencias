<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrador');
    }

    public function test_guests_cannot_access_clients(): void
    {
        $this->get('/empresas')->assertRedirect('/login');
    }

    public function test_admin_can_list_clients(): void
    {
        Client::create(['name' => 'Empresa Uno', 'status' => 'activo']);

        $this->actingAs($this->admin)
            ->get('/empresas')
            ->assertOk();
    }

    public function test_admin_can_create_client(): void
    {
        $this->actingAs($this->admin)
            ->post('/empresas', [
                'name' => 'Seguridad Total SA de CV',
                'business_name' => 'Seguridad Total',
                'rfc' => 'STO010101ABC',
                'status' => 'activo',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('clients', ['name' => 'Seguridad Total SA de CV']);
    }

    public function test_creating_client_requires_name(): void
    {
        $this->actingAs($this->admin)
            ->post('/empresas', ['status' => 'activo'])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_client(): void
    {
        $client = Client::create(['name' => 'Original', 'status' => 'activo']);

        $this->actingAs($this->admin)
            ->put("/empresas/{$client->id}", [
                'name' => 'Actualizada',
                'status' => 'inactivo',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => 'Actualizada', 'status' => 'inactivo']);
    }

    public function test_admin_can_delete_client(): void
    {
        $client = Client::create(['name' => 'Para borrar', 'status' => 'activo']);

        $this->actingAs($this->admin)
            ->delete("/empresas/{$client->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_user_without_permission_cannot_create_client(): void
    {
        $rh = User::factory()->create();
        $rh->assignRole('rh');

        $this->actingAs($rh)
            ->post('/empresas', ['name' => 'No debería crearse', 'status' => 'activo'])
            ->assertForbidden();
    }
}
