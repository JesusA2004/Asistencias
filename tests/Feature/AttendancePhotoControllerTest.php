<?php

namespace Tests\Feature;

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

class AttendancePhotoControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $supervisorA;

    private User $supervisorB;

    private User $colaborador;

    private Employee $employee;

    private Attendance $attendance;

    private AttendancePhoto $photo;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrador');

        $this->supervisorA = User::factory()->create();
        $this->supervisorA->assignRole('supervisor');

        $this->supervisorB = User::factory()->create();
        $this->supervisorB->assignRole('supervisor');

        $client = Client::create(['name' => 'Empresa Demo', 'status' => 'activo']);
        $sp = ServicePoint::create(['client_id' => $client->id, 'name' => 'Punto Demo', 'status' => 'activo']);

        SupervisorAssignment::create([
            'supervisor_user_id' => $this->supervisorA->id,
            'client_id' => $client->id,
            'service_point_id' => $sp->id,
        ]);

        $this->employee = Employee::create([
            'employee_number' => 'EMP-001', 'name' => 'Juan', 'last_name' => 'Pérez',
            'status' => 'activo', 'client_id' => $client->id, 'service_point_id' => $sp->id,
        ]);

        $this->colaborador = User::factory()->create();
        $this->colaborador->assignRole('colaborador');
        $this->employee->update(['user_id' => $this->colaborador->id]);

        $this->attendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'client_id' => $client->id,
            'service_point_id' => $sp->id,
            'supervisor_id' => $this->admin->id,
            'attendance_date' => now()->format('Y-m-d'),
            'status' => 'presente',
            'created_by' => $this->admin->id,
        ]);

        $this->photo = app(AttendancePhotoService::class)->store(
            UploadedFile::fake()->image('foto.jpg', 800, 600),
            $this->attendance,
            null,
            $this->employee,
            $this->admin,
            'entrada',
            'colaborador',
            request(),
        );
    }

    public function test_service_generates_photo_and_thumbnail_files(): void
    {
        Storage::disk('local')->assertExists($this->photo->photo_path);
        Storage::disk('local')->assertExists($this->photo->thumbnail_path);
        $this->assertSame($this->attendance->id, $this->photo->attendance_id);
        $this->assertSame($this->employee->id, $this->photo->employee_id);
    }

    public function test_admin_can_view_photo(): void
    {
        $this->actingAs($this->admin)
            ->get("/evidencias-asistencia/{$this->photo->id}/foto")
            ->assertOk();
    }

    public function test_assigned_supervisor_can_view_photo(): void
    {
        $this->actingAs($this->supervisorA)
            ->get("/evidencias-asistencia/{$this->photo->id}/miniatura")
            ->assertOk();
    }

    public function test_unassigned_supervisor_cannot_view_photo(): void
    {
        $this->actingAs($this->supervisorB)
            ->get("/evidencias-asistencia/{$this->photo->id}/foto")
            ->assertForbidden();
    }

    public function test_colaborador_can_view_own_photo(): void
    {
        // El rol "colaborador" incluye "Ver evidencias de asistencia" (base) para poder ver
        // su propia pestaña "Evidencias" en Mi Asistencia; AttendancePhotoPolicy lo acota a
        // employee_id propio, así que nunca ve fotos de otros colaboradores.
        $this->actingAs($this->colaborador)
            ->get("/evidencias-asistencia/{$this->photo->id}/foto")
            ->assertOk();
    }

    public function test_other_colaborador_cannot_view_photo(): void
    {
        $other = User::factory()->create();
        $other->assignRole('colaborador');

        $this->actingAs($other)
            ->get("/evidencias-asistencia/{$this->photo->id}/foto")
            ->assertForbidden();
    }

    public function test_colaborador_gallery_index_returns_empty_scope(): void
    {
        // Colaborador tiene el permiso base para ver sus propias fotos, pero no "ver todas"
        // ni "ver de sus ubicaciones": si entra directo al índice de la galería, no ve nada.
        $this->actingAs($this->colaborador)
            ->get('/evidencias-asistencia')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('photos.data', 0));
    }
}
