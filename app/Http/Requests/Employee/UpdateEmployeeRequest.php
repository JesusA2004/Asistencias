<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Editar colaboradores');
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;

        return [
            'employee_number' => "required|string|max:30|unique:employees,employee_number,{$employeeId}",
            'name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'second_last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:activo,inactivo,baja',
            'client_id' => 'nullable|exists:clients,id',
            'service_point_id' => 'nullable|exists:service_points,id',
            'shift_id' => 'nullable|exists:shifts,id',
        ];
    }
}
