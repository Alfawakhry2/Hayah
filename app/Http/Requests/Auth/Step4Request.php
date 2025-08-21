<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class Step4Request extends FormRequest
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
            'ability.can_sit' => 'nullable|in:yes,no,with_help',
            'ability.can_walk' => 'nullable|in:yes,no,with_help',
            'ability.uses_hands' => 'nullable|in:yes,no,one_hand',
            'ability.target_goals' => 'nullable|array',
            // 'child_image' => 'nullable|image|max:5120',
        ];
    }
}
