<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class LearningInfoUpdateRequest extends FormRequest
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
    public function rules()
    {
        $learningInfoId = $this->route('id');

        return [
            'name' => 'sometimes|required||max:255',
            'description' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'images.*.id' => "sometimes|integer|exists:images,id,learning_info_id,{$learningInfoId}",
            'images.*.image' => 'sometimes|file|mimes:jpeg,png',
            'video' => 'sometimes|file|mimes:mp4',
            'text_audios' => 'sometimes|required|array',
            'text_audios.*.id' => "sometimes|integer|exists:text_audios,id,learning_info_id,{$learningInfoId}",
            'text_audios.*.text' => 'sometimes|required|string',
            'text_audios.*.audio' => 'sometimes|file|mimes:mp3,wav',
            'qr_associations' => 'sometimes|required|array',
            'qr_associations.*.qr_identifier' => "sometimes|required|string|exists:qr_info_associations,qr_identifier,learning_info_id,{$learningInfoId}",
            'qr_associations.*.latitude' => 'sometimes|required|numeric',
            'qr_associations.*.longitude' => 'sometimes|required|numeric',
            'qr_associations.*.location_id' => 'sometimes|required|exists:locations,id',
        ];
    }
}
