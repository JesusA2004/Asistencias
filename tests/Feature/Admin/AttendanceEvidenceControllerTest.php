<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendancePhoto;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Services\AttendancePhotoService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceEvidenceControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $rh;

    private User $supervisorA;

    private User $colaborador;

    private Client $clientA;

    private Client $clientB;

    private AttendancePhoto $photoClientA;

    private AttendancePhoto $photoClientB;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrador');

        $this->rh = User::factory()->create();
        $this->rh->assignRole('rh');

        $this->supervisorA = User::factory()->create();
        $this->supervisorA->assignRole('supervisor');

        $this->colaborador = User::factory()->create();
        $this->colaborador->assignRole('colaborador');

        $this->clientA = Client::create(['name' => 'Empresa A', 'status' => 'activo']);
        $spA = ServicePoint::create(['client_id' => $this->clientA->id, 'name' => 'Punto A', 'status' => 'activo']);
        $employeeA = Employee::create([
            'employee_number' => 'A-001', 'name' => 'Ana', 'last_name' => 'Gómez',
            'status' => 'activo', 'client_id' => $this->clientA->id, 'service_point_id' => $spA->id,
        ]);

        $this->clientB = Client::create(['name' => 'Empresa B', 'status' => 'activo']);
        $spB = ServicePoint::create(['client_id' => $this->clientB->id, 'name' => 'Punto B', 'status' => 'activo']);
        $employeeB = Employee::create([
            'employee_number' => 'B-001', 'name' => 'Beto', 'last_name' => 'Ruiz',
            'status' => 'activo', 'client_id' => $this->clientB->id, 'service_point_id' => $spB->id,
        ]);

        SupervisorAssignment::create([
            'supervisor_user_id' => $this->supervisorA->id,
            'client_id' => $this->clientA->id,
            'service_point_id' => $spA->id,
        ]);

        $attendanceA = Attendance::create([
            'employee_id' => $employeeA->id, 'client_id' => $this->clientA->id, 'service_point_id' => $spA->id,
            'supervisor_id' => $this->admin->id, 'attendance_date' => now()->format('Y-m-d'),
            'status' => 'presente', 'created_by' => $this->admin->id,
        ]);
        $attendanceB = Attendance::create([
            'employee_id' => $employeeB->id, 'client_id' => $this->clientB->id, 'service_point_id' => $spB->id,
            'supervisor_id' => $this->admin->id, 'attendance_date' => now()->format('Y-m-d'),
            'status' => 'presente', 'created_by' => $this->admin->id,
        ]);

        $service = app(AttendancePhotoService::class);
        $this->photoClientA = $service->store(
            UploadedFile::fake()->image('a.jpg'), $attendanceA, null, $employeeA, $this->admin, 'entrada', 'admin', request(),
        );
        $this->photoClientB = $service->store(
            UploadedFile::fake()->image('b.jpg'), $attendanceB, null, $employeeB, $this->admin, 'entrada', 'admin', request(),
        );
    }

    public function test_admin_sees_all_evidences(): void
    {
        $this->actingAs($this->admin)
            ->get('/asistencias/evidencias')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('photos.data', 2));
    }

    public function test_rh_sees_all_evidences(): void
    {
        $this->actingAs($this->rh)
            ->get('/asistencias/evidencias')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('photos.data', 2));
    }

    public function test_supervisor_sees_only_assigned_client_evidences(): void
    {
        $this->actingAs($this->supervisorA)
            ->get('/asistencias/evidencias')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('photos.data', 1)
                ->where('photos.data.0.id', $this->photoClientA->id));
    }

    public function test_colaborador_sees_empty_scope_on_evidence_index(): void
    {
        // Colaborador tiene el permiso base "Ver evidencias de asistencia" (solo para poder
        // ver sus propias fotos vía la pestaña "Evidencias" de Mi Asistencia), pero no "ver
        // todas" ni "ver de sus ubicaciones", así que el índice de la galería general no le
        // muestra nada si llega a entrar directamente.
        $this->actingAs($this->colaborador)
            ->get('/asistencias/evidencias')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('photos.data', 0));
    }

    public function test_filter_by_client_narrows_results(): void
    {
        $this->actingAs($this->admin)
            ->get('/asistencias/evidencias?client_id='.$this->clientB->id)
            ->assertInertia(fn ($page) => $page
                ->has('photos.data', 1)
                ->where('photos.data.0.id', $this->photoClientB->id));
    }

    public function test_filter_by_search_narrows_results(): void
    {
        $this->actingAs($this->admin)
            ->get('/asistencias/evidencias?search=Beto')
            ->assertInertia(fn ($page) => $page
                ->has('photos.data', 1)
                ->where('photos.data.0.id', $this->photoClientB->id));
    }
}
