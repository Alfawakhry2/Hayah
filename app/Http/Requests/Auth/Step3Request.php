<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class Step3Request extends FormRequest
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
            'medical.age' => 'nullable|integer|min:0',
            'medical.length' => 'nullable|integer|min:0',
            'medical.weight' => 'nullable|integer|min:0',
            'medical.diagnosis' => "nullable|in:syndrome, genetic_mutation, oxygen_deficiency, other",
            'medical.severity' => 'nullable|in:mild, medium, severe',
            'medical.has_seizures' => 'boolean',
            'medical.on_medication' => 'boolean',
            'medical.medication_name' => 'nullable|string',
        ];
    }
}
