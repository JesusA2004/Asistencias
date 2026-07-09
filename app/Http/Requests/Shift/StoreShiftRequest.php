<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Crear turnos');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'work_days' => 'nullable|array',
            'work_days.*' => 'in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
            'tolerance_minutes' => 'required|integer|min:0|max:120',
            'status' => 'required|in:activo,inactivo',
        ];
    }
}
