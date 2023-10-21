<?php

namespace App\Http\Requests\Trivia;

use Illuminate\Foundation\Http\FormRequest;

class CreateTriviaRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'learning_info_id' => 'required|exists:learning_infos,id',
            'questions' => 'required|array',
            'questions.*.question_text' => 'required|string',
            'questions.*.answers' => 'required|array',
            'questions.*.answers.*.text' => 'required|string',
            'questions.*.answers.*.is_correct' => 'required|boolean',
            'questions.*.image_path' => 'nullable|image', 
        ];
        
    }
}
