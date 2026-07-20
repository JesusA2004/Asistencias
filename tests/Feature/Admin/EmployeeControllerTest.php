<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\Shift;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Client $client;

    private ServicePoint $servicePoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrador');
        $this->client = Client::create(['name' => 'Empresa Demo', 'status' => 'activo']);
        $this->servicePoint = ServicePoint::create(['client_id' => $this->client->id, 'name' => 'Punto Demo', 'status' => 'activo']);
    }

    public function test_all_active_service_points_are_always_sent_regardless_of_url_filter(): void
    {
        // Empresa/punto que NO corresponden al filtro de la URL: deben seguir llegando
        // para que el modal de creación pueda armar la cascada empresa -> punto sin recargar.
        $otherClient = Client::create(['name' => 'Otra Empresa', 'status' => 'activo']);
        ServicePoint::create(['client_id' => $otherClient->id, 'name' => 'Otro Punto', 'status' => 'activo']);

        $this->actingAs($this->admin)
            ->get("/colaboradores?client_id={$this->client->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Employees/Index')
                ->has('servicePoints', 2)
            );

        // Sin ningún filtro en la URL tampoco debe llegar vacío.
        $this->actingAs($this->admin)
            ->get('/colaboradores')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Employees/Index')
                ->has('servicePoints', 2)
            );
    }

    public function test_admin_can_create_employee(): void
    {
        $shift = Shift::create([
            'name' => 'Turno Demo', 'start_time' => '08:00', 'end_time' => '17:00', 'tolerance_minutes' => 10, 'status' => 'activo',
        ]);

        $this->actingAs($this->admin)
            ->post('/colaboradores', [
                'employee_number' => 'EMP-001',
                'name' => 'Juan',
                'last_name' => 'Pérez',
                'status' => 'activo',
                'client_id' => $this->client->id,
                'service_point_id' => $this->servicePoint->id,
                'shift_id' => $shift->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'employee_number' => 'EMP-001',
            'client_id' => $this->client->id,
            'service_point_id' => $this->servicePoint->id,
        ]);
    }

    public function test_creating_employee_without_client_leaves_it_null(): void
    {
        $this->actingAs($this->admin)
            ->post('/colaboradores', [
                'employee_number' => 'EMP-002',
                'name' => 'Sin Asignar',
                'last_name' => 'Todavía',
                'status' => 'activo',
                'client_id' => null,
                'service_point_id' => null,
                'shift_id' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'employee_number' => 'EMP-002',
            'client_id' => null,
        ]);
    }

    public function test_employee_number_must_be_unique(): void
    {
        Employee::create([
            'employee_number' => 'EMP-100', 'name' => 'A', 'last_name' => 'B', 'status' => 'activo',
        ]);

        $this->actingAs($this->admin)
            ->post('/colaboradores', [
                'employee_number' => 'EMP-100',
                'name' => 'C',
                'last_name' => 'D',
                'status' => 'activo',
            ])
            ->assertSessionHasErrors('employee_number');
    }

    public function test_admin_can_update_employee(): void
    {
        $employee = Employee::create([
            'employee_number' => 'EMP-200', 'name' => 'Original', 'last_name' => 'Apellido', 'status' => 'activo',
        ]);

        $this->actingAs($this->admin)
            ->put("/colaboradores/{$employee->id}", [
                'employee_number' => 'EMP-200',
                'name' => 'Actualizado',
                'last_name' => 'Apellido',
                'status' => 'inactivo',
                'client_id' => $this->client->id,
                'service_point_id' => $this->servicePoint->id,
            ])
            ->assertRedirect();

        $employee->refresh();
        $this->assertSame('Actualizado', $employee->name);
        $this->assertSame($this->client->id, $employee->client_id);
    }

    public function test_admin_can_delete_employee(): void
    {
        $employee = Employee::create([
            'employee_number' => 'EMP-300', 'name' => 'Para', 'last_name' => 'Borrar', 'status' => 'activo',
        ]);

        $this->actingAs($this->admin)
            ->delete("/colaboradores/{$employee->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_filter_by_has_user_narrows_results(): void
    {
        $withUser = User::factory()->create();
        Employee::create([
            'employee_number' => 'EMP-401', 'name' => 'Con', 'last_name' => 'Usuario', 'status' => 'activo', 'user_id' => $withUser->id,
        ]);
        Employee::create([
            'employee_number' => 'EMP-402', 'name' => 'Sin', 'last_name' => 'Usuario', 'status' => 'activo',
        ]);

        $this->actingAs($this->admin)
            ->get('/colaboradores?has_user=con')
            ->assertInertia(fn (Assert $page) => $page->has('employees.data', 1)
                ->where('employees.data.0.employee_number', 'EMP-401'));

        $this->actingAs($this->admin)
            ->get('/colaboradores?has_user=sin')
            ->assertInertia(fn (Assert $page) => $page->has('employees.data', 1)
                ->where('employees.data.0.employee_number', 'EMP-402'));
    }

    public function test_filter_by_recent_attendance_narrows_results(): void
    {
        $withRecent = Employee::create([
            'employee_number' => 'EMP-501', 'name' => 'Reciente', 'last_name' => 'Asistencia', 'status' => 'activo',
            'client_id' => $this->client->id, 'service_point_id' => $this->servicePoint->id,
        ]);
        $withoutRecent = Employee::create([
            'employee_number' => 'EMP-502', 'name' => 'Sin', 'last_name' => 'Reciente', 'status' => 'activo',
        ]);

        Attendance::create([
            'employee_id' => $withRecent->id, 'client_id' => $this->client->id, 'service_point_id' => $this->servicePoint->id,
            'supervisor_id' => $this->admin->id, 'attendance_date' => now()->format('Y-m-d'),
            'status' => 'presente', 'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get('/colaboradores?has_recent_attendance=con')
            ->assertInertia(fn (Assert $page) => $page->has('employees.data', 1)
                ->where('employees.data.0.employee_number', 'EMP-501'));

        $this->actingAs($this->admin)
            ->get('/colaboradores?has_recent_attendance=sin')
            ->assertInertia(fn (Assert $page) => $page->has('employees.data', 1)
                ->where('employees.data.0.employee_number', 'EMP-502'));
    }

    public function test_filter_by_supervisor_narrows_results_to_assigned_scope(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $otherClient = Client::create(['name' => 'Otra Empresa', 'status' => 'activo']);
        $otherSp = ServicePoint::create(['client_id' => $otherClient->id, 'name' => 'Otro Punto', 'status' => 'activo']);

        SupervisorAssignment::create([
            'supervisor_user_id' => $supervisor->id,
            'client_id' => $this->client->id,
            'service_point_id' => $this->servicePoint->id,
        ]);

        Employee::create([
            'employee_number' => 'EMP-601', 'name' => 'Asignado', 'last_name' => 'A', 'status' => 'activo',
            'client_id' => $this->client->id, 'service_point_id' => $this->servicePoint->id,
        ]);
        Employee::create([
            'employee_number' => 'EMP-602', 'name' => 'No', 'last_name' => 'Asignado', 'status' => 'activo',
            'client_id' => $otherClient->id, 'service_point_id' => $otherSp->id,
        ]);

        $this->actingAs($this->admin)
            ->get("/colaboradores?supervisor_id={$supervisor->id}")
            ->assertInertia(fn (Assert $page) => $page->has('employees.data', 1)
                ->where('employees.data.0.employee_number', 'EMP-601'));
    }

    public function test_counts_by_client_and_service_point_are_returned(): void
    {
        Employee::create([
            'employee_number' => 'EMP-701', 'name' => 'Uno', 'last_name' => 'A', 'status' => 'activo',
            'client_id' => $this->client->id, 'service_point_id' => $this->servicePoint->id,
        ]);

        $this->actingAs($this->admin)
            ->get('/colaboradores')
            ->assertInertia(fn (Assert $page) => $page
                ->has('countsByClient', 1)
                ->where('countsByClient.0.total', 1)
                ->has('countsByServicePoint', 1)
                ->where('countsByServicePoint.0.total', 1));
    }
}
