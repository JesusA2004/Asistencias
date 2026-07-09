<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Crear empresas');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'business_name' => 'nullable|string|max:200',
            'rfc' => 'nullable|string|max:20',
            'status' => 'required|in:activo,inactivo',
        ];
    }
}
