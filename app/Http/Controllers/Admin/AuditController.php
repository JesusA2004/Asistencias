<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AttendanceAudit::class);

        $audits = AttendanceAudit::query()
            ->with([
                'changer:id,name',
                'attendance.employee:id,employee_number,name,last_name',
                'attendance.client:id,name',
            ])
            ->when($request->action, fn ($q, $a) => $q->where('action', $a))
            ->when($request->changed_by, fn ($q, $u) => $q->where('changed_by', $u))
            ->when($request->date_from, fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('created_at', '<=', $d . ' 23:59:59'))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/Audit/Index', [
            'audits' => $audits,
            'users' => User::whereIn('id', AttendanceAudit::query()->select('changed_by')->distinct())
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => $request->only(['action', 'changed_by', 'date_from', 'date_to']),
        ]);
    }
}
