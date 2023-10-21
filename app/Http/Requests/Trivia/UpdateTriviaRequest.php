<?php

namespace App\Http\Requests\Trivia;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTriviaRequest extends FormRequest
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
            'description' => 'sometimes|string|max:255',
            'questions' => 'array',
            'questions.*.id' => 'sometimes|required|integer',
            'questions.*.question_text' => 'sometimes|string',
            'questions.*.image_path' => 'sometimes|image|nullable',
            'questions.*.answers' => 'sometimes|array',
            'questions.*.answers.*.id' => 'sometimes|required|integer',
            'questions.*.answers.*.text' => 'sometimes|string',
            'questions.*.answers.*.is_correct' => 'sometimes|boolean',
        ];
        
    }
}
