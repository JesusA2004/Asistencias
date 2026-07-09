<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Editar usuarios');
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => 'required|string|max:100',
            'email' => "required|email|max:150|unique:users,email,{$userId}",
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ];
    }
}
