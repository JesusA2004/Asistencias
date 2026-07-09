<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_dashboard_includes_all_new_metrics(): void
    {
        $client = Client::create(['name' => 'Empresa Demo', 'status' => 'activo']);
        $sp = ServicePoint::create(['client_id' => $client->id, 'name' => 'Punto Demo', 'status' => 'activo']);
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $employee = Employee::create([
            'employee_number' => 'EMP-001', 'name' => 'Juan', 'last_name' => 'Pérez',
            'status' => 'activo', 'client_id' => $client->id, 'service_point_id' => $sp->id,
        ]);
        Attendance::create([
            'employee_id' => $employee->id, 'client_id' => $client->id, 'service_point_id' => $sp->id,
            'supervisor_id' => $supervisor->id, 'attendance_date' => now()->format('Y-m-d'), 'status' => 'presente',
            'created_by' => $supervisor->id,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Dashboard')
                ->has('stats')
                ->has('chart_daily')
                ->has('chart_absents_by_client')
                ->has('status_distribution')
                ->has('compliance_by_location')
                ->has('lates_by_supervisor')
                ->has('top_incident_points')
                ->has('pending_captures_by_client')
                ->has('weekly_comparison.current')
                ->has('weekly_comparison.previous')
            );
    }

    public function test_supervisor_dashboard_includes_assigned_locations_and_recent_captures(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $client = Client::create(['name' => 'Empresa Demo', 'status' => 'activo']);
        SupervisorAssignment::create(['supervisor_user_id' => $supervisor->id, 'client_id' => $client->id, 'service_point_id' => null]);

        $this->actingAs($supervisor)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('supervisor/Dashboard')
                ->has('stats')
                ->has('assigned_locations_list', 1)
                ->has('recent_captures')
                ->has('chart_7_days')
            );
    }

    public function test_employee_dashboard_shows_month_stats(): void
    {
        $user = User::factory()->create();
        $user->assignRole('colaborador');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('employee/Dashboard'));
    }

    public function test_rh_dashboard_returns_basic_stats(): void
    {
        $user = User::factory()->create();
        $user->assignRole('rh');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('rh/Dashboard')
                ->has('stats.total_employees')
            );
    }
}
