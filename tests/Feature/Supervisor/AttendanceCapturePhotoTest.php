<?php

namespace Tests\Feature\Supervisor;

use App\Models\AttendancePhoto;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Services\SettingsRepository;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceCapturePhotoTest extends TestCase
{
    use RefreshDatabase;

    private User $supervisor;

    private Client $client;

    private ServicePoint $servicePoint;

    private Employee $employeeA;

    private Employee $employeeB;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SystemSettingsSeeder::class);

        $this->supervisor = User::factory()->create();
        $this->supervisor->assignRole('supervisor');

        $this->client = Client::create(['name' => 'Empresa Demo', 'status' => 'activo']);
        $this->servicePoint = ServicePoint::create(['client_id' => $this->client->id, 'name' => 'Punto Demo', 'status' => 'activo']);

        $this->employeeA = Employee::create([
            'employee_number' => 'EMP-001', 'name' => 'Juan', 'last_name' => 'Pérez',
            'status' => 'activo', 'client_id' => $this->client->id, 'service_point_id' => $this->servicePoint->id,
        ]);
        $this->employeeB = Employee::create([
            'employee_number' => 'EMP-002', 'name' => 'Ana', 'last_name' => 'López',
            'status' => 'activo', 'client_id' => $this->client->id, 'service_point_id' => $this->servicePoint->id,
        ]);

        SupervisorAssignment::create([
            'supervisor_user_id' => $this->supervisor->id,
            'client_id' => $this->client->id,
            'service_point_id' => $this->servicePoint->id,
        ]);
    }

    private function requirePhoto(): void
    {
        app(SettingsRepository::class)->setMany([
            'supervisor_capture_requires_photo' => true,
        ]);
    }

    private function basePayload(): array
    {
        return [
            'client_id' => $this->client->id,
            'service_point_id' => $this->servicePoint->id,
            'attendance_date' => now()->format('Y-m-d'),
        ];
    }

    public function test_entry_blocked_when_photo_required_and_missing(): void
    {
        $this->requirePhoto();

        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/entrada', [
                ...$this->basePayload(),
                'entries' => [
                    ['employee_id' => $this->employeeA->id, 'entry_time' => '08:00', 'status' => 'presente'],
                ],
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_entry_succeeds_with_photo_per_employee(): void
    {
        $this->requirePhoto();

        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/entrada', [
                ...$this->basePayload(),
                'entries' => [
                    ['employee_id' => $this->employeeA->id, 'entry_time' => '08:00', 'status' => 'presente'],
                    ['employee_id' => $this->employeeB->id, 'entry_time' => '08:05', 'status' => 'presente'],
                ],
                'photos' => [
                    $this->employeeA->id => UploadedFile::fake()->image('a.jpg', 640, 480),
                    $this->employeeB->id => UploadedFile::fake()->image('b.jpg', 640, 480),
                ],
            ])
            ->assertRedirect();

        $this->assertSame(2, AttendancePhoto::count());
        $photoA = AttendancePhoto::where('employee_id', $this->employeeA->id)->firstOrFail();
        $this->assertSame('supervisor', $photoA->capture_origin);
        $this->assertSame('entrada', $photoA->capture_type);
        $this->assertNotNull($photoA->attendance_event_id);
        Storage::disk('local')->assertExists($photoA->photo_path);
        Storage::disk('local')->assertExists($photoA->thumbnail_path);
    }

    public function test_entry_blocked_when_one_of_several_employees_missing_photo(): void
    {
        $this->requirePhoto();

        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/entrada', [
                ...$this->basePayload(),
                'entries' => [
                    ['employee_id' => $this->employeeA->id, 'entry_time' => '08:00', 'status' => 'presente'],
                    ['employee_id' => $this->employeeB->id, 'entry_time' => '08:05', 'status' => 'presente'],
                ],
                'photos' => [
                    $this->employeeA->id => UploadedFile::fake()->image('a.jpg'),
                ],
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_exit_requires_photo_when_enabled(): void
    {
        $this->actingAs($this->supervisor)->post('/asistencias/capturar/entrada', [
            ...$this->basePayload(),
            'entries' => [
                ['employee_id' => $this->employeeA->id, 'entry_time' => '08:00', 'status' => 'presente'],
            ],
        ]);

        $this->requirePhoto();

        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/salida', [
                ...$this->basePayload(),
                'exits' => [
                    ['employee_id' => $this->employeeA->id, 'exit_time' => '17:00'],
                ],
            ])
            ->assertSessionHas('error');

        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/salida', [
                ...$this->basePayload(),
                'exits' => [
                    ['employee_id' => $this->employeeA->id, 'exit_time' => '17:00'],
                ],
                'photos' => [
                    $this->employeeA->id => UploadedFile::fake()->image('salida.jpg'),
                ],
            ])
            ->assertRedirect();

        $photo = AttendancePhoto::where('capture_type', 'salida')->firstOrFail();
        $this->assertSame($this->employeeA->id, $photo->employee_id);
    }

    public function test_incident_does_not_require_photo(): void
    {
        // La incidencia (falta, descanso, permiso, incapacidad, retardo) nunca exige
        // evidencia fotográfica, aunque "supervisor_capture_requires_photo" esté activo:
        // no hay una asistencia física que fotografiar.
        $this->requirePhoto();

        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/incidencia', [
                ...$this->basePayload(),
                'status' => 'falta',
                'employee_ids' => [$this->employeeA->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('attendances', 1);
        $this->assertSame(0, AttendancePhoto::where('capture_type', 'incidencia')->count());
    }

    public function test_manual_capture_requires_photo_when_enabled(): void
    {
        $this->requirePhoto();
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $this->actingAs($admin)
            ->post('/asistencias/capturar/manual', [
                ...$this->basePayload(),
                'records' => [
                    ['employee_id' => $this->employeeA->id, 'status' => 'presente', 'entry_time' => '08:00', 'exit_time' => '17:00'],
                ],
            ])
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->post('/asistencias/capturar/manual', [
                ...$this->basePayload(),
                'records' => [
                    ['employee_id' => $this->employeeA->id, 'status' => 'presente', 'entry_time' => '08:00', 'exit_time' => '17:00'],
                ],
                'photos' => [$this->employeeA->id => UploadedFile::fake()->image('manual.jpg')],
            ])
            ->assertRedirect();

        $photo = AttendancePhoto::where('capture_type', 'manual')->firstOrFail();
        $this->assertSame('admin', $photo->capture_origin);
    }

    public function test_photo_not_required_by_default(): void
    {
        $this->actingAs($this->supervisor)
            ->post('/asistencias/capturar/entrada', [
                ...$this->basePayload(),
                'entries' => [
                    ['employee_id' => $this->employeeA->id, 'entry_time' => '08:00', 'status' => 'presente'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('attendance_photos', 0);
    }
}
