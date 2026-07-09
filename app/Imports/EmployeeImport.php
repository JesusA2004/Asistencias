<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\ServicePoint;
use Illuminate\Contracts\Validation\Validator;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class EmployeeImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    public function model(array $row): Employee
    {
        $this->imported++;

        return new Employee([
            'employee_number' => $row['employee_number'],
            'name' => $row['name'],
            'last_name' => $row['last_name'],
            'second_last_name' => $row['second_last_name'] ?? null,
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'status' => $row['status'] ?? 'activo',
            'client_id' => $row['client_id'] ?? null,
            'service_point_id' => $row['service_point_id'] ?? null,
            'shift_id' => $row['shift_id'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'employee_number' => 'required|string|max:30|unique:employees,employee_number',
            'name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'second_last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|in:activo,inactivo,baja',
            'client_id' => 'nullable|exists:clients,id',
            'service_point_id' => 'nullable|exists:service_points,id',
            'shift_id' => 'nullable|exists:shifts,id',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->getData();

            if (! empty($data['service_point_id']) && ! empty($data['client_id'])) {
                $belongsToClient = ServicePoint::where('id', $data['service_point_id'])
                    ->where('client_id', $data['client_id'])
                    ->exists();

                if (! $belongsToClient) {
                    $validator->errors()->add('service_point_id', 'El punto de servicio no pertenece a la empresa indicada.');
                }
            }
        });
    }
}
