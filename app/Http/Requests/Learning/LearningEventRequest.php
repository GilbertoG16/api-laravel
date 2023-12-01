<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LearningEventRequest extends FormRequest
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
         return [
             'name' => 'required|string|max:255',
             'description' => 'required|string',
             'start_event' => 'required|date_format:Y-m-d H:i:s',
             'end_event' => 'required|date_format:Y-m-d H:i:s|after:start_datetime',
             'learning_info_id' => 'required|exists:learning_infos,id',
         ];
     }

}
