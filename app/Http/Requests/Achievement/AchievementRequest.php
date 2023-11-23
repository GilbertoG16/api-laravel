<?php
namespace App\Http\Requests\Achievement;
use App\Models\Image;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
class AchievementRequest extends FormRequest
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
            'id' => 'required|integer',
            'name' => 'required|string',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png',
        ];
    }
}
?>