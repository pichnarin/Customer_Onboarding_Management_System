<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CreateStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'system_id' => ['required', 'uuid', 'exists:systems,id'],
            'name' => ['required', 'string', 'max:100', Rule::unique('onboarding_stages')->where(function ($query) {
                return $query->where('system_id', $this->input('system_id'));
            })],
            'description' => ['nullable', 'string'],
            'sequence_order' => ['required', 'integer', 'min:1', Rule::unique('onboarding_stages')->where(function ($query) {
                return $query->where('system_id', $this->input('system_id'));
            })],
            'estimated_duration_days' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'The stage name must be unique within the same system.',
            'sequence_order.unique' => 'The sequence order must be unique within the same system.',
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
