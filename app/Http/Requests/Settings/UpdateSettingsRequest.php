<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Editar configuración');
    }

    public function rules(): array
    {
        return [
            'allow_employee_self_attendance' => 'required|boolean',
            'employee_self_attendance_requires_photo' => 'required|boolean',
            'employee_self_attendance_allow_exit' => 'required|boolean',
            'employee_self_attendance_requires_location' => 'required|boolean',
            'supervisor_capture_requires_photo' => 'required|boolean',
            'attendance_photo_review_enabled' => 'required|boolean',
            'attendance_photo_retention_days' => 'required|integer|min:1|max:3650',
            'attendance_warning_text' => 'required|string|max:2000',
            'attendance_warning_version' => 'required|integer|min:1',
        ];
    }
}
