<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class Step2Request extends FormRequest
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
            'child.name' => 'required|string|max:255',
            'child.birth_date' => 'required|date',
            'child.gender' => 'required|string|in:male,female',
            'child.nationality' => 'nullable|string',
            'child.city' => 'nullable|string',
            'child.image' => 'nullable|image|max:5120'
        ];
    }
}
