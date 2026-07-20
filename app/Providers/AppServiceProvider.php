<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\AttendanceAudit;
use App\Models\AttendancePhoto;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Policies\AttendanceAuditPolicy;
use App\Policies\AttendancePhotoPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\ClientPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\RolePolicy;
use App\Policies\ServicePointPolicy;
use App\Policies\SettingPolicy;
use App\Policies\ShiftPolicy;
use App\Policies\SupervisorAssignmentPolicy;
use App\Policies\UserPolicy;
use App\Services\AttendancePhotoService;
use App\Services\SettingsRepository;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends AuthServiceProvider
{
    protected $policies = [
        Client::class => ClientPolicy::class,
        ServicePoint::class => ServicePointPolicy::class,
        Shift::class => ShiftPolicy::class,
        Employee::class => EmployeePolicy::class,
        SupervisorAssignment::class => SupervisorAssignmentPolicy::class,
        Attendance::class => AttendancePolicy::class,
        AttendanceAudit::class => AttendanceAuditPolicy::class,
        Role::class => RolePolicy::class,
        User::class => UserPolicy::class,
        Setting::class => SettingPolicy::class,
        AttendancePhoto::class => AttendancePhotoPolicy::class,
    ];

    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class);
        $this->app->singleton(AttendancePhotoService::class);
    }

    public function boot(): void
    {
        $this->registerPolicies();
        $this->configureDefaults();
        Schema::defaultStringLength(125);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)->mixedCase()->letters()->numbers()->symbols()->uncompromised()
            : null
        );
    }
}
