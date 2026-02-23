<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'sequence_order' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'estimated_duration_days' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
        ], 422));
    }
}
