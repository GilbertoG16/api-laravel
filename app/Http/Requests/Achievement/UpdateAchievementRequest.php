<?php

namespace App\Http\Requests\Achievement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAchievementRequest extends FormRequest
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
        
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'photo_url.*' => 'sometimes|file|mimes:jpeg,png',
            'achievement_type_id' => 'sometimes|integer',
            'id_asociacion' => 'sometimes|integer',
        ];
    }
}
