<?php

namespace Tests\Feature\Admin;

use App\Models\Shift;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftControllerTest extends TestCase
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

    public function test_admin_can_create_shift_with_work_days(): void
    {
        $this->actingAs($this->admin)
            ->post('/turnos', [
                'name' => 'Turno Matutino',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'work_days' => ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'],
                'tolerance_minutes' => 10,
                'status' => 'activo',
            ])
            ->assertRedirect();

        $shift = Shift::where('name', 'Turno Matutino')->firstOrFail();
        $this->assertSame(['lunes', 'martes', 'miercoles', 'jueves', 'viernes'], $shift->work_days);
        $this->assertSame(10, $shift->tolerance_minutes);
    }

    public function test_tolerance_minutes_must_be_a_non_negative_integer(): void
    {
        $this->actingAs($this->admin)
            ->post('/turnos', [
                'name' => 'Turno Inválido',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'tolerance_minutes' => -5,
                'status' => 'activo',
            ])
            ->assertSessionHasErrors('tolerance_minutes');
    }

    public function test_admin_can_update_shift(): void
    {
        $shift = Shift::create([
            'name' => 'Original',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'work_days' => ['lunes'],
            'tolerance_minutes' => 5,
            'status' => 'activo',
        ]);

        $this->actingAs($this->admin)
            ->put("/turnos/{$shift->id}", [
                'name' => 'Turno Vespertino',
                'start_time' => '14:00',
                'end_time' => '22:00',
                'work_days' => ['sabado', 'domingo'],
                'tolerance_minutes' => 15,
                'status' => 'activo',
            ])
            ->assertRedirect();

        $shift->refresh();
        $this->assertSame('Turno Vespertino', $shift->name);
        $this->assertSame(['sabado', 'domingo'], $shift->work_days);
    }

    public function test_admin_can_delete_shift(): void
    {
        $shift = Shift::create([
            'name' => 'Para borrar',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'tolerance_minutes' => 5,
            'status' => 'activo',
        ]);

        $this->actingAs($this->admin)
            ->delete("/turnos/{$shift->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('shifts', ['id' => $shift->id]);
    }
}
