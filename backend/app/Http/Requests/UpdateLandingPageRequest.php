<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLandingPageRequest extends FormRequest
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
            'about_me' => 'required|max: 1000',
            'skills' => 'required|max: 1000',
            'tech_stack_ids' => 'sometimes|array',
            'tech_stack_ids.*' => 'exists:tech_stacks,id'
        ];
    }
}
