<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Role;

class RegisterSuperAdmin extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roles = Role::pluck('name')->toArray();
        $validaRoles = implode(',', $roles);

        return [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'password_confirmation' => 'required|min:6',
            'roles' => 'required|array',
            'roles.*' => "in:$validaRoles", // Corregido aquí: $validaRoles en lugar de $validRoles
        ];
    }

    public function attributes(): array
    {
        return [
            'roles.*' => 'role',
        ];
    }
}
