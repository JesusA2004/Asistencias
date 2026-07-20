<?php

namespace Tests\Feature\Console;

use App\Models\Attendance;
use App\Models\AttendancePhoto;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\User;
use App\Services\AttendancePhotoService;
use App\Services\SettingsRepository;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeExpiredAttendancePhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_photos_older_than_retention_window_and_keeps_recent_ones(): void
    {
        Storage::fake('local');
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SystemSettingsSeeder::class);
        app(SettingsRepository::class)->set('attendance_photo_retention_days', 30);

        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $client = Client::create(['name' => 'Empresa Demo', 'status' => 'activo']);
        $sp = ServicePoint::create(['client_id' => $client->id, 'name' => 'Punto Demo', 'status' => 'activo']);
        $employee = Employee::create([
            'employee_number' => 'EMP-001', 'name' => 'Juan', 'last_name' => 'Pérez',
            'status' => 'activo', 'client_id' => $client->id, 'service_point_id' => $sp->id,
        ]);
        $attendance = Attendance::create([
            'employee_id' => $employee->id, 'client_id' => $client->id, 'service_point_id' => $sp->id,
            'supervisor_id' => $admin->id, 'attendance_date' => now()->format('Y-m-d'),
            'status' => 'presente', 'created_by' => $admin->id,
        ]);

        $service = app(AttendancePhotoService::class);
        $oldPhoto = $service->store(UploadedFile::fake()->image('old.jpg'), $attendance, null, $employee, $admin, 'entrada', 'admin', request());
        $recentPhoto = $service->store(UploadedFile::fake()->image('recent.jpg'), $attendance, null, $employee, $admin, 'entrada', 'admin', request());

        $oldPhoto->forceFill(['captured_at' => now()->subDays(45)])->save();

        $this->artisan('attendance-photos:purge-expired')->assertSuccessful();

        $this->assertDatabaseMissing('attendance_photos', ['id' => $oldPhoto->id]);
        $this->assertDatabaseHas('attendance_photos', ['id' => $recentPhoto->id]);
        Storage::disk('local')->assertMissing($oldPhoto->photo_path);
        Storage::disk('local')->assertExists($recentPhoto->photo_path);
    }
}
