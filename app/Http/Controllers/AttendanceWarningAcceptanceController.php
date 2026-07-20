<?php

namespace App\Http\Controllers;

use App\Models\AttendanceTermsAcceptance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceWarningAcceptanceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'context' => 'required|in:colaborador,supervisor',
        ]);

        AttendanceTermsAcceptance::create([
            'user_id' => $request->user()->id,
            'context' => $request->context,
            'warning_version' => setting('attendance_warning_version', 1),
            'warning_text_snapshot' => setting('attendance_warning_text', ''),
            'accepted_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return back();
    }
}
