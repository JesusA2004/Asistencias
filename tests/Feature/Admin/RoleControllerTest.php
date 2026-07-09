<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleControllerTest extends TestCase
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

    public function test_admin_can_create_role_with_permissions(): void
    {
        $this->actingAs($this->admin)
            ->post('/roles', [
                'name' => 'coordinador',
                'permissions' => ['Ver colaboradores', 'Ver empresas'],
            ])
            ->assertRedirect();

        $role = Role::where('name', 'coordinador')->firstOrFail();
        $this->assertTrue($role->hasPermissionTo('Ver colaboradores'));
        $this->assertTrue($role->hasPermissionTo('Ver empresas'));
        $this->assertFalse($role->hasPermissionTo('Eliminar empresas'));
    }

    public function test_admin_can_update_role_permissions(): void
    {
        $role = Role::firstOrCreate(['name' => 'coordinador', 'guard_name' => 'web']);
        $role->syncPermissions(['Ver colaboradores']);

        $this->actingAs($this->admin)
            ->put("/roles/{$role->id}", [
                'name' => 'coordinador',
                'permissions' => ['Ver colaboradores', 'Editar colaboradores'],
            ])
            ->assertRedirect();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('Editar colaboradores'));
    }

    public function test_cannot_delete_system_role(): void
    {
        $adminRole = Role::where('name', 'administrador')->firstOrFail();

        $this->actingAs($this->admin)
            ->delete("/roles/{$adminRole->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('roles', ['id' => $adminRole->id]);
    }

    public function test_can_delete_custom_role(): void
    {
        $role = Role::create(['name' => 'temporal', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->delete("/roles/{$role->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }
}
