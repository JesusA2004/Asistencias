<?php

namespace App\Http\Requests\ServicePoint;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicePointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Crear puntos de servicio');
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:200',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:activo,inactivo',
        ];
    }
}
