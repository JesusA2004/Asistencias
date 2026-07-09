<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
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

    public function test_admin_can_create_user_with_role(): void
    {
        $this->actingAs($this->admin)
            ->post('/usuarios', [
                'name' => 'Nuevo Supervisor',
                'email' => 'supervisor@demo.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'supervisor',
            ])
            ->assertRedirect();

        $user = User::where('email', 'supervisor@demo.com')->firstOrFail();
        $this->assertTrue($user->hasRole('supervisor'));
    }

    public function test_admin_can_update_user_without_changing_password(): void
    {
        $user = User::factory()->create(['name' => 'Original', 'password' => Hash::make('old-password')]);
        $user->assignRole('supervisor');
        $originalPassword = $user->password;

        $this->actingAs($this->admin)
            ->put("/usuarios/{$user->id}", [
                'name' => 'Actualizado',
                'email' => $user->email,
                'password' => '',
                'role' => 'supervisor',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('Actualizado', $user->name);
        $this->assertSame($originalPassword, $user->password);
    }

    public function test_admin_can_change_user_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('colaborador');

        $this->actingAs($this->admin)
            ->put("/usuarios/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'password' => '',
                'role' => 'rh',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->hasRole('rh'));
        $this->assertFalse($user->hasRole('colaborador'));
    }

    public function test_admin_can_delete_another_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('colaborador');

        $this->actingAs($this->admin)
            ->delete("/usuarios/{$user->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $this->actingAs($this->admin)
            ->delete("/usuarios/{$this->admin->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }
}
