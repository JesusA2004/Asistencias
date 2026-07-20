<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $supervisor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SystemSettingsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrador');

        $this->supervisor = User::factory()->create();
        $this->supervisor->assignRole('supervisor');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'allow_employee_self_attendance' => true,
            'employee_self_attendance_requires_photo' => true,
            'employee_self_attendance_allow_exit' => true,
            'employee_self_attendance_requires_location' => false,
            'supervisor_capture_requires_photo' => false,
            'attendance_photo_review_enabled' => true,
            'attendance_photo_retention_days' => 60,
            'attendance_warning_text' => 'Texto de advertencia actualizado.',
            'attendance_warning_version' => 2,
        ], $overrides);
    }

    public function test_admin_can_view_settings_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/configuracion')
            ->assertOk();
    }

    public function test_supervisor_cannot_view_settings_page(): void
    {
        $this->actingAs($this->supervisor)
            ->get('/configuracion')
            ->assertForbidden();
    }

    public function test_admin_can_update_settings_and_cache_reflects_new_value(): void
    {
        $this->actingAs($this->admin)
            ->patch('/configuracion', $this->validPayload())
            ->assertRedirect();

        $this->assertSame('1', Setting::where('key', 'allow_employee_self_attendance')->value('value'));
        $this->assertSame('60', Setting::where('key', 'attendance_photo_retention_days')->value('value'));
        $this->assertTrue(setting('allow_employee_self_attendance'));
        $this->assertSame(60, setting('attendance_photo_retention_days'));
    }

    public function test_supervisor_cannot_update_settings(): void
    {
        $this->actingAs($this->supervisor)
            ->patch('/configuracion', $this->validPayload())
            ->assertForbidden();
    }

    public function test_retention_days_must_be_a_positive_integer(): void
    {
        $this->actingAs($this->admin)
            ->patch('/configuracion', $this->validPayload(['attendance_photo_retention_days' => 0]))
            ->assertSessionHasErrors('attendance_photo_retention_days');
    }
}
