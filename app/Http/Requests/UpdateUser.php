<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Role;

class UpdateUser extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function validateRolesExist($attribute, $value, $fail)
    {
        $roles = is_array($value) ? $value : [$value];

        // Obtener roles existentes
        $existingRoles = Role::pluck('name')->toArray();

        // Verificar que todos los roles proporcionados existan
        $nonExistentRoles = array_diff($roles, $existingRoles);

        if (!empty($nonExistentRoles)) {
            $fail("Los roles siguientes no existen: " . implode(', ', $nonExistentRoles));
        }
    }

    public function rules()
    {
        $userId = $this->route('user');

        return [
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'password' => 'sometimes|string|min:8|confirmed',
            'roles' => [
                'sometimes',
                'array',
                function ($attribute, $value, $fail) {
                    $this->validateRolesExist($attribute, $value, $fail);
                },
            ],

            // Campos de perfil
            'name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'identification' => 'sometimes|string|max:255',
            'birth_date' => 'sometimes|date',
        ];
    }
}
