<?php

namespace Tests\Feature\Employee;

use App\Models\Attendance;
use App\Models\AttendancePhoto;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\SettingsRepository;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SelfAttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $colaborador;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SystemSettingsSeeder::class);

        $client = Client::create(['name' => 'Empresa Demo', 'status' => 'activo']);
        $sp = ServicePoint::create(['client_id' => $client->id, 'name' => 'Punto Demo', 'status' => 'activo']);
        $shift = Shift::create([
            'name' => 'Matutino', 'start_time' => '08:00', 'end_time' => '17:00', 'tolerance_minutes' => 10, 'status' => 'activo',
        ]);

        $this->colaborador = User::factory()->create();
        $this->colaborador->assignRole('colaborador');

        $this->employee = Employee::create([
            'employee_number' => 'EMP-001', 'name' => 'Juan', 'last_name' => 'Pérez',
            'status' => 'activo', 'client_id' => $client->id, 'service_point_id' => $sp->id,
            'shift_id' => $shift->id, 'user_id' => $this->colaborador->id,
        ]);
    }

    private function enableSelfAttendance(bool $requiresPhoto = true, bool $allowExit = true): void
    {
        app(SettingsRepository::class)->setMany([
            'allow_employee_self_attendance' => true,
            'employee_self_attendance_requires_photo' => $requiresPhoto,
            'employee_self_attendance_allow_exit' => $allowExit,
            'employee_self_attendance_requires_location' => false,
        ]);
    }

    public function test_self_attendance_page_reports_disabled_by_default(): void
    {
        $this->actingAs($this->colaborador)
            ->get('/mi-asistencia')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('enabled', false));
    }

    public function test_colaborador_cannot_register_entry_when_disabled(): void
    {
        Setting::where('key', 'allow_employee_self_attendance')->update(['value' => '0']);
        app(SettingsRepository::class)->flush();

        $this->actingAs($this->colaborador)
            ->post('/mi-asistencia/entrada', [
                'photo' => UploadedFile::fake()->image('foto.jpg'),
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_colaborador_can_register_entry_with_required_photo(): void
    {
        $this->enableSelfAttendance(requiresPhoto: true);

        $this->actingAs($this->colaborador)
            ->post('/mi-asistencia/entrada', [
                'photo' => UploadedFile::fake()->image('foto.jpg', 800, 600),
                'device_time' => now()->toIso8601String(),
            ])
            ->assertRedirect();

        $attendance = Attendance::where('employee_id', $this->employee->id)->firstOrFail();
        $this->assertNotNull($attendance->entry_time);
        $this->assertSame(1, AttendancePhoto::where('attendance_id', $attendance->id)->count());
        $photo = AttendancePhoto::where('attendance_id', $attendance->id)->firstOrFail();
        $this->assertSame('colaborador', $photo->capture_origin);
        $this->assertSame('entrada', $photo->capture_type);
        $this->assertDatabaseHas('attendance_events', ['attendance_id' => $attendance->id, 'event_type' => 'entrada']);
        $this->assertDatabaseHas('attendance_audits', ['attendance_id' => $attendance->id, 'action' => 'creado']);
    }

    public function test_entry_fails_without_photo_when_required(): void
    {
        $this->enableSelfAttendance(requiresPhoto: true);

        $this->actingAs($this->colaborador)
            ->post('/mi-asistencia/entrada', [])
            ->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_entry_succeeds_without_photo_when_not_required(): void
    {
        $this->enableSelfAttendance(requiresPhoto: false);

        $this->actingAs($this->colaborador)
            ->post('/mi-asistencia/entrada', [])
            ->assertRedirect();

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('attendance_photos', 0);
    }

    public function test_duplicate_entry_is_blocked(): void
    {
        $this->enableSelfAttendance(requiresPhoto: false);

        $this->actingAs($this->colaborador)->post('/mi-asistencia/entrada', []);
        $this->actingAs($this->colaborador)
            ->post('/mi-asistencia/entrada', [])
            ->assertSessionHas('error', 'Ya registraste tu entrada hoy.');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_exit_without_entry_is_blocked(): void
    {
        $this->enableSelfAttendance(requiresPhoto: false);

        $this->actingAs($this->colaborador)
            ->post('/mi-asistencia/salida', [])
            ->assertSessionHas('error', 'No has registrado tu entrada hoy.');
    }

    public function test_exit_blocked_when_not_allowed_by_setting(): void
    {
        $this->enableSelfAttendance(requiresPhoto: false, allowExit: false);

        $this->actingAs($this->colaborador)->post('/mi-asistencia/entrada', []);

        $this->actingAs($this->colaborador)
            ->post('/mi-asistencia/salida', [])
            ->assertSessionHas('error', 'El registro de salida no está habilitado.');
    }

    public function test_colaborador_can_register_full_day(): void
    {
        $this->enableSelfAttendance(requiresPhoto: false);

        $this->actingAs($this->colaborador)->post('/mi-asistencia/entrada', [])->assertRedirect();
        $this->actingAs($this->colaborador)->post('/mi-asistencia/salida', [])->assertRedirect();

        $attendance = Attendance::where('employee_id', $this->employee->id)->firstOrFail();
        $this->assertNotNull($attendance->entry_time);
        $this->assertNotNull($attendance->exit_time);
    }

    public function test_user_without_linked_employee_cannot_register(): void
    {
        $this->enableSelfAttendance(requiresPhoto: false);
        $orphan = User::factory()->create();
        $orphan->assignRole('colaborador');

        $this->actingAs($orphan)
            ->post('/mi-asistencia/entrada', [])
            ->assertSessionHas('error', 'Tu cuenta no está vinculada a un colaborador.');

        $this->assertDatabaseCount('attendances', 0);
    }
}
