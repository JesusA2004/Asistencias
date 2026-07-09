<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\AttendanceAudit;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ServicePoint;
use App\Models\Shift;
use App\Models\SupervisorAssignment;
use App\Policies\AttendanceAuditPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\ClientPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\ServicePointPolicy;
use App\Policies\ShiftPolicy;
use App\Policies\SupervisorAssignmentPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
        Role::class => \App\Policies\RolePolicy::class,
    ];

    public function register(): void {}

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
