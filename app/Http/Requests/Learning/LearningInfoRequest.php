<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class LearningInfoRequest extends FormRequest
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
            'name' => 'required|string',
            'description' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id',
            'text' => 'required|string',
            'images.*' => 'required|file|mimes:jpeg,png', 
            'video' => 'nullable|file|mimes:mp4',
            'audio' => 'nullable|file|mimes:mp3,wav',
            'qr_associations' => 'required|array',
            'qr_associations.*.latitude' => 'required|numeric',
            'qr_associations.*.longitude' => 'required|numeric',
            'qr_associations.*.location_id' => 'required|exists:locations,id',
        ];
    }
}
